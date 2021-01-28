<?php
namespace VladFilimon\M2BitcoinPayment\Block\Adminhtml\System\Config;

use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Test extends Field
{
    protected $_buttonLabel = 'Test RPC connection';

    protected $_buttonParams;

    /**
     * Add param to button
     *
     * @param string $name
     * @param string $element
     * @return \Magento\Config\Block\System\Config\Form\Field
     */
    public function addParam($name, $element)
    {
        $this->_buttonParams->addData($name, $element);

        return $this;
    }

    /**
     * Overwrite params to button
     *
     * @param string|array $name
     * @param string $element
     * @return \Magento\Config\Block\System\Config\Form\Field
     */
    public function setParam($name, $element=null)
    {
        $this->_buttonParams->setData($name, $element);

        return $this;
    }

    /**
     * Set the button label
     *
     * @param string $buttonLabel
     * @return \Magento\Config\Block\System\Config\Form\Field
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;
        
        return $this;
    }
	
    /**
     * Set template to itself
     *
     * @return \Magento\Config\Block\System\Config\Form\Field
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/test.phtml');
        } 
              
        $this->_buttonParams = new DataObject();
               
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()
			->unsCanUseWebsiteValue()
			->unsCanUseDefaultValue();
			
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $data = $element->getOriginalData();
        $label = !empty($data['button_label']) 
			? $data['button_label'] 
			: $this->_buttonLabel;
			
        $this->addData(
            [
                'button_label'   => __($label),
                'html_id'  	     => $element->getHtmlId(),
                'ajax_url'       => $this->_urlBuilder->getUrl('m2bitcoinpayment/connection/test'),
                'js_function'    => 'vfBitcoinRpcTest',
                'html_result_id' => 'vf_bitcoin_rpc_test',
            ]
        );
        
        return $this->_toHtml();
    }
    
    /**
     * Get the button params
     *
     * @return \Magento\Framework\DataObject
     */
    public function getParams()
    {         
        return $this->_buttonParams;
    }    
}
 
 
