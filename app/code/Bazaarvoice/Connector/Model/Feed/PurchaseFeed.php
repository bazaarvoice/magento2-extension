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
use \Magento\Store\Model\Store;
use \Magento\Framework\Exception;
use \Bazaarvoice\Connector\Model\XMLWriter;


class PurchaseFeed extends Feed
{
    const ALREADY_SENT_IN_FEED_FLAG = 'sent_in_bv_postpurchase_feed';
    const TRIGGER_EVENT_PURCHASE = 'purchase';
    const TRIGGER_EVENT_SHIP = 'ship';

    protected $num_days_lookback;
    protected $triggering_event;
    protected $families;

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
        $this->families = $helper->getConfig('feeds/families');
    }

    public function generateFeed()
    {
        $this->logger->info('Start Bazaarvoice Purchase Feed Generation');
        // TODO: Scopes
        $this->exportFeedByStore();  
        $this->logger->info('End Bazaarvoice Purchase Feed Generation');
    }

    public function exportFeedByStore()
    {
        $this->logger->info('Exporting purchase feed file for each store / store view');

        $stores = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();
        
        foreach ($stores as $store) {
            /* @var \Magento\Store\Model\Store $store */
            try {
                if ($this->helper->getConfig('feeds/enable_purchase_feed', $store->getId()) === '1'
                    && $this->helper->getConfig('general/enable_bv', $store->getId()) === '1'
                ) {
                    $this->logger->info('Exporting purchase feed for store: ' . $store->getCode());
                    $this->exportFeedForStore($store);
                }
                else {
                    $this->logger->info('Purchase feed disabled for store: ' . $store->getCode());
                }
            }
            catch (Exception $e) {
                $this->logger->error('Failed to export daily purchase feed for store: ' . $store->getCode());
                $this->logger->error('Error message: ' . $e->getMessage());
            }
        }
        
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
        // !TODO Uncomment this
//        $orders->addFieldToFilter('status', array(
//            'in' => array(
//                'complete',
//                'closed'
//            )
//        ));

        // Only orders created within our look-back window
        $orders->addFieldToFilter('created_at', array('gteq' => $this->getNumDaysLookbackStartDate()));
        // Include only orders that have not been sent or have errored out
        $orders->addFieldToFilter(
            array(self::ALREADY_SENT_IN_FEED_FLAG, self::ALREADY_SENT_IN_FEED_FLAG),
            array(
                array('neq' => 1),
                array('null' => 'null')
            )
        );
        $this->logger->info('Found ' . $orders->count() . ' orders to send.');

        // Write orders to file
        $this->sendOrders($orders, $store);
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @param Store $store
     */
    public function sendOrders($orders, $store)
    {
        // Build local file name / path
        $purchaseFeedFilePath = BP . '/var/export/bvfeeds';
        $purchaseFeedFileName =
            $purchaseFeedFilePath . '/purchaseFeed-store-' . $store->getId() . /* '-' . date('U') . */ '.xml';
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
            $writer->writeElement('Locale', $this->helper->getConfig('general/locale'));
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

        }

        $this->closeFile($writer, $purchaseFeedFileName);

        // Upload feed
        //$this->uploadFeed($purchaseFeedFileName, $store);
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



