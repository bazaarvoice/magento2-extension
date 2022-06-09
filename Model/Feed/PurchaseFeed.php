<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Source\Trigger;
use Bazaarvoice\Connector\Model\XMLWriter;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Media\ConfigFactory;
use Magento\ConfigurableProduct\Model\Product\Type;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem\Io\File;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Class PurchaseFeed
 *
 * @package Bazaarvoice\Connector\Model\Feed
 */
class PurchaseFeed extends Feed
{
    const ALREADY_SENT_IN_FEED_FLAG = 'sent_in_bv_postpurchase_feed';

    /**
     * @var string
     */
    protected $typeId = 'purchase';
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var array
     */
    private $orderStatus;
    /**
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    private $mediaConfigFactory;

    /**
     * Constructor
     *
     * @param \Bazaarvoice\Connector\Logger\Logger                       $logger
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param ConfigProviderInterface                                    $configProvider
     * @param StringFormatterInterface                                   $stringFormatter
     * @param \Bazaarvoice\Connector\Model\XMLWriter                     $xmlWriter
     * @param \Magento\Framework\Filesystem\Io\File                      $filesystem
     * @param \Magento\Framework\App\State                               $state
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection                  $resourceConnection
     * @param \Magento\Catalog\Model\Product\Media\ConfigFactory         $mediaConfigFactory
     * @param array                                                      $orderStatus
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        XMLWriter $xmlWriter,
        File $filesystem,
        State $state,
        CollectionFactory $orderCollectionFactory,
        ResourceConnection $resourceConnection,
        ConfigFactory $mediaConfigFactory,
        $orderStatus = []
    ) {
        try {
            $state->getAreaCode();
        } catch (Exception $e) {
            $state->setAreaCode(Area::AREA_FRONTEND);
        }
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        $this->xmlWriter = $xmlWriter;
        $this->filesystem = $filesystem;
        $this->state = $state;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->orderStatus = $orderStatus;
        $this->mediaConfigFactory = $mediaConfigFactory;
    }

    /**
     * @return false|string
     */
    protected function getNumDaysLookbackStartDate()
    {
        return date('Y-m-d', strtotime(date('Y-m-d', time()).' -'.$this->configProvider->getNumDaysLookback().' days'));
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @param \Magento\Store\Api\Data\StoreInterface|Store        $store
     * @param String                                              $purchaseFeedFileName
     */
    public function sendOrders($orders, $store, $purchaseFeedFileName)
    {
        if ($this->test) {
            $purchaseFeedFileName = dirname($purchaseFeedFileName).'/test-'.basename($purchaseFeedFileName);
        }

        /**
         * Get client name for the scope 
         */
        $clientName = $this->configProvider->getClientName($store->getId());

        /**
         * Create varien io object and write local feed file 
         */
        /**
         * @var XMLWriter $writer 
         */
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/PostPurchaseFeed/5.6', $clientName);

        /**
         * @var \Magento\Sales\Model\Order $order 
         */
        foreach ($orders as $order) {
            $writer->startElement('Interaction');

            $writer->writeElement('TransactionDate', $this->getTriggeringEventDate($order));
            $writer->writeElement('EmailAddress', $order->getCustomerEmail());
            $writer->writeElement('Locale', $this->configProvider->getLocale($order->getStoreId()));
            $writer->writeElement('UserName', $order->getBillingAddress()->getFirstname());

            if ($order->getCustomerId()) {
                $userId = $order->getCustomerId();
            } else {
                $userId = hash('sha256', $order->getCustomerEmail());
            }
            $writer->writeElement('UserID', $userId);

            $writer->startElement('Products');

            /**
             * if families are enabled, get all items 
             */
            if ($this->configProvider->isFamiliesEnabled()) {
                $items = $order->getAllItems();
            } else {
                $items = $order->getAllVisibleItems();
            }
            foreach ($items as $item) {
                if ($this->configProvider->isFamiliesEnabled() && $item->getProductType() == Type\Configurable::TYPE_CODE) {
                    continue;
                }

                //product has been deleted or disabled
                if (!$item->getProduct() || $item->getProduct()->getStatus() == Status::STATUS_DISABLED) {
                    continue;
                }

                //parent product has been deleted or disabled
                if ($item->getParentItem()
                    && (!$item->getParentItem()->getProduct()
                    || $item->getParentItem()->getProduct()->getStatus() == Status::STATUS_DISABLED)
                ) {
                    continue;
                }

                /* @var Order\Item $item */
                $writer->startElement('Product');

                /**
                 * @var Product $product 
                 */
                $product = $item->getProduct();
                /**
                 * Using store on the order, to handle website/group data 
                 */
                $product->setStoreId($order->getStoreId());
                $this->storeManager->setCurrentStore($order->getStoreId());
                $product->load($product->getId());

                $writer->writeElement('ExternalId', $this->stringFormatter->getFormattedProductSku($product));
                $writer->writeElement('Name', $product->getName());

                $imageUrl = $this->mediaConfigFactory->create()->getMediaUrl($product->getSmallImage());
                $originalPrice = $item->getOriginalPrice();

                if ($item->getParentItem()) {
                    /**
                     * @var Order\Item $parentItem 
                     */
                    $parentItem = $item->getParentItem();

                    /**
                     * get price from parent item 
                     */
                    $originalPrice = $parentItem->getOriginalPrice();

                    if ($this->configProvider->isFamiliesEnabled()) {
                        if (strpos($imageUrl, 'placeholder/image.jpg') !== false) {
                            /**
                             * if product families are enabled and product has no image, use configurable image 
                             */
                            try {
                                $parent = $parentItem->getProduct();
                                $imageUrl = $this->mediaConfigFactory->create()->getMediaUrl($parent->getSmallImage());
                            } catch (Exception $e) {
                            }
                        }
                    }
                }

                $writer->writeElement('ImageUrl', $imageUrl);
                $writer->writeElement('Price', number_format((float)$originalPrice, 2, '.', ''));

                $writer->endElement();
                /**
                 * Product 
                 */
            }

            $writer->endElement();
            /**
             * Products 
             */

            $writer->endElement();
            /**
             * Interaction 
             */

            /**
             * Mark order as sent 
             */
            if ($this->test == false) {
                try {
                    $order->setData(self::ALREADY_SENT_IN_FEED_FLAG, true)->save();
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            } else {
                break;
            }
        }

        $this->closeFile($writer, $purchaseFeedFileName);
        $this->logger->debug("Wrote file $purchaseFeedFileName");

        /**
         * Upload feed 
         */
        $destinationFile = '/ppe/inbox/bv_ppe_tag_feed-magento-'.date('U').'.xml';
        if ($this->test == false) {
            $this->uploadFeed($purchaseFeedFileName, $destinationFile, $store);
        }
    }

    /**
     * @param String $xmlns      Bazaarvoice Feed xsd reference
     * @param String $clientName Bazaarvoice Client name
     *
     * @return \Bazaarvoice\Connector\Model\XMLWriter
     */
    protected function openFile($xmlns, $clientName)
    {
        $writer = $this->xmlWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Feed');
        $writer->writeAttribute('xmlns', $xmlns);
        $writer->writeAttribute('generator', 'Magento Extension r'.$this->configProvider->getExtensionVersion());

        return $writer;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    protected function getTriggeringEventDate(Order $order)
    {
        $timestamp = strtotime($order->getCreatedAt());

        if ($this->configProvider->getTriggeringEvent() === Trigger::SHIPPING) {
            $timestamp = $this->getLatestShipmentDate($order);
        }

        return date('c', $timestamp);
    }

    /**
     * @param Order $order
     *
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

        return $latestShipmentTimestamp;
        /**
         * This should be an int timestamp of num seconds since epoch 
         */
    }

    /**
     * @param Store $store
     */
    public function exportFeedForStore(Store $store)
    {
        $orders = $this->getOrders();
        /**
         * Add filter to limit orders to this store 
         */
        $orders->addFieldToFilter('store_id', $store->getId());
        $this->logger->debug('Found '.$orders->count().' orders to send.');

        /**
         * Build local file name / path 
         */
        $purchaseFeedFilePath = BP.'/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath.'/purchaseFeed-store-'.$store->getId().'-'.date('U').'.xml';
        /**
         * Write orders to file 
         */
        if ($orders->count()) {
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
        }
    }

    /**
     * @param Group $storeGroup
     */
    public function exportFeedForStoreGroup(Group $storeGroup)
    {
        $orders = $this->getOrders();
        $storeTable = $this->resourceConnection->getTableName('store');

        /**
         * Add filter to limit orders to this store group 
         */
        $orders->getSelect()
            ->joinLeft(
                ['store_table' => $storeTable],
                'main_table.store_id = store_table.store_id',
                'store_table.group_id'
            )
            ->where('store_table.group_id = '.$storeGroup->getId());
        $this->logger->debug('Found '.$orders->count().' orders to send.');

        /**
         * Build local file name / path 
         */
        $purchaseFeedFilePath = BP.'/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath.'/purchaseFeed-group-'.$storeGroup->getId().'-'.date('U').'.xml';
        /**
         * Using default store for now 
         */
        $store = $storeGroup->getDefaultStore();
        /**
         * Write orders to file 
         */
        if ($orders->count()) {
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
        }
    }

    /**
     * @param Website $website
     */
    public function exportFeedForWebsite(Website $website)
    {
        $orders = $this->getOrders();
        $storeTable = $this->resourceConnection->getTableName('store');
        /**
         * Add filter to limit orders to this website 
         */
        $orders->getSelect()
            ->joinLeft(
                ['store_table' => $storeTable],
                'main_table.store_id = store_table.store_id',
                'store_table.website_id'
            )
            ->where('store_table.website_id = '.$website->getId());
        $this->logger->debug('Found '.$orders->count().' orders to send.');

        /**
         * Build local file name / path 
         */
        $purchaseFeedFilePath = BP.'/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath.'/purchaseFeed-website-'.$website->getId().'-'.date('U').'.xml';
        /**
         * Using default store for now 
         */
        $store = $website->getDefaultStore();
        /**
         * Write orders to file 
         */
        if ($orders->count()) {
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
        }
    }

    /**
     */
    public function exportFeedForGlobal()
    {
        $orders = $this->getOrders();

        $orders->getSelect();
        $this->logger->debug('Found '.$orders->count().' orders to send.');

        /**
         \* Build local file name / path 
        */
        $purchaseFeedFilePath = BP.'/var/export/bvfeeds';
        $purchaseFeedFileName = $purchaseFeedFilePath.'/purchaseFeed-'.date('U').'.xml';

        /**
         * Using admin store for now 
         */
        $store = $this->storeManager->getStore(0);

        /**
         * Write orders to file 
         */
        if ($orders->count()) {
            $this->sendOrders($orders, $store, $purchaseFeedFileName);
        }
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getOrders()
    {
        $orders = $this->orderCollectionFactory->create();

        /**
         * Status is 'complete' or 'closed' 
         */
        if ($this->test == false) {
            $orders->addFieldToFilter(
                'status', [
                'in' => $this->orderStatus,
                ]
            );
        }

        /**
         * Only orders created within our look-back window 
         */
        $orders->addFieldToFilter('created_at', ['gteq' => $this->getNumDaysLookbackStartDate()]);
        /**
         * Include only orders that have not been sent or have errored out 
         */
        if ($this->test == false) {
            $orders->addFieldToFilter(
                [self::ALREADY_SENT_IN_FEED_FLAG, self::ALREADY_SENT_IN_FEED_FLAG],
                [
                    ['neq' => 1],
                    ['null' => 'null'],
                ]
            );
        }

        return $orders;
    }
}
