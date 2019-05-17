<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterfaceFactory;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Class CatalogDataBuilder
 *
 * @method CatalogData create()

 * @package Bazaarvoice\Connector\Model\Dcc
 */
class CatalogDataBuilder implements CatalogDataBuilderInterface
{
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductBuilderInterface
     */
    private $catalogProductBuilder;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterfaceFactory
     */
    private $catalogDataFactory;

    /**
     * CatalogDataBuilder constructor.
     *
     * @param \Bazaarvoice\Connector\Api\ConfigProviderInterface                             $configProvider
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface                            $stringFormatter
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductBuilderInterface $catalogProductBuilder
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterfaceFactory                $catalogDataFactory
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        CatalogProductBuilderInterface $catalogProductBuilder,
        CatalogDataInterfaceFactory $catalogDataFactory
    ) {
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        $this->catalogProductBuilder = $catalogProductBuilder;
        $this->catalogDataFactory = $catalogDataFactory;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface
     */
    public function build($product): ?CatalogDataInterface
    {
        $dccCatalogData = $this->catalogDataFactory->create();
        $dccCatalogData->setLocale($this->configProvider->getLocale($product->getStoreId()));
        $dccCatalogData->setCatalogProducts($this->getCatalogProducts($product));
        return $dccCatalogData;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $parentProduct
     *
     * @return array|null
     */
    private function getCatalogProducts($parentProduct): ?array
    {
        $parentData = $this->getCatalogProduct($parentProduct);
        $productData = [$parentData];
        if ($parentProduct->getTypeId() == Configurable::TYPE_CODE
            && $this->configProvider->isFamiliesEnabled($parentProduct->getStoreId())
        ) {
            $children = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
            foreach ($children as $childProduct) {
                $childData = $this->getCatalogProduct($childProduct, $parentProduct);
                $productData[] = $childData;
            }
        }

        return $productData;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product      $product
     * @param null|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $parentProduct
     *
     * @return array
     */
    private function getCatalogProduct($product, $parentProduct = null)
    {
        $dccCatalogProduct = $this->catalogProductBuilder->build($product, $parentProduct);

        return $this->prepareOutput($dccCatalogProduct);
    }

    /**
     * @param $object
     *
     * @return array
     */
    private function prepareOutput($object)
    {
        /** @var \Magento\Framework\Model\AbstractModel $object */
        return $this->stringFormatter->stripEmptyValues($object->getData());
    }
}
