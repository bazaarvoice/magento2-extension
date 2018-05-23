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
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Product
 * @package Bazaarvoice\Connector\Block
 */
class Product extends \Magento\Framework\View\Element\Template
{
    /* @var \Magento\Framework\Registry */
    protected $_coreRegistry;

    /* @var \Bazaarvoice\Connector\Helper\Data */
    public $_helper;

    /* @var \Bazaarvoice\Connector\Logger\Logger */
    public $_logger;

    /** @var  \Magento\ConfigurableProduct\Helper\Data */
    public $_configHelper;

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
        array $data = [])
    {
        $this->_helper       = $helper;
        $this->_logger       = $logger;
        $this->_coreRegistry = $registry;
        $this->_configHelper = $configHelper;
        $this->_productRepo  = $productRepository;
        parent::__construct($context, $data);
    }

    public function getHelper()
    {
        return $this->_helper;
    }

    public function getConfig($path)
    {
        return $this->_helper->getConfig($path);
    }

    public function isEnabled()
    {
        return $this->getConfig('general/enable_bv');
    }

    public function canShow($type)
    {
        return $this->isEnabled() && $this->getConfig($type.'/enable_'.$type);
    }

    /**
     * Get current product id
     *
     * @return null|int
     * @throws NoSuchEntityException
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    public function getContainerUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . 'bazaarvoice/submission/container';
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getProductSku()
    {
        return $this->_helper->getProductId($this->getProduct()->getSku());
    }

    /**
     * @return bool|\Magento\Catalog\Model\Product
     * @throws NoSuchEntityException
     */
    public function getProduct()
    {
        if(empty($this->_product)) {
            $product = $this->_coreRegistry->registry( 'product' );
            $this->_product = $this->_productRepo->getById($product->getId());
        }
        return $this->_product;
    }

    /**
     * @return Boolean
     */
    public function isConfigurable()
    {
        if ($this->getProductId() && $this->getConfig('rr/children')) {
            return $this->getProduct()->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
        }
        return false;
    }

    /**
     * @return String
     */
    public function getChildrenJson()
    {
        $children = array();
        if ($this->isConfigurable() && $this->getConfig('rr/children')) {
            $product = $this->getProduct();

            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
            $options = $this->_configHelper->getOptions($product, $childProducts);

            /** @var \Magento\Catalog\Model\Product $childProduct */
            foreach ($childProducts as $childProduct) {
                $attributeValues = $options['index'][$childProduct->getId()];
                $attributeValue = '';
                foreach ($attributeValues as $key => $value)
                    $attributeValue .= $key . '_' . $value . '_';

                $children[$attributeValue] = $this->_helper->getProductId($childProduct);
            }

        }
        return json_encode($children, JSON_UNESCAPED_UNICODE);
    }


}