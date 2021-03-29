<?php
namespace VladFilimon\M2BitcoinPayment\Controller\Adminhtml\Connection;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use VladFilimon\M2BitcoinPayment\Model\Transport;

class Test extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config_system';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultFactory;

    /**
     * @var \VladFilimon\M2BitcoinPayment\Model\Transport
     */
    protected $_transport;

    /**
     * @param Context $context
     * @param JsonFactory $resultFactory
     * @param Transport $transport
     */
    public function __construct(
        Context $context,
        JsonFactory $resultFactory,
        Transport $transport
    ) {
        $this->_resultFactory = $resultFactory;
        $this->_transport = $transport;

        parent::__construct(
            $context
        );
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $result = ['valid' => 0, 'message' => __('Connection has failed')];

        $this->_transport->setConfig(
            $this->getRequest()->getParams()
        );

        try {
            $info = $this->_transport->getwalletinfo();
            if ($info && !empty($info->walletversion)) {
                $result = [
                    'valid' => 1,
                    'message' => __('Connection was successful')
                ];
            }
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }

        $resultJson = $this->_resultFactory->create();
        return $resultJson->setData($result);
    }
}
