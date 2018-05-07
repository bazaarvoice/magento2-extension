<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Model\Source;

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\Registry;

class Category extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource {
	/** @var Product currentProduct */
	protected $currentProduct;
	protected $categories = [];
	protected $categoryRepository;

	/**
	 * Category constructor.
	 *
	 * @param Registry $registry
	 * @param Collection $categoryCollection
	 *
	 * @param CategoryRepository $categoryRepository
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function __construct(
		Registry $registry,
		Collection $categoryCollection,
		CategoryRepository $categoryRepository
	) {
		$this->categoryRepository = $categoryRepository;

		$this->currentProduct = $registry->registry( 'current_product' );
		if ( $this->currentProduct ) {
			$collection = $this->currentProduct->getCategoryCollection();
		} else {
            $collection = $categoryCollection;
		}
        $collection->addAttributeToSelect( 'name' );
        /** @var \Magento\Catalog\Model\Category $category */
        foreach ( $collection as $category ) {
            $names = [];
            foreach ( $category->getParentCategories() as $parent ) {
                $names[ $parent->getId() ] = $parent->getName();
            }
            $names[ $category->getId() ] = $category->getName();
            $name                        = implode( '/', $names );
            $this->categories[]          = [
                'value' => $category->getId(),
                'label' => $name
            ];
        }
	}

	/**
	 * @param int|string $value
	 *
	 * @return bool|string
	 */
	public function getOptionText( $value ) {
		return $value;
	}


	public function toOptionArray() {
		return array_merge( [ [ 'value' => '', 'label' => __( 'Please Select...' ) ] ], $this->categories );
	}

	/**
	 * @return array
	 */
	public function getAllOptions() {
		return $this->toOptionArray();
	}

	/**
	 * @return array
	 */
	public function getFlatColumns() {
		return [
			ProductFeed::CATEGORY_EXTERNAL_ID => [
				'unsigned' => false,
				'default'  => null,
				'extra'    => null,
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				'nullable' => true,
				'comment'  => 'Bazaarvoice Category ID',
			],
		];
	}


}