<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Observer;

use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Indexer\Flat;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class Config
 *
 * @package Bazaarvoice\Connector\Observer
 */
class Config implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var \Bazaarvoice\Connector\Model\Indexer\Flat
     */
    private $indexer;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManger;

    /**
     * @param Logger           $logger
     * @param Flat             $indexer
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

    // @codingStandardsIgnoreStart

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        // @codingStandardsIgnoreEnd
        $this->messageManger->addNoticeMessage(__('Some configuration changes may require rebuilding the Bazaarvoice Product Feed Index.  This can be done by running the bv:index magento command or clicking Rebuild Index in Catalog > Bazaarvoice Product Feed'));
    }
}
