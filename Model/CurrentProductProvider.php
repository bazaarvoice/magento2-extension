<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Logger\Logger;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

/**
 * Class GetCurrentProductService
 */
class CurrentProductProvider
{
    /**
     * @var ProductInterface
     */
    private $currentProduct;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    private $bvLogger;

    /**
     * @param \Magento\Framework\Registry              $registry
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Bazaarvoice\Connector\Logger\Logger     $bvLogger
     */
    public function __construct(
        Registry $registry,
        ProductRepository $productRepository,
        Logger $bvLogger
    ) {
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->bvLogger = $bvLogger;
    }

    /**
     * Get current product. Currently uses the registry, which has been deprecated since Magento 2.3.
     * Will be modified when Magento implements the replacement.
     *
     * @return bool|\Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProduct()
    {
        if (empty($this->currentProduct)) {
            try {
                $product = $this->registry->registry('product');
                if ($product === null) {
                    throw new NoSuchEntityException();
                }
                $this->currentProduct = $this->productRepository->getById($product->getId());
            } catch (Exception $e) {
                $this->bvLogger->crit($e->getMessage()."\n".$e->getTraceAsString());

                return false;
            }
        }

        return $this->currentProduct;
    }
}
