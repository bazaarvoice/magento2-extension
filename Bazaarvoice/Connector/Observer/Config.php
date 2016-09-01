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

namespace Bazaarvoice\Connector\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Indexer\Flat;
use Magento\Framework\Message\ManagerInterface;

class Config implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $_logger;
    protected $_indexer;
    protected $_messageManger;

    /**
     * @param Logger $logger
     * @param Flat $indexer
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Logger $logger,
        Flat $indexer,
        ManagerInterface $messageManager
    )
    {
        $this->_logger = $logger;
        $this->_indexer = $indexer;
        $this->_messageManger = $messageManager;
    }

    // @codingStandardsIgnoreStart
    public function execute(EventObserver $observer)
    {
        // @codingStandardsIgnoreEnd
        $this->_logger->debug('Store Config Save Event');
        $this->_indexer->executeFull();
        $this->_messageManger->addNotice(__('Bazaarvoice Product Feed Index has been flagged for rebuild.'));

    }
}