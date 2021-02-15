<?php
namespace VladFilimon\M2BitcoinPayment\Model;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Transport
{
    /**
     * @var string
     */
    protected $_host;

    /**
     * @var string
     */
    protected $_port;

    /**
     * @var bool
     */
    protected $_ssl;

    /**
     * @var string
     */
    protected $_user;

    /**
     * @var string
     */
    public $_pass;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_encryptor = $encryptor;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param string $method
     * @param array $params
     * @return stdClass
     */
    public function call($method, array $params)
    {
        if (false != ($client = $this->getClient())) {
            $client->setRawData(
                json_encode([
                        'method' => $method,
                        'params' => $params
                    ])
            )
                ->setHeaders('Content-type', 'application/json');

            $response = $client->request(\Zend_Http_Client::POST);
            if ($response->isSuccessful()) {
                $data = json_decode($response->getBody());
                if (empty($data->error) && !empty($data->result)) {
                    return $data->result;
                }
            }
        }
        return false;
    }

    /**
     * @param string $config
     * @param bool $checkEncrypt
     * @return this
     */
    public function setConfig(array $config, $checkEncrypt = false)
    {
        foreach (['host', 'port', 'ssl', 'user', 'pass'] as $field) {
            if (isset($config[$field])) {
                $param = "_{$field}";
                $value = $config[$field];

                if (preg_match('#^\*+$#', $value)) {
                    $value = $this->_scopeConfig->getValue(
                        'payment/vladfilimon_m2bitcoinpayment/' . $field,
                        ScopeInterface::SCOPE_STORE
                    );
                }

                $this->{$param} = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return ($this->_ssl ? 'https' : 'http') .
            '://' . $this->_user . ':' . $this->_pass .
            '@' . $this->_host . ':' . $this->_port;
    }

    /**
     * @return \Zend_Http_Client
     */
    public function getClient()
    {
        return new \Zend_Http_Client($this->getUrl(), [
            'adapter'     => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => [CURLOPT_SSL_VERIFYPEER => false],
        ]);
    }

    public function __call($method, array $args = [])
    {
        return $this->call(
            $method,
            $args
        );
    }
}
