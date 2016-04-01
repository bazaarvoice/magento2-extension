<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Block\ProductList;

class Item extends \Bazaarvoice\Connector\Block\Product
{
    /* @var \Magento\Catalog\Model\Product\Interceptor */
    protected $product;
    protected $productIds;

    protected $type;

    public function isEnabled() {
        $typesEnabled = explode(',', $this->getConfig('rr/inline_ratings'));
        return in_array($this->type, $typesEnabled);
    }

    public function beforeGetProductPrice($subject, $product)
    {
        if($this->isEnabled()) {
            $this->product = $product;
        }
    }

    public function afterGetProductPrice($subject, $result)
    {
        if($this->isEnabled()) {
            $productIdentifier = $this->helper->getProductId($this->product);
            $this->productIds[$productIdentifier] = array('url' => $this->product->getProductUrl());
            $result = '<div id="BVRRInlineRating_' . $this->type . '-' . $productIdentifier . '"></div>' . $result;
        }
        return $result;
    }

    public function afterToHtml($subject, $result)
    {
        if($this->isEnabled()) {
            $result .= '
            <script type="text/javascript">
            $BV.ui("rr", "inline_ratings", {
                productIds: ' . json_encode($this->productIds) . ',
                containerPrefix : "BVRRInlineRating_' . $this->type .'" 
            });
            </script>';
        }
        return $result;
    }
}