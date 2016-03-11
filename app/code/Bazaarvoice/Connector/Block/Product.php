<?php
    
namespace Bazaarvoice\Connector\Block;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license 
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package	    Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */
 
class Product extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Bazaarvoice\Connector\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->_coreRegistry = $registry;
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
    
    public function getProductSku()
    {
        if($this->getProductId())
            return $this->helper->getProductId($this->getProductId());
        return '';
    }
    
    public function getProduct()
    {
        if(is_numeric($this->getProductId())) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->get('Magento\Catalog\Model\Product')->load($this->getProductId());
            return $product;
        }
        return;
    }

}