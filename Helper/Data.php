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
namespace Bazaarvoice\Connector\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\Store;

class Data extends AbstractHelper
{
    public function getConfig($configPath, $store = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->getDefaultConfig('bazaarvoice/'.$configPath, $store, $scope);
    }

    public function getDefaultConfig($configPath, $store = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        $value = $this->scopeConfig->getValue($configPath, $scope, $store);
        return $value;
    }

    public function canSendFeed($storeId)
    {
    	return $this->getConfig('general/enable_bv', $storeId) == true
	           && $this->getConfig('feeds/enable_product_feed', $storeId) == true;
    }

    /**
     * Get url to bv.js javascript API file
     *
     * C2013 staging call:
     * ----------------------
     * <code>
     *   src="//apps.bazaarvoice.com/deployments/{{ClientName}}/{{DeploymentZoneName}}/{{Environment}}/{{Locale}}/bv.js"
     * </code>
     *
     * @static
     * @param $isStatic
     * @param null|Store $store
     * @return string
     */
    public function getBvApiHostUrl($isStatic, $store = null)
    {
	    $url = '//apps.bazaarvoice.com/deployments/'
	              . $this->getConfig('general/client_name')
	              . '/' . strtolower(str_replace(' ', '_', $this->getConfig('general/deployment_zone')))
	              . '/' . $this->getConfig('general/environment')
	              . '/' . $this->getConfig('general/locale')
	              . '/bv.js';
	    
        return $url;
    }
    
    /**
     * Get the uniquely identifying product ID for a catalog product.
     *
     * This is the unique, product family-level id (duplicates are unacceptable).
     * If a product has its own page, this is its product ID. It is not necessarily
     * the SKU ID, as we do not collect separate Ratings & Reviews for different
     * styles of product - i.e. the 'Blue' vs. 'Red Widget'.
     *
     * @param  mixed $product a reference to a catalog product object
     * @return string The unique product ID to be used with Bazaarvoice
     */
    public function getProductId($product)
    {
        /**
         * Disabling this code which allowed getProductId to accept a
         * product ID instead of sku.
         * It causes a bug with numeric skus.
        if (is_numeric($product)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->get('Magento\Catalog\Model\Product')->load($product);
        }
         * */

        if (is_object($product))
            $rawProductId = $product->getSku();
        else
            $rawProductId = $product;

        /** Customizations go here */
        $rawProductId = preg_replace_callback('/\./s', function($match) {return "_bv".ord($match[0])."_";}, $rawProductId);
        /** No further customizations after this */

        return $this->replaceIllegalCharacters($rawProductId);
    }
    
    /**
     * This unique ID can only contain alphanumeric characters (letters and numbers
     * only) and also the asterisk, hyphen, and underscore characters. If your
     * product IDs contain invalid characters, simply replace them with an alternate
     * character like an underscore. This will only be used in the feed and not for
     * any customer facing purpose.
     *
     * @static
     * @param string $rawId
     * @return mixed
     */
    public function replaceIllegalCharacters($rawId)
    {
        /**
         * We need to use a reversible replacement so that we can reconstruct the original ID later.
         * Example rawId = qwerty$%@#asdf
         * Example encoded = qwerty_bv36__bv37__bv64__bv35_asdf
         */

        return preg_replace_callback('/[^\w\d\*\-_]/s', function($match) {return "_bv".ord($match[0])."_";}, $rawId);
    }
    
    public function getExtensionVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Module\ModuleResource $module */
        $module = $objectManager->get('\Magento\Framework\Module\ModuleResource');
        $data = $module->getDataVersion('Bazaarvoice_Connector');
        return print_r($data, 1);
    }

    /**
     * @param $value
     * @return string
     */
    public function jsonEncode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * json decode, sends original data if error
     * @param $value
     * @return mixed
     */
    public function jsonDecode($value)
    {
        $result = json_decode($value, true);
        if (json_last_error() != JSON_ERROR_NONE)
            return $value;
        return $result;
    }
    
}
