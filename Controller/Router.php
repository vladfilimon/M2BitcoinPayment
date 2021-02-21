<?php
namespace VladFilimon\M2BitcoinPayment\Controller;

class Router implements \Magento\Framework\App\RouterInterface
{
    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    protected $actionFactory;

    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Config primary
     *
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * Url
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Response
     *
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;


    /**
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\ResponseInterface $response
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->_eventManager = $eventManager;
        $this->_url = $url;
        $this->_response = $response;
    }

    /**
     * In order to bypass cache, use the /checkout/ route for our cryptocurrency controllers.
     * Validate and match cryptocurency module actions and modify request
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $url_key = trim($request->getPathInfo(), '/');

        if(strpos($url_key, 'checkout/index/bitcoinPayment') === 0) {
            $request->setModuleName('m2bitcoinpayment')->setControllerName('index')->setActionName('bitcoinPayment');
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $url_key);

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        } elseif (strpos($url_key, 'checkout/cryptopayment/order') === 0) {
            $request->setModuleName('m2bitcoinpayment')->setControllerName('cryptopayment')->setActionName('order');
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $url_key);

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        } elseif (strpos($url_key, 'checkout/bitcoin/payment') === 0) {
            $request->setModuleName('m2bitcoinpayment')->setControllerName('bitcoin')->setActionName('payment');
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $url_key);

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        } elseif (strpos($url_key, 'checkout/bitcoin/pooling') === 0) {
            $request->setModuleName('m2bitcoinpayment')->setControllerName('bitcoin')->setActionName('pooling');
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $url_key);

            return $this->actionFactory->create('Magento\Framework\App\Action\Forward');
        }
    }
}
