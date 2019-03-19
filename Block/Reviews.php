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

use Bazaarvoice\Connector\Helper\Seosdk;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;

class Reviews extends Product
{
    protected $_requestInterface;
    protected $_urlInterface;

    /**
     * Reviews constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Magento\ConfigurableProduct\Helper\Data $configHelper
     * @param ProductRepository $productRepository
     * @param array $data
     */
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
        $this->_urlInterface = $context->getUrlBuilder();
        $this->_requestInterface = $context->getRequest();
        parent::__construct( $context, $registry, $helper, $logger, $configHelper, $productRepository, $categoryRepository, $data );
    }



    /**
     * Get only aggregate data
     * @return string
     * @throws \Exception
     */
    public function getAggregateSEOContent()
    {
        $this->bvLogger->debug( __CLASS__ . ' getAggregateSEOContent');
        if ($this->getIsEnabled()) {
            $params = $this->_getParams();
            $bv = new Seosdk($params);
            $seoContent = $bv->reviews->getAggregateRating();
            return $seoContent;
        }
        return '';
    }

    /**
     * Get complete BV SEO Content
     * @return string
     * @throws \Exception
     */
    public function getSEOContent()
    {
        $this->bvLogger->debug( __CLASS__ . ' getSEOContent');
        if ($this->getIsEnabled()) {
            $params = $this->_getParams();
            $bv = new Seosdk($params);
            $seoContent = $bv->reviews->getReviews();
            if($this->getConfig('general/environment') == 'staging')
                $seoContent .= '<!-- BV Reviews SEO Parameters: ' . json_encode($params) . '-->';
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
        /** Check if admin has configured a legacy display code */
        if (strlen($this->getConfig('bv_config/display_code'))) {
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

        $productUrl = $this->_urlInterface->getCurrentUrl();
        $parts = parse_url($productUrl);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['bvrrp']);
            unset($query['bvstate']);
            $baseUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . http_build_query($query);
        } else {
            $baseUrl = $productUrl;
        }

        $params = array(
            'seo_sdk_enabled' => TRUE,
            'bv_root_folder' => $deploymentZoneId, /** replace with your display code (BV provided) */
            'subject_id' => $this->getHelper()->getProductId($product), /** replace with product id */
            'cloud_key' => $this->getConfig('general/cloud_seo_key'), /** BV provided value */
            'base_url' => $baseUrl,
            'page_url' => $productUrl,
            'staging' => ($this->getConfig('general/environment') == 'staging' ? TRUE : FALSE)
        );

        $this->bvLogger->debug( 'SEO Params: ' . print_r($params, 1));

        if ($this->_requestInterface->getParam('bvreveal') == 'debug')
            $params['bvreveal'] = 'debug';

        return $params;

    }

    protected function getIsEnabled()
    {
        return $this->getConfig('general/enable_cloud_seo') && $this->isEnabled();
    }

}
