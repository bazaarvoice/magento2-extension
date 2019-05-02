<?php

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

	/**
	 * @param EventObserver $observer
	 */
	public function execute(EventObserver $observer)
    {
        // @codingStandardsIgnoreEnd
        $this->_logger->debug('Store Config Save Event');
        $this->_messageManger->addNoticeMessage(__('Some configuration changes require the Bazaarvoice Product Feed Index to be rebuilt.  This can be done by running the bv:index magento command or click Rebuild Index in Catalog > Bazaarvoice Product Feed'));

    }
}