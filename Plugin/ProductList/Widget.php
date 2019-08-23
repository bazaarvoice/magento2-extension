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
    protected $type = ProductList::CATALOG_PRODUCTS_LIST_WIDGET;

    /**
     * @param $subject
     * @param $product
     */
    public function beforeGetProductPriceHtml(
        /** @noinspection PhpUnusedParameterInspection */
        // @codingStandardsIgnoreLine Squiz.Functions.MultiLineFunctionDeclaration.FirstParamSpacing
        $subject,
        $product
    ) {
        if ($this->isHostedInlineRatingsEnabled()) {
            $this->product = $product;
        }
    }

    /**
     * @param $subject
     * @param $result
     *
     * @return string
     */
    public function afterGetProductPriceHtml(
        /** @noinspection PhpUnusedParameterInspection */
        // @codingStandardsIgnoreLine Squiz.Functions.MultiLineFunctionDeclaration.FirstParamSpacing
        $subject,
        $result
    ) {
        if ($this->isHostedInlineRatingsEnabled()) {
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
