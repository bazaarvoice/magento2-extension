<?php

namespace Bazaarvoice\Connector\Block;

use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Product
 * @package Bazaarvoice\Connector\Block
 */
class Product extends \Magento\Framework\View\Element\Template {
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


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Magento\ConfigurableProduct\Helper\Data $configHelper,
        ProductRepository $productRepository,
        array $data = []
    ) {
        $this->helper        = $helper;
        $this->bvLogger      = $logger;
        $this->_coreRegistry = $registry;
        $this->configHelper  = $configHelper;
        $this->_productRepo  = $productRepository;
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
                $product        = $this->_coreRegistry->registry( 'product' );
                if($product == null)
                    throw new NoSuchEntityException();
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
                $key       = '';
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
     * Add checking product before render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getProduct()) {
            return parent::_toHtml();
        }

        return '';
    }
}