<?php


namespace Bazaarvoice\Connector\Block\Adminhtml\System\Config\Sftp;

class TestConnection extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Bazaarvoice_Connector::system/config/testconnection.phtml');
        return $this;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('adminhtml/config_sftp/testconnection'),
                'field_mapping' => str_replace('"', '\\"', json_encode($this->_getFieldMapping()))
            ]
        );

        return $this->_toHtml();
    }

    protected function _getFieldMapping()
    {
        return [
            'username' => 'bazaarvoice_feeds_sftp_username',
            'password' => 'bazaarvoice_feeds_sftp_password',
            'host' => 'bazaarvoice_feeds_sftp_host_name'
        ];
    }

}