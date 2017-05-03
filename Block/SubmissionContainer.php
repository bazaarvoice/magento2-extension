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

namespace Bazaarvoice\Connector\Block;

use Bazaarvoice\Connector\Helper\Data;
use Magento\Framework\View\Element\Template;

class SubmissionContainer extends Template
{
    protected $_helper;

    /**
     * SubmissionContainer constructor.
     *
     * @param Template\Context $context
     * @param array            $data
     * @param Data             $helper
     */
    public function __construct(Template\Context $context, array $data, Data $helper)
    {
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return Template
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareLayout()
    {
        $this->pageConfig->addRemotePageAsset($this->getUrl('bazaarvoice/submission/container'),
            'canonical',
            ['attributes' => ['rel' => 'canonical']]);

        return parent::_prepareLayout();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_helper->getConfig('rr/enable_rr') && $this->_helper->getConfig('rr/container');
    }

}
