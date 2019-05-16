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

    // @codingStandardsIgnoreStart
    public function beforeGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        $subject, $product)
    {
        // @codingStandardsIgnoreEnd
        if ($this->isEnabled()) {
            $this->_product = $product;
        }
    }

    // @codingStandardsIgnoreStart
    public function afterGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        $subject, $result)
    {
        // @codingStandardsIgnoreEnd
        if ($this->isEnabled()) {
            $productIdentifier = $this->helper->getProductId($this->_product);
            $productUrl = $this->_product->getProductUrl();
            $result = '
            <div data-bv-show="inline_rating"
				 data-bv-product-id="' . $productIdentifier . '"
				 data-bv-redirect-url="' . $productUrl . '"></div>' . $result;

        }
        return $result;
    }

}
