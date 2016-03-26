<?php
namespace Bazaarvoice\Connector\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public function getConfig($config_path, $store = null)
    {
        return $this->scopeConfig->getValue(
            'bazaarvoice/'.$config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get url to bvapi.js javascript API file
     *
     * C2013 staging call:
     * ----------------------
     * <code>
     *   src="//display-stg.ugc.bazaarvoice.com/static/{{ClientName}}/{{DeploymentZoneName}}/{{Locale}}/bvapi.js"
     * </code>
     *
     * @static
     * @param  $isStatic
     * @return string
     */
    public function getBvApiHostUrl($isStatic, $store = null)
    {
        // Build protocol based on current page
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '') ? 'https' : 'http';
        // Build hostname based on environment setting
        $environment = $this->getConfig('general/environment', $store);
        if ($environment == 'staging') {
            $apiHostname =  'display-stg.ugc.bazaarvoice.com';
        }
        else {
            $apiHostname =  'display.ugc.bazaarvoice.com';
        }
        // Build static dir name based on param
        if($isStatic) {
            $static = 'static/';
        }
        else {
            $static = '';
        }
        // Lookup other config settings
        $clientName = $this->getConfig('general/client_name', $store);
        $deploymnetZoneName = $this->getConfig('general/deployment_zone', $store);
        // Get locale code from BV config, 
        // Note that this doesn't use Magento's locale, this will allow clients to override this and map it as they see fit
        $localeCode = $this->getConfig('general/locale', $store);
        // Build url string
        $url = $protocol . '://' . $apiHostname . '/' . $static . $clientName . '/' . urlencode($deploymnetZoneName) . '/' . $localeCode;
        // Return final url
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
        if(is_numeric($product)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->get('Magento\Catalog\Model\Product')->load($product);    
        }
        
        $rawProductId = $product->getSku();

        // >> Customizations go here
        $rawProductId = preg_replace_callback('/\./s', create_function('$match','return "_bv".ord($match[0])."_";'), $rawProductId);        
        // << No further customizations after this
        
        return $this->replaceIllegalCharacters($rawProductId);
    }
    
    /**
     * This unique ID can only contain alphanumeric characters (letters and numbers
     * only) and also the asterisk, hyphen, period, and underscore characters. If your
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
        // We need to use a reversible replacement so that we can reconstruct the original ID later.
        // Example rawId = qwerty$%@#asdf
        // Example encoded = qwerty_bv36__bv37__bv64__bv35_asdf

        return preg_replace_callback('/[^\w\d\*-\._]/s', create_function('$match','return "_bv".ord($match[0])."_";'), $rawId);
    }    
    
}