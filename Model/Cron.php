<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;

/**
 * Class Cron
 *
 * @package Bazaarvoice\Connector\Model
 */
class Cron
{
    /** @var Logger $logger */
    private $logger;

    const JOB_CODE = 'bazaarvoice_send_orders';
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\PurchaseFeed
     */
    private $purchaseFeed;
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\ProductFeed
     */
    private $productFeed;

    /**
     * Cron constructor.
     *
     * @param Logger                                         $logger
     * @param ConfigProviderInterface                        $configProvider
     * @param \Bazaarvoice\Connector\Model\Feed\PurchaseFeed $purchaseFeed
     * @param \Bazaarvoice\Connector\Model\Feed\ProductFeed  $productFeed
     */
    public function __construct(
        Logger $logger,
        ConfigProviderInterface $configProvider,
        PurchaseFeed $purchaseFeed,
        ProductFeed $productFeed
    ) {
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->purchaseFeed = $purchaseFeed;
        $this->productFeed = $productFeed;
    }

    public function sendPurchaseFeed()
    {
        $this->logger->info('Begin Purchase Feed Cron');
        $this->purchaseFeed->generateFeed();

        $this->logger->info('End Purchase Feed Cron');
    }

    public function sendProductFeed()
    {
        $this->logger->info('Begin Product Feed Cron');
        $this->productFeed->generateFeed();
        $this->logger->info('End Product Feed Cron');
    }
}
