<?php

namespace Bazaarvoice\Connector\Block;

use Bazaarvoice\Connector\Helper\Data;
use Magento\Framework\View\Element\Template;

class SubmissionContainer extends Template
{
    protected $_helper;

    /**
     * SubmissionContainer constructor.
     * @param Template\Context $context
     * @param array $data
     * @param Data $helper
     */
    public function __construct(Template\Context $context, array $data, Data $helper)
    {
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return Template
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