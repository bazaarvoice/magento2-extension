<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\ViewModel;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\CurrentProductProvider;
use Bazaarvoice\Connector\Model\Dcc;
use Bazaarvoice\Connector\Model\Source\Environment;
use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Product
 *
 * @package Bazaarvoice\Connector\ViewModel
 */
class Product implements ArgumentInterface
{
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    protected $bvLogger;
    /**
     * @var \Magento\ConfigurableProduct\Helper\Data
     */
    protected $configHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepo;
    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    protected $product;
    /**
     * @var int
     */
    protected $productId;
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;
    /**
     * @var \Bazaarvoice\Connector\Model\Dcc
     */
    private $dccBuilder;
    /**
     * @var \Bazaarvoice\Connector\Model\CurrentProductProvider
     */
    private $currentProductProvider;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;

    /**
     * Product constructor.
     *
     * @param \Magento\Framework\Registry                         $registry
     * @param ConfigProviderInterface                             $configProvider
     * @param StringFormatterInterface                            $stringFormatter
     * @param \Bazaarvoice\Connector\Logger\Logger                $logger
     * @param \Magento\ConfigurableProduct\Helper\Data            $configHelper
     * @param \Magento\Catalog\Model\ProductRepository            $productRepository
     * @param \Magento\Framework\Escaper                          $escaper
     * @param \Bazaarvoice\Connector\Model\Dcc                    $dccBuilder
     * @param \Bazaarvoice\Connector\Model\CurrentProductProvider $currentProductProvider
     */
    public function __construct(
        Registry $registry,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        Logger $logger,
        Data $configHelper,
        ProductRepository $productRepository,
        Escaper $escaper,
        Dcc $dccBuilder,
        CurrentProductProvider $currentProductProvider
    ) {
        $this->bvLogger = $logger;
        $this->coreRegistry = $registry;
        $this->configHelper = $configHelper;
        $this->productRepo = $productRepository;
        $this->escaper = $escaper;
        $this->dccBuilder = $dccBuilder;
        $this->currentProductProvider = $currentProductProvider;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
    }

    /**
     * @return ConfigProviderInterface
     */
    public function getConfigProvider()
    {
        return $this->configProvider;
    }

    /**
     * @return mixed
     */
    public function isBvEnabled()
    {
        return $this->configProvider->isBvEnabled();
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        if ($this->getProduct()) {
            return $this->stringFormatter->getFormattedProductSku($this->getProduct()->getSku());
        }

        return null;
    }

    /**
     * Get product object from core registry object
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->currentProductProvider->getProduct();
    }

    /**
     * @return false|string
     */
    public function getChildrenJson()
    {
        $children = [];
        if ($this->isConfigurable() && $this->configProvider->isRrChildrenEnabled()) {
            $product = $this->getProduct();

            /** @var Configurable $typeInstance */
            $typeInstance = $product->getTypeInstance();
            $childProducts = $typeInstance->getUsedProductCollection($product);
            $allowAttributes = $typeInstance->getConfigurableAttributes($product);

            /** @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $childProduct */
            foreach ($childProducts as $childProduct) {
                $key = '';
                foreach ($allowAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $childProduct->getData($productAttribute->getAttributeCode());

                    $key .= $productAttributeId.'_'.$attributeValue.'_';
                }
                $children[$key] = $this->stringFormatter->getFormattedProductSku($childProduct);
            }
        }
        /** @noinspection PhpParamsInspection */
        $this->bvLogger->info($children);

        return $this->stringFormatter->jsonEncode($children);
    }

    /**
     * @return bool
     */
    public function isConfigurable()
    {
        try {
            if ($this->getProductId() && $this->configProvider->isRrChildrenEnabled()) {
                return $this->getProduct()->getTypeId() == Configurable::TYPE_CODE;
            }
        } catch (Exception $e) {
            $this->bvLogger->crit($e->getMessage()."\n".$e->getTraceAsString());
        }

        return false;
    }

    /**
     * Get current product id
     *
     * @return null|int
     */
    public function getProductId()
    {
        if ($this->getProduct()) {
            return $this->getProduct()->getId();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getDccConfig()
    {
        $product = $this->getProduct();
        $dccConfigData = $this->dccBuilder->getJson($product->getId(), $product->getStoreId());

        return $dccConfigData;
    }

    /**
     * @return bool
     */
    public function canShowDebugDetails()
    {
        return ($this->configProvider->getEnvironment() == Environment::STAGING);
    }

    /**
     * @return bool
     */
    public function isDccEnabled()
    {
        return $this->configProvider->isDccEnabled();
    }
}
