<?php
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
     * @var
     */
    protected $productIds;
    /**
     * @var
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
        $subject,
        $product
    ) {
        //todo: Combine with afterGetProductPrice into an around plugin?
        // @codingStandardsIgnoreEnd
        if ($this->isBvEnabled()) {
            $this->product = $product;
        }
    }

    // @codingStandardsIgnoreStart

    /**
     * @return bool
     */
    public function isBvEnabled()
    {
        $typesEnabled = explode(',', $this->configProvider->getInlineRatings());

        return in_array($this->type, $typesEnabled);
    }

    // @codingStandardsIgnoreStart

    /**
     * @param $subject
     * @param $result
     *
     * @return string
     */
    public function afterGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        $subject,
        $result
    ) {
        //todo: Combine with afterGetProductPrice into an around plugin?
        // @codingStandardsIgnoreEnd
        if ($this->isBvEnabled()) {
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
