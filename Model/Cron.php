<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

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