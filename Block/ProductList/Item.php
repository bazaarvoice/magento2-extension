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

namespace Bazaarvoice\Connector\Block\ProductList;

class Item extends \Bazaarvoice\Connector\Block\Product
{
    /* @var \Magento\Catalog\Model\Product\Interceptor */
    protected $_product;
    protected $_productIds;

    protected $_type;

    public function isEnabled()
    {
        $typesEnabled = explode(',', $this->getConfig('rr/inline_ratings'));
        return in_array($this->_type, $typesEnabled);
    }

    // @codingStandardsIgnoreStart
    public function beforeGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        $subject, $product)
    {
        // @codingStandardsIgnoreEnd
        if ($this->isEnabled()) {
            $this->_product = $product;
        }
    }

    // @codingStandardsIgnoreStart
    public function afterGetProductPrice(
        /** @noinspection PhpUnusedParameterInspection */
        $subject, $result)
    {
        // @codingStandardsIgnoreEnd
        if ($this->isEnabled()) {
            $productIdentifier = $this->helper->getProductId($this->_product);
            $productUrl = $this->_product->getProductUrl();
            $result = '
            <div data-bv-show="inline_rating"
				 data-bv-product-id="' . $productIdentifier . '"
				 data-bv-redirect-url="' . $productUrl . '"></div>' . $result;

        }
        return $result;
    }

}
