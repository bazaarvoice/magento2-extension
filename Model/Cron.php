<?php

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;

class Cron
{
    /** @var Data $_helper */
    protected $_helper;
    /** @var Logger $_logger */
    protected $_logger;
    /** @var  ObjectManagerInterface $_objectManager */
    protected $_objectManager;

    CONST JOB_CODE = 'bazaarvoice_send_orders';

    /**
     * Cron constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(Logger $logger, Data $helper, ObjectManagerInterface $objectManager)
    {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_objectManager = $objectManager;
    }

    public function sendPurchaseFeed()
    {
        $this->_logger->info('Begin Purchase Feed Cron');

        $this->_objectManager->create('\Bazaarvoice\Connector\Model\Feed\PurchaseFeed')->generateFeed();

        $this->_logger->info('End Purchase Feed Cron');
    }

    public function sendProductFeed()
    {
        $this->_logger->info('Begin Product Feed Cron');

        $this->_objectManager->create('\Bazaarvoice\Connector\Model\Feed\ProductFeed')->generateFeed();

        $this->_logger->info('End Product Feed Cron');
    }

}