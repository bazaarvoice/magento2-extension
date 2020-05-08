<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\ViewModel;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\CurrentProductProvider;
use Bazaarvoice\Connector\Model\SeoContent;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Reviews
 *
 * @package Bazaarvoice\Connector\ViewModel
 */
class Reviews implements ArgumentInterface
{
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    protected $bvLogger;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Bazaarvoice\Connector\Model\SeoContent
     */
    private $seoContent;
    /**
     * @var \Bazaarvoice\Connector\Model\CurrentProductProvider
     */
    private $currentProductProvider;

    /**
     * Reviews constructor.
     *
     * @param \Bazaarvoice\Connector\Logger\Logger                $bvLogger
     * @param ConfigProviderInterface                             $configProvider
     * @param StringFormatterInterface                            $stringFormatter
     * @param \Bazaarvoice\Connector\Model\SeoContent             $seoContent
     * @param \Bazaarvoice\Connector\Model\CurrentProductProvider $currentProductProvider
     */
    public function __construct(
        Logger $bvLogger,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        SeoContent $seoContent,
        CurrentProductProvider $currentProductProvider
    ) {
        $this->bvLogger = $bvLogger;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        $this->seoContent = $seoContent;
        $this->currentProductProvider = $currentProductProvider;
    }

    /**
     * @return string
     */
    public function getAggregateSEOContent()
    {
        return $this->seoContent->getAggregateSeoContent();
    }

    /**
     * @return bool
     */
    public function canShow()
    {
        return $this->configProvider->isRrEnabled();
    }

    /**
     * @return false|string
     */
    public function getSeoContent()
    {
        return $this->seoContent->getRatingsReviewsSeoContent();
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        $currentProduct = $this->currentProductProvider->getProduct();
        if ($currentProduct) {
            return $this->stringFormatter->getFormattedProductSku($currentProduct->getSku());
        }

        return null;
    }

    /**
     * @return string
     */
    public function getExtensionInjectionMessage()
    {
        return $this->configProvider->getExtensionInjectionMessage();
    }
}
