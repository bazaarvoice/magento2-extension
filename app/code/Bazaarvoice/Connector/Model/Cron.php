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

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;

class Cron
{
    /** @var Data $helper */
    protected $helper;
    /** @var Logger $logger */
    protected $logger;
    /** @var  ObjectManagerInterface $objectManager */
    protected $objectManager;

    CONST JOB_CODE = 'bazaarvoice_send_orders';

    /**
     * Cron constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(Logger $logger, Data $helper, ObjectManagerInterface $objectManager)
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->objectManager = $objectManager;
    }

    public function sendPurchaseFeed()
    {
        $this->logger->info('Begin Purchase Feed Cron');

        $this->objectManager->create('\Bazaarvoice\Connector\Model\Feed\PurchaseFeed')->generateFeed();

        $this->logger->info('End Purchase Feed Cron');
    }

    public function sendProductFeed()
    {
        $this->logger->info('Begin Product Feed Cron');

        $this->objectManager->create('\Bazaarvoice\Connector\Model\Feed\ProductFeed')->generateFeed();

        $this->logger->info('End Product Feed Cron');
    }

}