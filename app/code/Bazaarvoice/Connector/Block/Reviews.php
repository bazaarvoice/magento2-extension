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

use Bazaarvoice\BV;

class Reviews extends Product
{
    /**
     * Get only aggregate data
     * @return string
     */
    public function getAggregateSEOContent()
    {
        if($this->getIsEnabled()){
            $params = $this->_getParams();
            $bv = new BV($params);
            $seoContent = $bv->reviews->getAggregateRating();
            $seoContent .= '
<!-- BV Aggregate Rating Parameters: ' . print_r($params, 1) . '-->';
            return $seoContent;
        }
        return '';
    }

    /**
     * Get complete BV SEO Content
     * @return string
     */
    public function getSEOContent()
    {
        if($this->getIsEnabled()){
            $params = $this->_getParams();
            $bv = new BV($params);
            $seoContent = $bv->reviews->getContent();
            $seoContent .= '
<!-- BV Reviews SEO Parameters: ' . print_r($params, 1) . '-->';
            return $seoContent;
        }
        return '';
    }

    /**
     * Get BV SEO params from admin
     * @return array
     */
    protected function _getParams()
    {
        // Check if admin has configured a legacy display code
        if(strlen($this->getConfig('bv_config/display_code'))) {
            $deploymentZoneId =
                $this->getConfig('bv_config/display_code') .
                '-' . $this->getConfig('general/locale');
        }
        else {
            $deploymentZoneId =
                str_replace(' ', '_', $this->getConfig('general/deployment_zone')) .
                '-' . $this->getConfig('general/locale');
        }

        $product = $this->getProduct();

        $urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $productUrl = $urlInterface->getCurrentUrl();
        $parts = parse_url($productUrl);
        if(isset($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['bvrrp']);
            $baseUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . http_build_query($query);
        } else {
            $baseUrl = $productUrl;
        }

        $this->logger->debug($baseUrl);

        $params = array(
            'seo_sdk_enabled' => TRUE,
            'bv_root_folder' => $deploymentZoneId, // replace with your display code (BV provided)
            'subject_id' => $this->getHelper()->getProductId($product), // replace with product id
            'cloud_key' => $this->getConfig('general/cloud_seo_key'), // BV provided value
            'base_url' => $baseUrl,
            'page_url' => $productUrl,
            'staging' => ($this->getConfig('general/environment') == "staging" ? TRUE : FALSE)
        );


        $requestInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\RequestInterface');
        if($requestInterface->getParam('bvreveal') == 'debug')
            $params['bvreveal'] = 'debug';

        return $params;

    }

    protected function getIsEnabled()
    {
        return $this->getConfig('general/enable_cloud_seo') && $this->isEnabled();
    }
    
}