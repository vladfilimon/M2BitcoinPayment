<?php
namespace VladFilimon\M2BitcoinPayment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;

class Data extends AbstractHelper
{
    const XML_CONFIG_ENABLED = 'payment/vladfilimon_m2bitcoinpayment/active';
    const XML_CONFIG_PREFIX = 'payment/vladfilimon_m2bitcoinpayment/instance_prefix';
    const XML_CONFIG_HOST = 'payment/vladfilimon_m2bitcoinpayment/host';
    const XML_CONFIG_PORT = 'payment/vladfilimon_m2bitcoinpayment/port';
    const XML_CONFIG_SSL = 'payment/vladfilimon_m2bitcoinpayment/ssl';
    const XML_CONFIG_USER = 'payment/vladfilimon_m2bitcoinpayment/user';
    const XML_CONFIG_PASS = 'payment/vladfilimon_m2bitcoinpayment/pass';
    const XML_CONFIG_CONFIRM = 'payment/vladfilimon_m2bitcoinpayment/nr_confirmations';
    const XML_CONFIG_EXPIRE = 'payment/vladfilimon_m2bitcoinpayment/expire_minutes';

    public function isEnabled()
    {
        return $this->isModuleOutputEnabled() &&
            $this->_getConfig(self::XML_CONFIG_ENABLED);
    }

    public function getConfirmation()
    {
        return $this->_getConfig(self::XML_CONFIG_CONFIRM);
    }

    public function getExpire()
    {
        return $this->_getConfig(self::XML_CONFIG_EXPIRE);
    }

    public function getPrefix()
    {
        return $this->_getConfig(self::XML_CONFIG_PREFIX);
    }

    public function formatAmount($amount)
    {
        return (string)number_format($amount, 10);
    }

    public function getAccount(Order $order)
    {
        return $this->getPrefix() . $order->getIncrementId();
    }

    public function getConfig()
    {
        return [
            'host' => $this->_getConfig(self::XML_CONFIG_HOST),
            'port' => $this->_getConfig(self::XML_CONFIG_PORT),
            'ssl'  => $this->_getConfig(self::XML_CONFIG_SSL),
            'user' => $this->_getConfig(self::XML_CONFIG_USER),
            'pass' => $this->_getConfig(self::XML_CONFIG_PASS)
        ];
    }

    protected function _getConfig($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
