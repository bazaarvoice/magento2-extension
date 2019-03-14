<?php

namespace Bazaarvoice\Connector\Api;

use Bazaarvoice\Connector\Api\Data\IndexInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface IndexRepositoryInterface
{
    /**
     * @param \Bazaarvoice\Connector\Api\Data\IndexInterface $object
     *
     * @return \Bazaarvoice\Connector\Api\Data\IndexInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(IndexInterface $object);

    /**
     * @param $id
     *
     * @return \Bazaarvoice\Connector\Api\Data\IndexInterface|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface|mixed
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param \Bazaarvoice\Connector\Api\Data\IndexInterface $object
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(IndexInterface $object);

    /**
     * @param $id
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id);

    /**
     * @param $productId
     * @param $storeId
     * @param $scope
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByProductIdStoreIdScope($productId, $storeId, $scope);
}
