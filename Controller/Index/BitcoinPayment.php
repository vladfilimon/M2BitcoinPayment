<?php
namespace VladFilimon\M2BitcoinPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Session\SessionManager;

/**
 * Controller class for ESI calls
 *
 * @package Cyberia\ESI\Controller\Index
 */
class BitcoinPayment extends Action
{

    const ERR_WALLET_CONNECTION_FAILED = 1;

    protected $_resultRawFactory;
    protected $_scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;
    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $quoteManagement;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var OrderSender
     */
    protected $orderSender;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var SessionManager
     */
    protected $sessionManager;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;



    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context,
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\ResultFactory $resultRawFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param OrderSender $orderSender,
     * @param \Psr\Log\LoggerInterface $logger
     * @param SessionManager $sessionManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $resultRawFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger,
        SessionManager $sessionManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder
    )
    {
        $this->_resultRawFactory = $resultRawFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        $this->quoteManagement = $quoteManagement;
        $this->_checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->_logger = $logger;
        $this->sessionManager = $sessionManager;
        $this->_quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->resultPageFactory = $resultPageFactory;

        return parent::__construct($context);
    }

    protected function getPendingOrderByQuoteId($quoteId) {
        $filters = [
            $this->filterBuilder
                ->setField('quote_id')
                ->setValue($quoteId)
                ->create(),
            $this->filterBuilder
                ->setField('state')
                ->setValue(\Magento\Sales\Model\Order::STATE_NEW)
                ->create() /*,
            $this->filterBuilder
                ->setField('status')
                ->setValue('pending')
                ->create()*/
        ];

        $this->searchCriteriaBuilder
            ->addFilters($filters)
            ->addSortOrder(
                $this->sortOrderBuilder
                    ->setField('created_at')
                    ->setAscendingDirection()
                    ->create()
            );

        $order = $this->orderRepository->getList(
            $this->searchCriteriaBuilder->create()
        )->getItems();
        /**
         * @TODO throw exception if no items in array
         */

        return reset($order);
    }

    /**
     * Proxy action for encoding the order id to a hash; Will be redirected to real payment page
     * @return mixed
     */
    public function execute()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();

        if(!$orderId) {
            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result = $this->resultRedirectFactory->create()->setPath('checkout/cart');
            $this->messageManager->addErrorMessage(__('Your checkout session has expired! Please add some products to the cart and try again.'));

            return $result;
        }

        $hashObj = new \Hashids\Hashids();
        $orderHashId = $hashObj->encode($orderId);
        /**
         * @var \Magento\Framework\Controller\ResultInterface $resultRedirect
         */
        $resultRedirect = $this->_resultRawFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        $resultRedirect->setPath(
            'checkout/bitcoin/payment',
            ['id' => $orderHashId]
        );

        return $resultRedirect;
    }

    /**
     * Make sure addresses will be saved without validation errors
     *
     * @return void
     */
    protected function ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
            if (!$this->_config->getValue('requireBillingAddress')
                && !$this->_quote->getBillingAddress()->getEmail()
            ) {
                $this->_quote->getBillingAddress()->setSameAsBilling(1);
            }
        }
    }

    /**
     * Get one page checkout model
     *
     * @return \Magento\Checkout\Model\Type\Onepage
     * @codeCoverageIgnore
     */
    public function getOnepage()
    {
        return $this->_objectManager->get(\Magento\Checkout\Model\Type\Onepage::class);
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckout()
    {
        return $this->_objectManager->get(\Magento\Checkout\Model\Session::class);
    }
}
