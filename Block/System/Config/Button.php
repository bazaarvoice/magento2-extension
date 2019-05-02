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

        $originalData = $element->getOriginalData();

        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : __('Run Feed');
        $buttonId = !empty($originalData['id']) ? $originalData['id'] : 'bv_export';
        $url = !empty($originalData['button_url']) ? $originalData['button_url'] : '*/bvfeed/runpurchase';
        $url = $this->getUrl($url);

        $data = [
            'id' => $buttonId,
            'label' => $buttonLabel,
            'onclick' => "window.open('" . $url . "')",
        ];

        $html = $buttonBlock->setData($data)->toHtml();
        return $html;
    }
    
}