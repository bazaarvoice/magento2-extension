<?php
namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

class Run extends \Magento\Backend\App\Action
{ 
    public function execute()
    {        
        echo "<pre>";
        $feed = \Magento\Framework\App\ObjectManager::getInstance()->get('Bazaarvoice\Connector\Model\Feed\PurchaseFeed');
        $feed->generateFeed();
    }   
}