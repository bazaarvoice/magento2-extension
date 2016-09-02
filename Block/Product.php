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
/**
 * Class Product
 * @package Bazaarvoice\Connector\Block
 */
class Product extends \Magento\Framework\View\Element\Template
{
    /* @var \Magento\Framework\Registry */
    protected $_coreRegistry;

    /* @var \Bazaarvoice\Connector\Helper\Data */
    public $helper;

    /* @var \Bazaarvoice\Connector\Logger\Logger */
    public $logger;

    /** @var  \Magento\Framework\ObjectManagerInterface */
    public $objectManager;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Bazaarvoice\Connector\Logger\Logger $logger,
        array $data = [])
    {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_coreRegistry = $registry;
        $this->objectManager = $objectManager;
        $this->logger->debug(__CLASS__.' construct');
        parent::__construct($context, $data);
    }

    public function getHelper()
    {
        return $this->helper;
    }

    public function getConfig($path)
    {
        return $this->helper->getConfig($path);
    }

    public function isEnabled()
    {
        return $this->getConfig('general/enable_bv') == 1;
    }

    /**
     * Get current product id
     *
     * @return null|int
     */
    public function getProductId()
    {
        $product = $this->_coreRegistry->registry('product');
        return $product ? $product->getId() : null;
    }

    public function getContainerUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . 'bazaarvoice/submission/container';
    }

    public function getProductSku()
    {
        if ($this->getProductId())
            return $this->helper->getProductId($this->getProductId());
        return '';
    }

    /**
     * @return bool|\Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        $this->logger->debug(__CLASS__.' get product');
        if (is_numeric($this->getProductId())) {
            $product = $this->objectManager->get('Magento\Catalog\Model\Product')->load($this->getProductId());
            return $product;
        }
        return false;
    }

    /**
     * @return Boolean
     */
    public function isConfigurable()
    {
        if ($this->getProduct()) {
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
        if ($this->isConfigurable()) {
            $product = $this->getProduct();

            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
            $options = $this->objectManager->get('\Magento\ConfigurableProduct\Helper\Data')->getOptions($product, $childProducts);

            /** @var \Magento\Catalog\Model\Product $childProduct */
            foreach ($childProducts as $childProduct) {
                $attributeValues = $options['index'][$childProduct->getId()];
                $attributeValue = '';
                foreach ($attributeValues as $key => $value)
                    $attributeValue .= $key . '_' . $value . '_';

                $children[$attributeValue] = $this->helper->getProductId($childProduct);
            }

        }
        return json_encode($children, JSON_UNESCAPED_UNICODE);
    }


}