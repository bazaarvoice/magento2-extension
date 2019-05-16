<?php

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Helper\Seosdk;
use Bazaarvoice\Connector\Logger\Logger;
use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Class SeoContent
 *
 * @package Bazaarvoice\Connector\Model
 */
class SeoContent
{
    /**
     * @var
     */
    private $seoSdk;
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    private $logger;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var \Bazaarvoice\Connector\Model\CurrentProductProvider
     */
    private $currentProductProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $requestInterface;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlInterface;

    /**
     * SeoContent constructor.
     *
     * @param \Bazaarvoice\Connector\Logger\Logger                $logger
     * @param ConfigProviderInterface                             $configProvider
     * @param StringFormatterInterface                            $stringFormatter
     * @param \Bazaarvoice\Connector\Model\CurrentProductProvider $currentProductProvider
     * @param \Magento\Framework\App\RequestInterface             $requestInterface
     * @param \Magento\Framework\UrlInterface                     $urlInterface
     */
    public function __construct(
        Logger $logger,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        CurrentProductProvider $currentProductProvider,
        RequestInterface $requestInterface,
        UrlInterface $urlInterface
    ) {
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->currentProductProvider = $currentProductProvider;
        $this->stringFormatter = $stringFormatter;
        $this->requestInterface = $requestInterface;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @return string
     */
    public function getQaSeoContent()
    {
        $bv = $this->getSeosdk();
        if ($bv) {
            $seoContent = $bv->questions->getContent();
            $seoContent .= $this->getDebugSeoParams();
            return $seoContent;
        }
        return '';
    }

    /**
     * @return string
     */
    public function getAggregateSeoContent()
    {
        $bv = $this->getSeosdk();
        if ($bv) {
            $seoContent = $bv->reviews->getAggregateRating();
            $seoContent .= $this->getDebugSeoParams();
            return $seoContent;
        }
        return '';
    }

    /**
     * @return false|string
     */
    public function getRrSeoContent()
    {
        $bv = $this->getSeosdk();
        if ($bv) {
            $seoContent = $bv->reviews->getReviews();
            $seoContent .= $this->getDebugSeoParams();
            return $seoContent;
        }
        return '';
    }

    /**
     * @return \Bazaarvoice\Connector\Helper\Seosdk
     */
    private function getSeosdk()
    {
        $this->logger->debug(__CLASS__ . ' getSEOContent');
        if ($this->configProvider->isCloudSeoEnabled() && !$this->seoSdk) {
            try {
                $params = $this->getParams();
                $this->seoSdk = new Seosdk($params);
            } catch (Exception $e) {
                $this->logger->critical('Could not initialize Seo SDK with error: ' . $e->getTraceAsString());
            }
        }

        return $this->seoSdk;
    }

    /**
     * @return string
     */
    private function getDebugSeoParams()
    {
        if ($this->configProvider->getEnvironment() == 'staging') {
            $params = $this->getParams();
            return '<!-- BV Reviews SEO Parameters: ' . json_encode($params) . '-->';
        }

        return '';
    }

    /**
     * Get BV SEO params from admin
     *
     * @return array
     */
    private function getParams()
    {
        /** Check if admin has configured a legacy display code */
        if ($this->configProvider->getLegacyDisplayCode()) {
            $deploymentZoneId
                = $this->configProvider->getLegacyDisplayCode().
                '-'.$this->configProvider->getLocale();
        } else {
            $deploymentZoneId
                = str_replace(' ', '_', $this->configProvider->getDeploymentZone()).
                '-'.$this->configProvider->getLocale();
        }

        $product = $this->currentProductProvider->getProduct();

        $productUrl = $this->urlInterface->getCurrentUrl();
        $parts = parse_url($productUrl);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['bvrrp']);
            unset($query['bvstate']);
            $baseUrl = $parts['scheme'].'://'.$parts['host'].$parts['path'].'?'.http_build_query($query);
        } else {
            $baseUrl = $productUrl;
        }

        $params = [
            'seo_sdk_enabled' => true,
            'bv_root_folder'  => $deploymentZoneId,
            /** replace with your display code (BV provided) */
            'subject_id'      => $this->stringFormatter->getFormattedProductSku($product),
            /** replace with product id */
            'cloud_key'       => $this->configProvider->getCloudSeoKey(),
            /** BV provided value */
            'base_url'        => $baseUrl,
            'page_url'        => $productUrl,
            'staging'         => ($this->configProvider->getEnvironment() == 'staging' ? true : false),
        ];

        $this->logger->debug('SEO Params: '.print_r($params, $return = true));

        if ($this->requestInterface->getParam('bvreveal') == 'debug') {
            $params['bvreveal'] = 'debug';
        }

        return $params;
    }
}
