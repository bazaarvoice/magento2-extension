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

use Bazaarvoice\Connector\Model\Feed\Feed;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Store\Model\Store;

class Product extends Feed
{
    protected $productHelper;
    
    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Helper\Product $catalogProductHelper
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Helper\Product $catalogProductHelper
    ) {
        parent::__construct($logger, $helper, $objectManager);
        $this->productHelper = $catalogProductHelper;
    }


    /**
     * @param XMLWriter $writer
     * @param $store
     */
    public function processProductsForStore(XMLWriter $writer, Store $store)
    {
        $writer->startElement('Products');
        $productFactory = $this->objectManager->get('\Magento\Catalog\Model\ProductFactory');

        /* @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $productFactory->create()->getCollection();
        $productCollection
            ->setStore($store)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('brand')
            ->addAttributeToSelect('category_ids')
            ->addAttributeToSelect('image');

        $i = 0;
        foreach($productCollection as $product) {
            if($i++ > 10) break;
            /* @var $product \Magento\Catalog\Model\Product */
            
            $writer->startElement('Product');
            
            $writer->writeElement('ExternalId', $this->helper->getProductId($product));
            $writer->writeElement('Name', $product->getName(), true);
            $writer->writeElement('Description', $product->getData('description'), true);

            // !TODO Brands
            $writer->writeElement('BrandExternalId', $product->getData('brand'));

            // !TODO Categories
            $writer->writeElement('CategoryExternalId', $product->getData('category_ids'));

            $writer->writeElement('ProductPageUrl', $this->productHelper->getProductUrl($product), true);

            // !TODO Localized Image Url
            $writer->writeElement('ImageUrl',
                $store->getUrl() .
                'pub/media/catalog/product' .
                $product->getImage());

            // !TODO extra attributes
            foreach(array('ManufacturerPartNumber', 'EAN', 'UPC') as $code) {
                $writer->startElement($code.'s');
                $writer->writeElement($code, $product->getData($code));
                $writer->endElement();
            }

            // !TODO Families

            $writer->endElement(); // Product    
        }
        
        $writer->endElement(); // Products
        
    }



}