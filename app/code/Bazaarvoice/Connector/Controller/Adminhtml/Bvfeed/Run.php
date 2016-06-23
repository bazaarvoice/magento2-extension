<?php
namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;

class Run extends \Magento\Backend\App\Action
{ 
    public function execute()
    {        
        echo "<pre>";
        /** @var PurchaseFeed $feed */
        $feed = \Magento\Framework\App\ObjectManager::getInstance()->get('Bazaarvoice\Connector\Model\Feed\PurchaseFeed');
        $feed->generateFeed(false, true);
    }   
}