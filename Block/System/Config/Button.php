<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

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
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @var \Magento\Backend\Block\Widget\Button $buttonBlock */
        $buttonBlock = $this->getForm()->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');

        $originalData = $element->getOriginalData();

        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : __('Run Feed');
        $buttonId = !empty($originalData['id']) ? $originalData['id'] : 'bv_export';
        $url = !empty($originalData['button_url']) ? $originalData['button_url'] : '*/bvfeed/runpurchase';
        $url = $this->getUrl($url);

        $data = [
            'id'      => $buttonId,
            'label'   => $buttonLabel,
            'onclick' => "window.open('" . $url . "')",
        ];

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }

}
