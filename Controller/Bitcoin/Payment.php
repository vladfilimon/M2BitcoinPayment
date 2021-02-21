<?php
namespace VladFilimon\M2BitcoinPayment\Controller\Bitcoin;

use VladFilimon\M2BitcoinPayment\Model\Bitcoin;
use VladFilimon\M2BitcoinPayment\Model\Transport;
use VladFilimon\M2BitcoinPayment\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use \Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Exception\NoSuchEntityException;

class Payment extends Action
{
    /**
     * @var Transport
     */
    protected $_transport;

    protected $_resultRawFactory;

    protected $_scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context,
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param Transport $transport
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Transport $transport,
        Data $helper
    )
    {
        $this->_orderRepository = $orderRepository;
        $this->_pageResultFactory = $pageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_eventManager = $eventManager;
        $this->_transport = $transport;
        $this->_helper = $helper;

        return parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $hashObj = new \Hashids\Hashids();
        $decodedArray = $hashObj->decode($this->getRequest()->getParam('id'));
        if (!sizeof($decodedArray)) {
            /**
             * @todo log and redirect back to checkout
             */
            throw new \Exception('Order id could not be decoded!');
        }

        try {
            $order = $this->_orderRepository->get(reset($decodedArray));
        }
        catch(\NoSuchEntityException $e)
        {
            throw new \Exception('ERR_ORDER_NOT_FOUND');
        }

        $this->_transport->setConfig($this->_helper->getConfig());
        $payment = $order->getPayment();
        if ($payment->getMethod() != Bitcoin::PAYMENT_METHOD_CODE) {
            throw new \Exception('ERR_PAYMENT_METHOD_INCOMPATIBLE');
        }

        /**
         * Do not check $order->getBaseTotalDue() to determine if payment is complete
         * If order is in STATE_PROCESSING, it should be enough.
         * Thus, for special cases, we can confirm orders from admin without receiving the actual paymnet
         */
        switch($order->getState()) {
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                // Correct order state for payment page
                break;
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:

                // Payment for the order has been receive, redirect to success page
                $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->_checkoutSession->setLastOrderId($order->getId());
                /**
                 * @TODO make a separate success page
                 */
                return $this->_redirect('checkout/onepage/success');

                $page = $this->_pageResultFactory->create();
                $this->_eventManager->dispatch(
                    'checkout_onepage_controller_success_action',
                    ['order_ids' => [$order->getId()]]
                );

                return $page;
            case \Magento\Sales\Model\Order::STATE_NEW:
                throw new \Exception('ERR_PAYMENT_ORDER_STATE_INCOMPATIBLE: Please complete the checkout process first.');
                break;
            case \Magento\Sales\Model\Order::STATE_HOLDED:
                throw new \Exception('ERR_PAYMENT_ORDER_STATE_INCOMPATIBLE: We are currently reviewing your order.');
                break;
            case \Magento\Sales\Model\Order::STATE_CLOSED:
                throw new \Exception('ERR_PAYMENT_ORDER_STATE_INCOMPATIBLE: The order is closed.');
                break;
            default:
                throw new \Exception('ERR_PAYMENT_ORDER_STATE_INCOMPATIBLE');
                break;
        }

        $page = $this->_pageResultFactory->create();

        $page->getLayout()->getBlock('m2bitcoinpayment.bitcoin.payment')->setOrder($order);
        $this->getResponse()->setNoCacheHeaders();

        return $page;
    }

}
