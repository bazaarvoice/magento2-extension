<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @package   Bazaarvoice_Connector
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
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
    protected $logger;
    protected $indexer;
    protected $messageManger;

    /**
     * @param Logger $logger
     * @param Flat $indexer
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Logger $logger,
        Flat $indexer,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->indexer = $indexer;
        $this->messageManger = $messageManager;
    }

    public function execute(EventObserver $observer)
    {
        $this->logger->debug('Store Config Save Event');
        $this->indexer->executeFull();
        $this->messageManger->addNotice(__('Bazaarvoice Product Feed Index has been flagged for rebuild.'));

    }
}