<?php
namespace VladFilimon\M2BitcoinPayment\Model;

use VladFilimon\M2BitcoinPayment\Model\Transport;
use VladFilimon\M2BitcoinPayment\Helper\Data;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Block\Info\Instructions;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Paypal\Model\Config;

/**
 * Payment method model
 */
class Bitcoin extends AbstractMethod
{
    /**
     * Pending transfer status
     */
    const STATUS_PENDING_TRANSFER = 'bitcoin_pending_transfer';

    /**
     * Pending confirmed status
     */
    const STATUS_PENDING_CONFIRMED = 'bitcoin_pending_confirmed';

    /**
     * Payment method code
     */
    const PAYMENT_METHOD_CODE = 'vladfilimon_m2bitcoinpayment';

    /**
     * Payment method code
     */
    protected $_code = self::PAYMENT_METHOD_CODE;

    /**
     * Need to run payment initialize while order place?
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Can admins use this payment method?
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType =  Instructions::class;


    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @var Transport
     */
    protected $_transport;

    /**
     * @var Data
     */
    protected $_helper;

    const PAYMENT_ADDITIONAL_INFO_FIELD_ADDRESS = 'm2bitcoinpayment_address';
    const PAYMENT_ADDITIONAL_INFO_FIELD_AMOUNT = 'm2bitcoinpayment_amount';
    const PAYMENT_ADDITIONAL_INFO_FIELD_URI = 'm2bitcoinpayment_uri';
    const PAYMENT_ADDITIONAL_INFO_FIELD_CONFIRMATIONS = 'm2bitcoinpayment_confirmations';
    const PAYMENT_ADDITIONAL_INFO_FIELD_EXPIRES = 'm2bitcoinpayment_expires';
    const PAYMENT_ADDITIONAL_INFO_FIELD_TIME = 'm2bitcoinpayment_time';
    const PAYMENT_ADDITIONAL_INFO_FIELD_STATUS = 'm2bitcoinpayment_status';
    const PAYMENT_ADDITIONAL_INFO_FIELD_MEMO = 'm2bitcoinpayment_memo';

    /**
     * Initialize method
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentHelper $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param Transport $transport
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentHelper $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Transport $transport,
        Data $helper,
        array $data = []
    ) {
        $this->_transport = $transport;
        $this->_transport->setConfig($helper->getConfig());
        $this->_helper = $helper;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Method that will be executed instead of authorize or capture if
     * flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this
     */
    public function initialize($paymentAction, $stateObject)
    {
        /*
        if ($paymentAction !== Config::PAYMENT_ACTION_ORDER) {
            throw new \Exception(sprintf('ERR_PAYMENT_ACTION_NOT_IMPLEMENTED: %s', $paymentAction));
        }*/

        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        try {
            $address = $this->_transport->getnewaddress();

            if(is_null($address)) {
                throw new \Exception('ERR_WALLET_NO_RESPONSE');
            }
        } catch(\Exception $e) {
            $this->_logger->critical(sprintf('ERR_WALLET_CONNECTION_FAILED: %s', $e->getMessage()));
            throw new \Magento\Framework\Exception\LocalizedException(__('ERR_WALLET_CONNECTION_FAILED: We can\'t communicate with our wallet at this time.'));
        }

        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_ADDRESS,
            $address
        );
        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_AMOUNT,
            0.03//$walletResponse->getAmount()->getBitcoins() //getSatoshis()
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_URI,
            "bitcoin:{$address}?amount=0.03&label=URI&message=Payment"
        );
        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_CONFIRMATIONS,
            $this->_helper->getConfirmation()
        );
        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_EXPIRES,
            $this->_helper->getExpire()
        );
        /*
        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_TIME,
            $walletResponse->getTime()
        );*/
        /*
        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_STATUS,
            $walletResponse->getStatus()
        );*/
        /*
        $payment->setAdditionalInformation(
            self::PAYMENT_ADDITIONAL_INFO_FIELD_MEMO,
            $walletResponse->getMemo()
        );*/

        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setAnetTransType(self::ACTION_ORDER);

        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
        $payment->setAmountAuthorized($order->getTotalDue());

        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(self::STATUS_PENDING_TRANSFER);
        $stateObject->setIsNotified(false);

        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $payment->setParentTransactionId(null)
            ->setTransactionId($payment->getTxid())
            ->setIsTransactionClosed(true);

        return $this;
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        /**
         * @todo check if rate is available
         */
        return true;
    }

    /**
     * Returns the URL for redirecting to the payment page;
     * Actual redirect is being performed by frontend js in the method-renderer
     * This is still used as not to email details to the customer upon place order
     * @see \Magento\Quote\Observer\SubmitObserver::51
     *
     * Also it possibly acts as a fallback for older Magento2 versions (need checking)
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $urlBuilder = ObjectManager::getInstance()->get(UrlInterface::class);

        return $urlBuilder->getUrl('checkout/index/bitcoinPayment');
    }
}
