<?php
namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Backend\App\Action\Context;

class Runproduct extends \Magento\Backend\App\Action
{

    /** @var  ProductFeed $productFeed */
    protected $productFeed;

    /**
     * Runproduct constructor.
     * @param Context $context
     * @param ProductFeed $productFeed
     */
    public function __construct(Context $context, ProductFeed $productFeed)
    {
        parent::__construct($context);
        $this->productFeed = $productFeed;
    }

    public function execute()
    {
        echo "<pre>";
        $this->productFeed->generateFeed(false, true);
    }
}