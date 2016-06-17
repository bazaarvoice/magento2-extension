<?php
namespace Bazaarvoice\Connector\Block\System\Config;

/**
 * Class Export
 */
class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @var \Magento\Backend\Block\Widget\Button $buttonBlock  */
        $buttonBlock = $this->getForm()->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');

        $url = $this->getUrl("*/bvfeed/run");
        
        $data = [
            'id' => 'bv_pie_export' . $this->getVarnishVersion(),
            'label' => __('Run Bazaarvoice Purchase Feed'),
            'onclick' => "window.open('" . $url . "')",
        ];

        $html = $buttonBlock->setData($data)->toHtml();
        return $html;
    }
    
}