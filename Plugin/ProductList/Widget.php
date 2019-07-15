<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin\ProductList;

use Bazaarvoice\Connector\Model\Source\ProductList;

/**
 * Class Widget
 *
 * @package Bazaarvoice\Connector\Plugin\ProductList
 */
class Widget extends Item
{
    /**
     * @var string
     */
    protected $type = ProductList::WIDGET;

    // @codingStandardsIgnoreStart

    /**
     * @param $subject
     * @param $product
     */
    public function beforeGetProductPriceHtml(
        /** @noinspection PhpUnusedParameterInspection */
        $subject,
        $product
    ) {
        // @codingStandardsIgnoreEnd
        if ($this->configProvider->isBvEnabled()) {
            $this->product = $product;
        }
    }

    // @codingStandardsIgnoreStart

    /**
     * @param $subject
     * @param $result
     *
     * @return string
     */
    public function afterGetProductPriceHtml(
        /** @noinspection PhpUnusedParameterInspection */
        $subject,
        $result
    ) {
        // @codingStandardsIgnoreEnd
        if ($this->configProvider->isBvEnabled()) {
            $productIdentifier = $this->stringFormatter->getFormattedProductSku($this->product);
            $productUrl = $this->product->getProductUrl();
            $result = '
            <div data-bv-show="inline_rating"
				 data-bv-product-id="'.$productIdentifier.'"
				 data-bv-redirect-url="'.$productUrl.'"></div>'.$result;
        }

        return $result;
    }
}
