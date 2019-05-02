<?php

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Backend\App\Action\Context;

class Runproduct extends \Magento\Backend\App\Action
{

    /** @var  ProductFeed $_productFeed */
    protected $_productFeed;

    /**
     * Runproduct constructor.
     * @param Context $context
     * @param ProductFeed $productFeed
     */
    public function __construct(Context $context, ProductFeed $productFeed)
    {
        parent::__construct($context);
        $this->_productFeed = $productFeed;
    }

    public function execute()
    {
        echo '<pre>';
        $this->_productFeed->generateFeed(false, true);
    }
}