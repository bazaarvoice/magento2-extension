<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData;

use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProductInterface;
use Magento\Framework\DataObject;

/**
 * @method array  getUpcs()
 * @method array  getEans()
 * @method array  getIsbns()
 * @method array  getFamilies()
 * @method string getFamily()
 * @method CatalogProduct setUpcs(array $upcs)
 * @method CatalogProduct setEans(array $eans)
 * @method CatalogProduct setIsbns(array $isbns)
 * @method CatalogProduct setFamilies(array $families)
 * @method CatalogProduct setFamily(string $family)
 */
class CatalogProduct extends DataObject implements CatalogProductInterface
{
    /**
     * @return string|null
     */
    public function getProductId()
    {
        return $this->getData('productId');
    }

    /**
     * @param string $productId
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setProductId(string $productId = null): CatalogProduct
    {
        return $this->setData('productId', $productId);
    }

    /**
     * @return string|null
     */
    public function getProductName()
    {
        return $this->getData('productName');
    }

    /**
     * @param string $productName
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setProductName(string $productName = null): CatalogProduct
    {
        return $this->setData('productName', $productName);
    }

    /**
     * @return string|null
     */
    public function getProductDescription()
    {
        return $this->getData('productDescription');
    }

    /**
     * @param string $productDescription
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setProductDescription(string $productDescription = null): CatalogProduct
    {
        return $this->setData('productDescription', $productDescription);
    }

    /**
     * @return string|null
     */
    public function getProductImageURL()
    {
        return $this->getData('productImageURL');
    }

    /**
     * @param string $productImageURL
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setProductImageURL(string $productImageURL = null): CatalogProduct
    {
        return $this->setData('productImageURL', $productImageURL);
    }

    /**
     * @return string|null
     */
    public function getProductPageURL()
    {
        return $this->getData('productPageURL');
    }

    /**
     * @param string $productPageUrl
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setProductPageURL(string $productPageUrl = null): CatalogProduct
    {
        return $this->setData('productPageURL', $productPageUrl);
    }

    /**
     * @return string|null
     */
    public function getBrandId()
    {
        return $this->getData('brandId');
    }

    /**
     * @param string $brandId
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setBrandId($brandId): CatalogProduct
    {
        return $this->setData('brandId', $brandId);
    }

    /**
     * @return string|null
     */
    public function getBrandName()
    {
        return $this->getData('brandName');
    }

    /**
     * @param string $brandName
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setBrandName($brandName): CatalogProduct
    {
        return $this->setData('brandName', $brandName);
    }

    /**
     * @return array|null
     */
    public function getCategoryPath()
    {
        return $this->getData('categoryPath');
    }

    /**
     * @param array $categoryPath
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setCategoryPath(array $categoryPath = null): CatalogProduct
    {
        return $this->setData('categoryPath', $categoryPath);
    }

    /**
     * @return array|null
     */
    public function getManufacturerPartNumbers()
    {
        return $this->getData('manufacturerPartNumbers');
    }

    /**
     * @param array $categoryPath
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setManufacturerPartNumbers(array $categoryPath = null): CatalogProduct
    {
        return $this->setData('manufacturerPartNumbers', $categoryPath);
    }

    /**
     * @return array|null
     */
    public function getModelNumbers()
    {
        return $this->getData('modelNumbers');
    }

    /**
     * @param array $categoryPath
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setModelNumbers(array $categoryPath = null): CatalogProduct
    {
        return $this->setData('modelNumbers', $categoryPath);
    }

    /**
     * @return string|null
     */
    public function getInactive()
    {
        return $this->getData('Inactive');
    }

    /**
     * @param string $inactive
     *
     * @return \Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct
     */
    public function setInactive(string $inactive): CatalogProduct
    {
        return $this->setData('Inactive', $inactive);
    }
}
