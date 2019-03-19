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

namespace Bazaarvoice\Connector\Block;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Product
 * @package Bazaarvoice\Connector\Block
 */
class Product extends \Magento\Framework\View\Element\Template {

    protected $_customAttributes = [ 'UPC', 'ManufacturerPartNumber', 'EAN', 'ISBN', 'ModelNumber' ];

    /* @var \Magento\Framework\Registry */
    protected $_coreRegistry;

    /* @var \Bazaarvoice\Connector\Helper\Data */
    public $helper;

    /* @var \Bazaarvoice\Connector\Logger\Logger */
    public $bvLogger;

    /** @var  \Magento\ConfigurableProduct\Helper\Data */
    public $configHelper;

    /** @var ProductRepository */
    protected $_productRepo;

    /** @var \Magento\Catalog\Model\Product */
    protected $_product;
    protected $_productId;
    protected $_categoryRepo;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Magento\ConfigurableProduct\Helper\Data $configHelper,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        array $data = []
    ) {
        $this->helper        = $helper;
        $this->bvLogger      = $logger;
        $this->_coreRegistry = $registry;
        $this->configHelper  = $configHelper;
        $this->_productRepo  = $productRepository;
        $this->_categoryRepo = $categoryRepository;
        parent::__construct( $context, $data );
    }

    public function getHelper() {
        return $this->helper;
    }

    public function getConfig( $path ) {
        return $this->helper->getConfig( $path );
    }

    public function isEnabled() {
        return $this->getConfig( 'general/enable_bv' );
    }

    public function canShow( $type ) {
        return $this->isEnabled() && $this->getConfig( $type . '/enable_' . $type );
    }

    /**
     * Get current product id
     *
     * @return null|int
     */
    public function getProductId() {
        if ( $this->getProduct() ) {
            return $this->getProduct()->getId();
        }

        return null;
    }

    public function getContainerUrl() {
        return $this->_storeManager->getStore()->getBaseUrl() . 'bazaarvoice/submission/container';
    }

    /**
     * @return string
     */
    public function getProductSku() {
        if ( $this->getProduct() ) {
            return $this->helper->getProductId( $this->getProduct()->getSku() );
        }

        return null;
    }

    /**
     * Get product object from core registry object
     *
     * @return bool|\Magento\Catalog\Model\Product
     */
    public function getProduct() {
        if ( empty( $this->_product ) ) {
            try {
                $product = $this->_coreRegistry->registry( 'product' );
                if ( $product == null ) {
                    throw new NoSuchEntityException();
                }
                $this->_product = $this->_productRepo->getById( $product->getId() );
            } Catch ( \Exception $e ) {
                $this->bvLogger->crit( $e->getMessage() . "\n" . $e->getTraceAsString() );

                return false;
            }
        }

        return $this->_product;
    }

    /**
     * @return Boolean
     */
    public function isConfigurable() {
        try {

            if ( $this->getProductId() && $this->getConfig( 'rr/children' ) ) {
                return $this->getProduct()->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
            }
        } Catch ( \Exception $e ) {
            $this->bvLogger->crit( $e->getMessage() . "\n" . $e->getTraceAsString() );
        }

        return false;
    }

    /**
     * @return String
     */
    public function getChildrenJson() {
        $children = array();
        if ( $this->isConfigurable() && $this->getConfig( 'rr/children' ) ) {
            $product = $this->getProduct();

            /** @var Configurable $typeInstance */
            $typeInstance    = $product->getTypeInstance();
            $childProducts   = $typeInstance->getUsedProductCollection( $product );
            $allowAttributes = $typeInstance->getConfigurableAttributes( $product );

            /** @var \Magento\Catalog\Model\Product $childProduct */
            foreach ( $childProducts as $childProduct ) {
                $key = '';
                foreach ( $allowAttributes as $attribute ) {
                    $productAttribute   = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue     = $childProduct->getData( $productAttribute->getAttributeCode() );

                    $key .= $productAttributeId . '_' . $attributeValue . '_';
                }
                $children[ $key ] = $this->helper->getProductId( $childProduct );
            }

        }
        $this->bvLogger->info( $children );

        return json_encode( $children, JSON_UNESCAPED_UNICODE );
    }

    /**
     * @return string
     */
    public function getBvConfigData() {
        $product    = $this->getProduct();
        $parentData = $this->getBvConfigProduct( $product );
        if (
            $product->getTypeId() == Configurable::TYPE_CODE
            && $this->getConfig( 'general/families' )
        ) {
            $family                 = $this->helper->getProductId( $product );
            //$family                 = [[ 'id' => $family, 'expand' => true, 'members' => [ $family ] ]];
            $parentData['family'] = $family;
            $productData            = [ $parentData ];
            $gtinData               = $this->getGTIN( $parentData );
            $children               = $product->getTypeInstance()->getUsedProducts( $product );
            foreach ( $children as $child ) {
                $childData = $this->getBvConfigProduct( $child );
                if ( ! empty( $parentData['brandName'] ) ) {
                    $childData['brandName'] = $parentData['brandName'];
                }
                $childData['family'] = $family;
                $gtinData              = $this->getGTIN( $childData, $gtinData );
                $productData[]         = $childData;
            }
            foreach ( $productData as $key => $productItem ) {
                foreach ( $this->_customAttributes as $attribute ) {
                    if ( ! empty( $gtinData[ $attribute . 's' ] ) ) {
                        $productItem[ $attribute . 's' ] = $gtinData[ $attribute . 's' ];
                    }
                }
                $productData[ $key ] = $productItem;
            }
        } else {
            $productData = [ $parentData ];
        }

        return json_encode( $productData, JSON_UNESCAPED_UNICODE );
    }

    protected function getGTIN( $data, $currentData = [] ) {
        $gtin = [];
        foreach ( $this->_customAttributes as $attribute ) {
            $values = ! empty( $data[ $attribute . 's' ] ) ? $data[ $attribute . 's' ] : [];
            if ( ! empty( $currentData[ $attribute . 's' ] ) ) {
                if ( ! empty( $values ) ) {
                    $gtin[ $attribute . 's' ] = array_merge( $currentData[ $attribute . 's' ], $values );
                } else {
                    $gtin[ $attribute . 's' ] = $currentData[ $attribute . 's' ];
                }
            } else {
                if ( ! empty( $values ) ) {
                    $gtin[ $attribute . 's' ] = $values;
                }
            }
        }

        return $gtin;
    }

    public function getBvConfigProduct( $product ) {
        $brandAttr = $this->helper->getConfig( 'feeds/brand_code' );
        $data      = [
            "productId"      => $this->helper->getProductId( $product ),
            "productName"    => $product->getName(),
            "productImageURL"       => $this->getUrl( 'pub/media/catalog' ) . 'product' . $product->getImage(),
            "productPageURL" => $product->getProductUrl()
        ];

        if ( $brand = $product->getAttributeText( $brandAttr ) ) {
            $data['brandName'] = $brand;
        }

        if ( $product->getData( 'bv_category_external_id' ) ) {
            $categoryId = $product->getData( 'bv_category_external_id' );
        } else {
            $categoryIds = $product->getCategoryIds();
            $categoryId  = array_pop( $categoryIds );
        }

        if ( $categoryId ) {
            $category     = $this->_categoryRepo->get( $categoryId );
            $categoryTree = $category->getPath();
            $categoryTree = explode( '/', $categoryTree );
            array_shift( $categoryTree );
            foreach ( $categoryTree as $key => $treeId ) {
                $parent               = $this->_categoryRepo->get( $treeId );
                $categoryTree[ $key ] = $parent->getName();
            }
            $data['categoryPath'] = $categoryTree;
        }

        foreach ( $this->_customAttributes as $customAttribute ) {
            $code = strtolower( $customAttribute );
            $attr = $this->helper->getConfig( "feeds/{$code}_code" );
            if ( $attr ) {
                $value = $product->getAttributeText( $attr );
                if ( empty( $value ) ) {
                    $value = $product->getData( $attr );
                }
                if ( ! empty( $value ) ) {
                    if ( is_string( $value ) && strpos( $value, ',' ) ) {
                        $value = $this->helper->explodeAndTrim( ',', $value );
                    } else {
                        $value = [ $value ];
                    }
                    $data[ $customAttribute . 's' ] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Add checking product before render block HTML
     *
     * @return string
     */
    protected function _toHtml() {
        if ( $this->getProduct() ) {
            return parent::_toHtml();
        }

        return '';
    }
}
