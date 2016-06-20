<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Block;

use Bazaarvoice\Connector\Helper\Data;
use Magento\Framework\View\Element\Template;

class SubmissionContainer extends Template
{
    protected $helper;

    /**
     * SubmissionContainer constructor.
     * @param Template\Context $context
     * @param array $data
     * @param Data $helper
     */
    public function __construct(Template\Context $context, array $data, Data $helper)
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
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
        return $this->helper->getConfig('rr/enable_rr') && $this->helper->getConfig('rr/container');
    }

}