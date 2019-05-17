<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductInterfaceFactory;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class CatalogProductBuilder
 *
 * @package Bazaarvoice\Connector\Model\Dcc\CatalogData
 */
class CatalogProductBuilder implements CatalogProductBuilderInterface
{
    private const UPC = 'upc';
    private const MPN = 'ManufacturerPartNumber';
    private const EAN = 'EAN';
    private const ISBN = 'ISBN';
    private const MODEL_NUMBER = 'ModelNumber';
    private const PRODUCT_SMALL_IMAGE = 'product_small_image';
    const BRAND = 'brand';
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductInterfaceFactory
     */
    private $dccCatalogProductFactory;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageHelperFactory;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathBuilderInterface
     */
    private $dccCategoryPathBuilder;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyBuilderInterface
     */
    private $dccFamilyBuilder;

    /**
     * CatalogDataBuilder constructor.
     *
     * @param \Bazaarvoice\Connector\Api\ConfigProviderInterface                                          $configProvider
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface                                         $stringFormatter
     * @param \Magento\Catalog\Model\CategoryRepository                                                   $categoryRepository
     * @param \Magento\Framework\Escaper                                                                  $escaper
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductInterfaceFactory              $dccCatalogProductFactory
     * @param \Magento\Catalog\Helper\ImageFactory                                                        $imageHelperFactory
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathBuilderInterface $dccCategoryPathBuilder
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyBuilderInterface       $dccFamilyBuilder
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        CategoryRepository $categoryRepository,
        Escaper $escaper,
        CatalogProductInterfaceFactory $dccCatalogProductFactory,
        ImageFactory $imageHelperFactory,
        CategoryPathBuilderInterface $dccCategoryPathBuilder,
        FamilyBuilderInterface $dccFamilyBuilder
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->escaper = $escaper;
        $this->dccCatalogProductFactory = $dccCatalogProductFactory;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        $this->imageHelperFactory = $imageHelperFactory;
        $this->dccCategoryPathBuilder = $dccCategoryPathBuilder;
        $this->dccFamilyBuilder = $dccFamilyBuilder;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product      $product
     * @param null|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $parentProduct
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductInterface
     */
    public function build($product, $parentProduct = null): ?CatalogProductInterface
    {
        $dccCatalogProduct = $this->dccCatalogProductFactory->create();
        $dccCatalogProduct->setProductId($this->getProductId($product));
        $dccCatalogProduct->setProductName($this->escaper->escapeHtml($product->getName()));
        $dccCatalogProduct->setProductDescription($this->escaper->escapeHtml($product->getData('short_description')));
        $dccCatalogProduct->setProductImageUrl($this->getProductImageUrl($product, $parentProduct));
        $dccCatalogProduct->setProductPageUrl($this->getProductPageUrl($product, $parentProduct));
        $dccCatalogProduct->setBrandId($this->getBrandId($product, $parentProduct));
        $dccCatalogProduct->setBrandName($this->getBrandName($product, $parentProduct));
        $dccCatalogProduct->setCategoryPath($this->getCategoryPaths($product));
        $dccCatalogProduct->setUpcs($this->getCustomAttributeData($product, static::UPC));
        $dccCatalogProduct->setManufacturerPartNumbers($this->getCustomAttributeData($product, static::MPN));
        $dccCatalogProduct->setEans($this->getCustomAttributeData($product, static::EAN));
        $dccCatalogProduct->setIsbns($this->getCustomAttributeData($product, static::ISBN));
        $dccCatalogProduct->setModelNumbers($this->getCustomAttributeData($product, static::MODEL_NUMBER));
        $dccCatalogProduct->setFamilies($this->getFamilies($parentProduct ?? $product));

        return $dccCatalogProduct;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return array
     */
    private function getCategoryPaths($product): array
    {
        if ($product->getData('bv_category_external_id')) {
            $categoryIds = [$product->getData('bv_category_external_id')];
        } else {
            $categoryIds = $product->getCategoryIds();
        }

        $categoryPaths = [];
        if ($categoryIds) {
            foreach ($categoryIds as $categoryId) {
                try {
                    $category = $this->categoryRepository->get($categoryId, $product->getStoreId());
                    if (!$category->getIsActive()) {
                        continue;
                    }
                    $categoryTree = $category->getPath();
                    $categoryTree = explode('/', $categoryTree);
                    array_shift($categoryTree);
                    foreach ($categoryTree as $key => $treeId) {
                        $parentCategory = $this->categoryRepository->get($treeId, $product->getStoreId());
                        $dccCategoryPath = $this->dccCategoryPathBuilder->build($parentCategory);
                        $categoryPaths[] = $this->prepareOutput($dccCategoryPath);
                    }
                    //phpcs:ignore
                } catch (NoSuchEntityException $e) {
                    //Category does not exist in this store
                }
            }
        }

        return $categoryPaths;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return bool|array
     */
    private function getFamilies($product): ?array
    {
        if ($this->configProvider->isFamiliesEnabled($product->getStoreId())) {
            $familyAttributeData[ProductInterface::SKU] = $product->getSku();
            if ($familyAttributes = $this->configProvider->getFormattedFamilyAttributes($product->getStoreId())) {
                foreach ($familyAttributes as $familyAttribute) {
                    if ($product->getData($familyAttribute)) {
                        $familyAttributeData[$familyAttribute] = $product->getData($familyAttribute);
                    }
                }
            }

            $dccFamilies = [];
            foreach ($familyAttributeData as $familyAttributeDatum) {
                $dccFamily = $this->dccFamilyBuilder->build($product, $familyAttributeDatum);
                $dccFamilies[] = $this->prepareOutput($dccFamily);
            }

            return $dccFamilies;
        }

        return null;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param $attributeCode
     *
     * @return mixed
     */
    private function getCustomAttributeData($product, $attributeCode)
    {
        $code = strtolower($attributeCode);
        $attr = $this->configProvider->getAttributeCode($code, $product->getStoreId());
        $value = [];
        if ($attr) {
            $value = $product->getAttributeText($attr);
            if (empty($value)) {
                $value = $product->getData($attr);
            }
            if (!empty($value)) {
                if (is_string($value) && strpos($value, ',') !== false) {
                    $value = $this->stringFormatter->explodeAndTrim(',', $value);
                } else {
                    $value = [$value];
                }
            }
        }

        return $value;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $parentProduct
     *
     * @return string
     */
    private function getProductImageUrl($product, $parentProduct = null)
    {
        if ($parentProduct && $parentProduct->getData('small_image')) {
            $productToUse = $parentProduct;
        } else {
            $productToUse = $product;
        }
        $imageUrl = $this->imageHelperFactory->create()->init($productToUse, static::PRODUCT_SMALL_IMAGE)->getUrl();
        return $this->escaper->escapeUrl($imageUrl);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $parentProduct
     *
     * @return string
     */
    private function getProductPageUrl($product, $parentProduct = null): string
    {
        return $this->escaper->escapeUrl($parentProduct ? $parentProduct->getProductUrl()
            : $product->getProductUrl());
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $parentProduct
     *
     * @return string|null
     */
    private function getBrandId($product, $parentProduct = null)
    {
        $brandAttr = $this->getBrandAttribute($product);
        $parentBrandId = null;
        if ($parentProduct) {
            $parentBrandAttr = $this->getBrandAttribute($parentProduct);
            $parentBrandId = $parentProduct->getData($parentBrandAttr);
        }
        return $product->getData($brandAttr) ?? $parentBrandId;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $parentProduct
     *
     * @return string|null
     */
    private function getBrandName($product, $parentProduct = null)
    {
        $brandAttr = $this->getBrandAttribute($product);
        $parentBrandName = null;
        if ($parentProduct) {
            $parentBrandAttr = $this->getBrandAttribute($parentProduct);
            $parentBrandName = $parentProduct->getAttributeText($parentBrandAttr);
        }

        return $product->getAttributeText($brandAttr) ?? $parentBrandName;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return string|null
     */
    private function getBrandAttribute($product)
    {
        return $this->configProvider->getAttributeCode(static::BRAND, $product->getStoreId());
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

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    private function getProductId($product): string
    {
        $prefix = '';
        if ($this->configProvider->isProductPrefixEnabled($product->getStoreId())) {
            $prefix = $this->configProvider->getPrefix($product->getStoreId());
        }
        return $prefix . $this->stringFormatter->getFormattedProductSku($product);
    }
}
