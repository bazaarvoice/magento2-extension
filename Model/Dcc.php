<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataBuilderInterface;
use Bazaarvoice\Connector\Api\DccInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Builder
 *
 * @package Bazaarvoice\Connector\Model\Dcc
 */
class Dcc extends DataObject implements DccInterface
{
    /**
     * @var \Bazaarvoice\Connector\Api\StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    private $logger;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var \Bazaarvoice\Connector\Model\CurrentProductProvider
     */
    private $currentProductProvider;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataBuilderInterface
     */
    private $catalogDataBuilder;

    /**
     * DccBuilder constructor.
     *
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataBuilderInterface $catalogDataBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                 $productRepository
     * @param \Bazaarvoice\Connector\Model\CurrentProductProvider             $currentProductProvider
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface             $stringFormatter
     * @param \Bazaarvoice\Connector\Logger\Logger                            $logger
     * @param array                                                           $data
     */
    public function __construct(
        CatalogDataBuilderInterface $catalogDataBuilder,
        ProductRepositoryInterface $productRepository,
        CurrentProductProvider $currentProductProvider,
        StringFormatterInterface $stringFormatter,
        Logger $logger,
        array $data = []
    ) {
        $this->stringFormatter = $stringFormatter;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->currentProductProvider = $currentProductProvider;
        $this->catalogDataBuilder = $catalogDataBuilder;
        parent::__construct($data);
    }

    /**
     * @param int|null $productId
     * @param int|null $storeId
     *
     * @return string
     */
    public function getJson($productId = null, $storeId = null): ?string
    {
        $product = $this->getProduct($productId, $storeId);
        if ($product) {
            $this->build($product);
            return $this->stringFormatter->jsonEncode($this->getData());
        }

        return '';
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     *
     * @return $this
     */
    private function build($product)
    {
        $dccCatalogData = $this->catalogDataBuilder->build($product);
        $this->setCatalogData($dccCatalogData->getData());

        return $this;
    }

    /**
     * @param int|null $productId
     * @param int|null $storeId
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     */
    private function getProduct($productId = null, $storeId = null)
    {
        try {
            if ($productId && $storeId) {
                return $this->productRepository->getById($productId, $editMode = false, $storeId);
            }

            return $this->currentProductProvider->getProduct();
        } catch (NoSuchEntityException $e) {
            $this->logger->critical("Product does not exist. ID: $productId and store: $storeId. "
                ."Error: {$e->getTraceAsString()}");
        }

        return false;
    }

    /**
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface
     */
    private function getCatalogData()
    {
        return $this->getData('catalogData');
    }

    /**
     * @param \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface $catalogData
     *
     * @return \Bazaarvoice\Connector\Model\Dcc
     */
    private function setCatalogData($catalogData)
    {
        return $this->setData('catalogData', $catalogData);
    }
}
