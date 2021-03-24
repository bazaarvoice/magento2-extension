<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Indexer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

/**
 * Class Flat
 *
 * @package Bazaarvoice\Connector\Model\Indexer
 */
class Indexer implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var \Bazaarvoice\Connector\Model\Indexer\Eav
     */
    protected $eavIndexer;
    /**
     * @var \Bazaarvoice\Connector\Model\Indexer\Flat
     */
    protected $flatIndexer;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Indexer constructor.
     *
     * @param \Bazaarvoice\Connector\Model\Indexer\Eav           $eavIndexer
     * @param \Bazaarvoice\Connector\Model\Indexer\Flat          $flatIndexer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Eav $eavIndexer,
        Flat $flatIndexer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->eavIndexer = $eavIndexer;
        $this->flatIndexer = $flatIndexer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @throws \Exception
     */
    public function executeFull()
    {
        if ($this->useFlatIndexer()) {
            $this->flatIndexer->executeFull();
        } else {
            $this->eavIndexer->executeFull();
        }
    }

    /**
     * @param array $ids
     *
     * @throws \Exception
     */
    public function execute($ids = [])
    {
        if ($this->useFlatIndexer()) {
            $this->flatIndexer->execute($ids);
        } else {
            $this->eavIndexer->execute($ids);
        }
    }

    /**
     * @param array|\int[] $ids
     *
     * @return mixed
     */
    public function executeList(array $ids)
    {
        return true;
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function executeRow($id)
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function useFlatIndexer(): bool
    {
        return $this->scopeConfig->getValue('catalog/frontend/flat_catalog_product')
            && $this->scopeConfig->getValue('catalog/frontend/flat_catalog_category');
    }
}
