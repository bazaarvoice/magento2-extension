<?php
namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;
use Magento\Backend\App\Action\Context;

class Runpurchase extends \Magento\Backend\App\Action
{

    /** @var  PurchaseFeed $purchaseFeed */
    protected $purchaseFeed;

    /**
     * Runpurchase constructor.
     * @param Context $context
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(Context $context, PurchaseFeed $purchaseFeed)
    {
        parent::__construct($context);
        $this->purchaseFeed = $purchaseFeed;
    }

    public function execute()
    {        
        echo "<pre>";
        $this->purchaseFeed->generateFeed(false, true);
    }   
}