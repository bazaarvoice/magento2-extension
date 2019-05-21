<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyBuilderInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterface;
use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterfaceFactory;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

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
     * @param null|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $parentProduct
     * @param string                                                                         $familyCode
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterface
     */
    public function build($parentProduct, $familyCode): ?FamilyInterface
    {
        $products = [$parentProduct];
        if ($parentProduct->getTypeId() == Configurable::TYPE_CODE
            && $this->configProvider->isFamiliesEnabled($parentProduct->getStoreId())
        ) {
            $children = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
            foreach ($children as $childProduct) {
                $products[] = $childProduct;
            }
        }

        $familyMembers = [];
        foreach ($products as $product) {
            $familyMembers[] = $this->getFamilyMember($product);
        }

        $dccFamily = $this->dccFamilyFactory->create();
        $dccFamily->setId($familyCode);
        $dccFamily->setExpand($this->configProvider->isFamiliesExpandEnabled($parentProduct->getStoreId()));
        $dccFamily->setMembers($familyMembers);

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
