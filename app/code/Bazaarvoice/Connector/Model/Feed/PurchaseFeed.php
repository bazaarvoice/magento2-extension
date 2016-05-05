<?php
namespace Bazaarvoice\Connector\Model\Feed;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license 
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */

use \Magento\Catalog\Model\Product;
use \Magento\ConfigurableProduct\Model\Product\Type;
use \Magento\Sales\Model\Order;
use \Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use \Magento\Framework\Exception;
use \Bazaarvoice\Connector\Model\XMLWriter;
use \Magento\Store\Model\Website;


class PurchaseFeed extends Feed
{
    const ALREADY_SENT_IN_FEED_FLAG = 'sent_in_bv_postpurchase_feed';
    const TRIGGER_EVENT_PURCHASE = 'purchase';
    const TRIGGER_EVENT_SHIP = 'ship';

    protected $type_id = 'purchase';
    protected $num_days_lookback;
    protected $triggering_event;

    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($logger, $helper, $objectManager);

        $this->triggering_event = $helper->getConfig('feeds/triggering_event') === \Bazaarvoice\Connector\Model\Source\Trigger::SHIPPING ? self::TRIGGER_EVENT_SHIP : self::TRIGGER_EVENT_PURCHASE;
        $this->num_days_lookback = $helper->getConfig('feeds/lookback');
    }

    /**
     * @param Store $store
     */
    public function exportFeedForStore(Store $store)
    {
        /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
        $orderFactory = $this->objectManager->get('\Magento\Sales\Model\OrderFactory');
        /* @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $orderFactory->create()->getCollection();

        // Add filter to limit orders to this store
        $orders->addFieldToFilter('store_id', $store->getId());
        // Status is 'complete' or 'closed'
        if($this->test == false) {
            $orders->addFieldToFilter('status', array(
                'in' => array(
                    'complete',
                    'closed'
                )
            ));
        }

        // Only orders created within our look-back window
        $orders->addFieldToFilter('created_at', array('gteq' => $this->getNumDaysLookbackStartDate()));
        // Include only orders that have not been sent or have errored out
        if($this->test == false) {
            $orders->addFieldToFilter(
                array(self::ALREADY_SENT_IN_FEED_FLAG, self::ALREADY_SENT_IN_FEED_FLAG),
                array(
                    array('neq' => 1),
                    array('null' => 'null')
                )
            );
        }
        $this->logger->info('Found ' . $orders->count() . ' orders to send.');

        // Build local file name / path
        $purchaseFeedFilePath = BP . '/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath . '/purchaseFeed-store-' . $store->getId() . '-' . date('U') . '.xml';
        // Write orders to file
        if($orders->count())
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
    }

    /**
     * @param Group $storeGroup
     */
    public function exportFeedForStoreGroup(Group $storeGroup)
    {
        /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
        $orderFactory = $this->objectManager->get('\Magento\Sales\Model\OrderFactory');
        /* @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $orderFactory->create()->getCollection();

        // Add filter to limit orders to this store group
        $orders->getSelect()
            ->joinLeft('store', 'main_table.store_id = store.store_id', 'store.group_id')
            ->where('store.group_id = ' . $storeGroup->getId());
        // Status is 'complete' or 'closed'
        if($this->test == false) {
            $orders->addFieldToFilter('status', array(
                'in' => array(
                    'complete',
                    'closed'
                )
            ));
        }

        // Only orders created within our look-back window
        $orders->addFieldToFilter('created_at', array('gteq' => $this->getNumDaysLookbackStartDate()));
        // Include only orders that have not been sent or have errored out
        if($this->test == false) {
            $orders->addFieldToFilter(
                array(self::ALREADY_SENT_IN_FEED_FLAG, self::ALREADY_SENT_IN_FEED_FLAG),
                array(
                    array('neq' => 1),
                    array('null' => 'null')
                )
            );
        }
        $this->logger->info('Found ' . $orders->count() . ' orders to send.');

        // Build local file name / path
        $purchaseFeedFilePath = BP . '/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath . '/purchaseFeed-group-' . $storeGroup->getId() . '-' . date('U') . '.xml';
        // Using default store for now
        $store = $storeGroup->getDefaultStore();
        // Write orders to file
        if($orders->count())
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
    }


    /**
     * @param Website $website
     */
    public function exportFeedForWebsite(Website $website)
    {
        /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
        $orderFactory = $this->objectManager->get('\Magento\Sales\Model\OrderFactory');
        /* @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $orderFactory->create()->getCollection();

        // Add filter to limit orders to this website
        $orders->getSelect()
            ->joinLeft('store', 'main_table.store_id = store.store_id', 'store.website_id')
            ->where('store.website_id = ' . $website->getId());
        // Status is 'complete' or 'closed'
        if($this->test == false) {
            $orders->addFieldToFilter('status', array(
                'in' => array(
                    'complete',
                    'closed'
                )
            ));
        }

        // Only orders created within our look-back window
        $orders->addFieldToFilter('created_at', array('gteq' => $this->getNumDaysLookbackStartDate()));
        // Include only orders that have not been sent or have errored out
        if($this->test == false) {
            $orders->addFieldToFilter(
                array(self::ALREADY_SENT_IN_FEED_FLAG, self::ALREADY_SENT_IN_FEED_FLAG),
                array(
                    array('neq' => 1),
                    array('null' => 'null')
                )
            );
        }
        $this->logger->info('Found ' . $orders->count() . ' orders to send.');

        // Build local file name / path
        $purchaseFeedFilePath = BP . '/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath . '/purchaseFeed-website-' . $website->getId() . '-' . date('U') . '.xml';
        // Using default store for now
        $store = $website->getDefaultStore();
        // Write orders to file
        if($orders->count())
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
    }

    /**
     */
    public function exportFeedForGlobal()
    {
        /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
        $orderFactory = $this->objectManager->get('\Magento\Sales\Model\OrderFactory');
        /* @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $orderFactory->create()->getCollection();

        $orders->getSelect();
        // Status is 'complete' or 'closed'
        if($this->test == false) {
            $orders->addFieldToFilter('status', array(
                'in' => array(
                    'complete',
                    'closed'
                )
            ));
        }

        // Only orders created within our look-back window
        $orders->addFieldToFilter('created_at', array('gteq' => $this->getNumDaysLookbackStartDate()));
        // Include only orders that have not been sent or have errored out
        if($this->test == false) {
            $orders->addFieldToFilter(
                array(self::ALREADY_SENT_IN_FEED_FLAG, self::ALREADY_SENT_IN_FEED_FLAG),
                array(
                    array('neq' => 1),
                    array('null' => 'null')
                )
            );
        }
        $this->logger->info('Found ' . $orders->count() . ' orders to send.');

        // Build local file name / path
        $purchaseFeedFilePath = BP . '/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath . '/purchaseFeed-' . date('U') . '.xml';

        // Using admin store for now
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore(0);
        
        // Write orders to file
        if($orders->count())
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
    }    

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @param Store $store
     * @param String $purchaseFeedFileName
     */
    public function sendOrders($orders, $store, $purchaseFeedFileName)
    {
        if($this->test)
            $purchaseFeedFileName = dirname($purchaseFeedFileName) . '/test-' . basename($purchaseFeedFileName);

        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name', $store->getId());

        /** @var \Magento\Catalog\Helper\Product $productHelper */
        $productHelper = $this->objectManager->get('\Magento\Catalog\Helper\Product');

        // Create varien io object and write local feed file
        /** @var XMLWriter $writer */
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/PurchaseFeed/5.6', $clientName);

        foreach($orders as $order) {
            /** @var \Magento\Sales\Model\Order $order */

            $writer->startElement('Interaction');

            $writer->writeElement('TransactionDate', $this->getTriggeringEventDate($order));
            $writer->writeElement('EmailAddress', $order->getCustomerEmail());
            $writer->writeElement('Locale', $this->helper->getConfig('general/locale', $order->getStoreId()));
            $writer->writeElement('UserName', $order->getCustomerFirstname());

            if($order->getCustomerId()) {
                $userId = $order->getCustomerId();
            } else {
                $userId = md5($order->getCustomerEmail());
            }
            $writer->writeElement('UserID', $userId);

            $writer->startElement('Products');
            
            // if families are enabled, get all items
            if($this->families){
                $items = $order->getAllItems();
            } else {
                $items = $order->getAllVisibleItems();
            }
            foreach ($items as $item) {
                if($this->families && $item->getProductType() == Type\Configurable::TYPE_CODE)
                    continue;

                /* @var Order\Item $item */
                $writer->startElement('Product');
                
                /** @var Product $product */
                $product = $item->getProduct();
                // Using store on the order, to handle website/group data
                $product->setStoreId($order->getStoreId());
                $product->load($product->getId());
                
                $writer->writeElement('ExternalId', $this->helper->getProductId($product));
                $writer->writeElement('Name', $product->getName());

                $imageUrl = $productHelper->getImageUrl($product);
                $originalPrice = $item->getOriginalPrice();

                if($item->getParentItem()) {
                    /** @var Order\Item $parentItem */
                    $parentItem = $item->getParentItem();

                    // get price from parent item
                    $originalPrice = $parentItem->getOriginalPrice();

                    if($this->families) {
                        /** @var Product $parent */
                        $parent = $parentItem->getProduct();

                        if($product->getImage() == 'no_selection'){
                            // if product families are enabled and product has no image, use configurable image
                            $imageUrl = $productHelper->getImageUrl($parent);
                        }
                    }
                }

                $writer->writeElement('ImageUrl', $imageUrl);
                $writer->writeElement('Price', number_format((float)$originalPrice, 2));
                
                $writer->endElement(); // Product
            }

            $writer->endElement(); // Products

            $writer->endElement(); // Interaction
            
            // Mark order as sent
            if($this->test == false)
                $order->setData(self::ALREADY_SENT_IN_FEED_FLAG, true)->save();
            else
                break;
        }

        $this->closeFile($writer, $purchaseFeedFileName);
        $this->logger->info("Wrote file $purchaseFeedFileName");

        // Upload feed
        $destinationFile = '/ppe/inbox/bv_ppe_tag_feed-magento-' . date('U') . '.xml';
        if($this->test == false)
            $this->uploadFeed($purchaseFeedFileName, $destinationFile, $store);
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function getTriggeringEventDate(Order $order)
    {
        $timestamp = strtotime($order->getCreatedAt());

        if ($this->triggering_event === self::TRIGGER_EVENT_SHIP) {
            $timestamp = $this->getLatestShipmentDate($order);
        }

        return date('c', $timestamp);
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function getLatestShipmentDate(Order $order)
    {
        $latestShipmentTimestamp = 0;

        $shipments = $order->getShipmentsCollection();
        /* @var $shipment Order\Shipment */
        foreach ($shipments as $shipment) {
            $latestShipmentTimestamp = max(strtotime($shipment->getCreatedAt()), $latestShipmentTimestamp);
        }

        return $latestShipmentTimestamp; // This should be an int timestamp of num seconds since epoch
    }

    protected function getNumDaysLookbackStartDate()
    {
        return date('Y-m-d', strtotime(date('Y-m-d', time()) . ' -' . $this->num_days_lookback . ' days'));
    }

}



