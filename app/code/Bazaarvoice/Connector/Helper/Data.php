<?php
namespace Bazaarvoice\Connector\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public function getConfig($config_path, $store = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->getDefaultConfig('bazaarvoice/'.$config_path, $store, $scope);
    }

    public function getDefaultConfig($config_path, $store = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        $value = $this->scopeConfig->getValue($config_path, $scope, $store);
        if($value == '') {
            if($config_path == 'bazaarvoice/feeds/product_filename')
                $value = 'productfeed.xml';
            elseif($config_path == 'bazaarvoice/feeds/product_path')
                $value = '/import-inbox';
        }
        return $value;
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
            $apiHostname =  'display.ugc.bazaarvoice.com/bvstaging';
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
        $deploymentZoneName = $this->getConfig('general/deployment_zone', $store);
        // Get locale code from BV config, 
        // Note that this doesn't use Magento's locale, this will allow clients to override this and map it as they see fit
        $localeCode = $this->getConfig('general/locale', $store);
        // Build url string
        $url = $protocol . '://' . $apiHostname . '/' . $static . $clientName . '/' . urlencode($deploymentZoneName) . '/' . $localeCode;
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

        if(is_object($product))
            $rawProductId = $product->getSku();
        else
            $rawProductId = $product;

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


    /**
     * Returns the product unless the product visibility is
     * set to not visible.  In this case, it will try and pull
     * the parent/associated product from the order item.
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return \Magento\Catalog\Model\Product
     */
    public function getReviewableProductFromOrderItem($item)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product');
        $product->setStoreId($item->getStoreId());
        $product->load($item->getProductId());

        if ($product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
        {
            $options = $item->getProductOptions();
            if(isset($options['super_product_config']['product_id'])){
                try
                {
                    $parentId = $options['super_product_config']['product_id'];
                    $product = $objectManager->get('Magento\Catalog\Model\Product');
                    $product = $product->load($parentId);
                }
                catch (\Magento\Framework\Exception $ex) {}
            }
        }

        return $product;
    }
    
    public function getExtensionVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Module\ModuleResource $module */
        $module = $objectManager->get('\Magento\Framework\Module\ModuleResource');
        $data = $module->getDataVersion('Bazaarvoice_Connector');
        return print_r($data, 1);
    }
    
}