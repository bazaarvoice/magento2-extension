<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterfaceFactory;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Magento\Framework\Escaper;

/**
 * Class CategoryPathBuilder
 *
 * @package Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
 */
class CategoryPathBuilder implements CategoryPathBuilderInterface
{
    /**
     * @var \Bazaarvoice\Connector\Api\StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterfaceFactory
     */
    private $dccCategoryPathFactory;
    /**
     * @var \Bazaarvoice\Connector\Api\ConfigProviderInterface
     */
    private $configProvider;

    /**
     * CategoryPathBuilder constructor.
     *
     * @param \Bazaarvoice\Connector\Api\ConfigProviderInterface                                          $configProvider
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface                                         $stringFormatter
     * @param \Magento\Framework\Escaper                                                                  $escaper
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterfaceFactory $dccCategoryPathFactory
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        Escaper $escaper,
        CategoryPathInterfaceFactory $dccCategoryPathFactory
    ) {
        $this->stringFormatter = $stringFormatter;
        $this->escaper = $escaper;
        $this->dccCategoryPathFactory = $dccCategoryPathFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category $category
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterface
     */
    public function build($category): ?CategoryPathInterface
    {
        $dccCategoryPath = $this->dccCategoryPathFactory->create();
        $dccCategoryPath->setId($this->getCategoryId($category));
        $dccCategoryPath->setName($this->getCategoryName($category));
        return $dccCategoryPath;
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category $category
     *
     * @return string
     */
    private function getCategoryId($category): string
    {
        $prefix = $this->configProvider->getCategoryPrefix($category->getStoreId());
        return $prefix . $this->stringFormatter->getFormattedCategoryPath($category);
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category $category
     *
     * @return string
     */
    private function getCategoryName($category): string
    {
        $prefix = $this->configProvider->getCategoryPrefix($category->getStoreId());
        return $prefix . $this->escaper->escapeHtml($category->getName());
    }
}
