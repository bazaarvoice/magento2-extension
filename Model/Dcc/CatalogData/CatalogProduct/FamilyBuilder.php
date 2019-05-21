<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterfaceFactory;
use Bazaarvoice\Connector\Api\StringFormatterInterface;

/**
 * Class FamilyBuilder
 *
 * @package Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
 */
class FamilyBuilder implements FamilyBuilderInterface
{
    /**
     * @var \Bazaarvoice\Connector\Api\StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Bazaarvoice\Connector\Api\ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterfaceFactory
     */
    private $dccFamilyFactory;

    /**
     * FamilyBuilder constructor.
     *
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface                                   $stringFormatter
     * @param \Bazaarvoice\Connector\Api\ConfigProviderInterface                                    $configProvider
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterfaceFactory $dccFamilyFactory
     */
    public function __construct(
        StringFormatterInterface $stringFormatter,
        ConfigProviderInterface $configProvider,
        FamilyInterfaceFactory $dccFamilyFactory
    ) {
        $this->stringFormatter = $stringFormatter;
        $this->configProvider = $configProvider;
        $this->dccFamilyFactory = $dccFamilyFactory;
    }

    /**
     * @param null|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param string                                                                         $familyCode
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterface
     */
    public function build($product, $familyCode): ?FamilyInterface
    {
        $dccFamily = $this->dccFamilyFactory->create();
        $dccFamily->setId($familyCode);
        $dccFamily->setExpand($this->configProvider->isFamiliesExpandEnabled($product->getStoreId()));
        $dccFamily->setMembers([$this->getFamilyMember($product)]);

        return $dccFamily;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    private function getFamilyMember($product): string
    {
        $prefix = '';
        if ($this->configProvider->isProductPrefixEnabled($product->getStoreId())) {
            $prefix = $this->configProvider->getPrefix($product->getStoreId());
        }
        return $prefix . $this->stringFormatter->getFormattedProductSku($product->getSku());
    }
}
