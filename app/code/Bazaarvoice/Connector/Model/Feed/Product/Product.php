<?php
namespace Bazaarvoice\Connector\Model\Feed\Product;
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license 
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */
 
use Magento\Catalog\Model\ProductFactory; 

class Product extends \Bazaarvoice\Connector\Model\Feed\Feed
{
    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
    }
    
    public function processProductsForStore($writer, $store)
    {
        $writer->startElement('Products');
        
        $productCollection = $this->productFactory->create()->getCollection();
        foreach($productCollection as $product) {
            
            $product->load($product->getId());
            
            $writer->startElement('Product');
            
            $writer->writeElement('ExternalId', $this->helper->getProductId($product));
            $writer->writeElement('Name', $product->getName());
            
            $writer->endElement(); // Product    
        }
        
        $writer->endElement(); // Products
        
    }



}