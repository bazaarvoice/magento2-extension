<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin\ProductList;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;

/**
 * Class Item
 *
 * @package Bazaarvoice\Connector\Plugin\ProductList
 */
class Item
{
    /* @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product */
    protected $product;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;
    /**
     * @var StringFormatterInterface
     */
    protected $stringFormatter;

    /**
     * Item constructor.
     *
     * @param ConfigProviderInterface  $configProvider
     * @param StringFormatterInterface $stringFormatter
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter
    ) {
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
    }

    /**
     * @param $subject
     * @param $product
     */
    public function beforeGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        // @codingStandardsIgnoreLine Squiz.Functions.MultiLineFunctionDeclaration.FirstParamSpacing
        $subject,
        $product
    ) {
        //todo: Combine with afterGetProductPrice into an around plugin?
        if ($this->isHostedInlineRatingsEnabled()) {
            $this->product = $product;
        }
    }

    /**
     * @return bool
     */
    public function isHostedInlineRatingsEnabled()
    {
        if (!$this->configProvider->isRrEnabled()) {
            return false;
        }

        $inlineRatings = $this->configProvider->getInlineRatings();

        if (!$inlineRatings) {
            return false;
        }

        $typesEnabled = explode(',', $inlineRatings);

        return in_array($this->type, $typesEnabled);
    }

    /**
     * @param $subject
     * @param $result
     *
     * @return string
     */
    public function afterGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        // @codingStandardsIgnoreLine Squiz.Functions.MultiLineFunctionDeclaration.FirstParamSpacing
        $subject,
        $result
    ) {
        //todo: Combine with afterGetProductPrice into an around plugin?
        if ($this->isHostedInlineRatingsEnabled()) {
            $productIdentifier = $this->stringFormatter->getFormattedProductSku($this->product);
            $productUrl = $this->product->getProductUrl();
            $result = '
            <!-- '. $this->getExtensionInjectionMessage() .' -->
            <div data-bv-show="inline_rating"
                 data-bv-seo="false"
				 data-bv-product-id="'.$productIdentifier.'"
				 data-bv-redirect-url="'.$productUrl.'"></div>'.$result;
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getExtensionInjectionMessage()
    {
        return $this->configProvider->getExtensionInjectionMessage();
    }
}
