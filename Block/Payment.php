<?php

namespace VladFilimon\M2BitcoinPayment\Block;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use VladFilimon\M2BitcoinPayment\Model\Bitcoin;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

class Payment extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Cyberia\CryptoPayment\Helper\Data
     */
    protected $_cryptoPaymentHelper;

    /** @var int */
    protected $_remainingTime;

    /**
     * @param DateTimeFactory $dateFactory
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        DateTimeFactory $dateFactory,
        Context $context,
        array $data = []
    ) {
        $this->_dateFactory = $dateFactory;
        return parent::__construct($context, $data);
    }



    protected function getPayment()
    {
        $payment = $this->getOrder()->getPayment();
        return $payment;
    }

    /**
     * Returns the cryptocurrency payment address
     * @return string
     */

    public function getAddress()
    {
        return $this
            ->getPayment()
            ->getAdditionalInformation(
                Bitcoin::PAYMENT_ADDITIONAL_INFO_FIELD_ADDRESS
            );
    }

    /**
     * Returns the URI for the cryptocurrency payment
     * @return string
     */
    public function getUri()
    {
        return $this->getPayment()
            ->getAdditionalInformation(
                Bitcoin::PAYMENT_ADDITIONAL_INFO_FIELD_URI
            );
    }

    public function getAmount()
    {
        /**
         * @TODO amont is now satoshis, do convert
         */
        return $this->getPayment()
            ->getAdditionalInformation(
                Bitcoin::PAYMENT_ADDITIONAL_INFO_FIELD_AMOUNT
            );
    }

    public function getRequiredConfirmations()
    {
        return $this->getPayment()
            ->getAdditionalInformation(
                Bitcoin::PAYMENT_ADDITIONAL_INFO_FIELD_CONFIRMATIONS
            );
    }

    public function getRemainingTime($forceReload = false)
    { return 21;
        if ($this->_remainingTime === NULL || $forceReload) {
            $order = $this->getOrder();
            $createdAt = $order->getCreatedAt();
            $dateObj = $this->_dateFactory->create();
            $expireOffset = $order->getPayment()
                ->getAdditionalInformation(
                    Bitcoin::PAYMENT_ADDITIONAL_INFO_FIELD_EXPIRES
                );
            $expireOffset = 1028;
            $secondsLeft = (($dateObj->gmtTimestamp($createdAt) + ($expireOffset * 60)) - $dateObj->gmtTimestamp());
            $this->_remainingTime = max(0, $secondsLeft);
        }

        return $this->_remainingTime;
    }

    public function getQr()
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        return base64_encode($writer->writeString($this->getUri()));
    }

}
