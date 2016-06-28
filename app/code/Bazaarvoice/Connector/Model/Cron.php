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
use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;

class Cron
{
    /** @var Data $helper */
    protected $helper;
    /** @var Logger $logger */
    protected $logger;
    /** @var  PurchaseFeed $purchaseFeed */
    protected $purchaseFeed;

    CONST JOB_CODE = 'bazaarvoice_send_orders';

    /**
     * Cron constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(Logger $logger, Data $helper, PurchaseFeed $purchaseFeed)
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->purchaseFeed = $purchaseFeed;
    }

    public function sendPurchaseFeed()
    {
        $this->logger->info('Begin Purchase Feed Cron');

        $this->purchaseFeed->generateFeed();

        $this->logger->info('End Purchase Feed Cron');
    }

}