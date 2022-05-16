<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\Data\IndexInterface;
use Bazaarvoice\Connector\Api\IndexRepositoryInterface;
use Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory;
use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class IndexRepository
 *
 * @package Bazaarvoice\Connector\Model
 */
class IndexRepository implements IndexRepositoryInterface
{
    /**
     * @var \Bazaarvoice\Connector\Model\IndexFactory
     */
    private $objectFactory;

    /**
     * @var \Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\Api\SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;
    /**
     * @var \Bazaarvoice\Connector\Model\ResourceModel\Index
     */
    private $resourceModel;

    /**
     * IndexRepository constructor.
     *
     * @param \Bazaarvoice\Connector\Model\IndexFactory        $objectFactory
     * @param CollectionFactory                                $collectionFactory
     * @param SearchResultsInterfaceFactory                    $searchResultsFactory
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index $resourceModel
     */
    public function __construct(
        IndexFactory $objectFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        \Bazaarvoice\Connector\Model\ResourceModel\Index $resourceModel
    ) {
        $this->objectFactory = $objectFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @param \Bazaarvoice\Connector\Api\Data\IndexInterface $object
     *
     * @return \Bazaarvoice\Connector\Api\Data\IndexInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(IndexInterface $object)
    {
        try {
            /**
             * @noinspection PhpParamsInspection 
             */
            $this->resourceModel->save($object);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param $id
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param \Bazaarvoice\Connector\Api\Data\IndexInterface $object
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(IndexInterface $object)
    {
        try {
            /**
             * @noinspection PhpParamsInspection 
             */
            $this->resourceModel->delete($object);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return \Bazaarvoice\Connector\Api\Data\IndexInterface|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        $object = $this->objectFactory->create();
        $object->load($id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(
                __(
                    'Object with id "%1" does not exist.',
                    $id
                )
            );
        }

        return $object;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface|mixed
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $collection = $this->collectionFactory->create();

        if ($this->getIncludeQuoteData() === true) {
            $collection->addQuoteData();
        }

        if ($this->getIncludeCurrentVersionData() === true) {
            $collection->addCurrentVersionData();
        }

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType()
                    ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /**
             * @var SortOrder $sortOrder 
             */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC'
                        : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);

        return $searchResults;
    }

    /**
     * @param $productId
     * @param $storeId
     * @param $scope
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByProductIdStoreIdScope($productId, $storeId, $scope)
    {
        $object = $this->objectFactory->create();
        $object->loadByStore($productId, $storeId, $scope);
        if (!$object->getId()) {
            throw new NoSuchEntityException(
                __(
                    'Object with product ID "%1", store ID "%2", scope "%3" does not exist.',
                    $productId,
                    $storeId,
                    $scope
                )
            );
        }

        return $object;
    }
}
