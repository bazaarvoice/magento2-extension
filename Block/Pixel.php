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

use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Pixel
{

    /**
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Directory\Model\Region $region
     * @param \Magento\Catalog\Model\ProductRepository $productRepo
     */
    public function __construct(
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Catalog\Helper\Image $imageHelper,
	    \Magento\Directory\Model\Region $region,
		\Magento\Catalog\Model\ProductRepository $productRepo
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->imageHelper = $imageHelper;
        $this->region = $region;
        $this->productRepo = $productRepo;
    }

    /** @codingStandardsIgnoreStart
     * @param \Magento\Checkout\Block\Onepage\Success $subject
     * @param $result
     * @return string
     */
    public function afterToHtml(/** @noinspection PhpUnusedParameterInspection */ $subject, $result)
    {
        /** @codingStandardsIgnoreEnd */
        if ($this->helper->getConfig('general/enable_bvpixel') != true)
            return $result;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->checkoutSession->getLastRealOrder();

        $orderDetails = array();

        $address = $order->getBillingAddress();

        $orderDetails['currency'] = $order->getOrderCurrencyCode();
        $orderDetails['orderId'] = $order->getIncrementId();

        $total = $order->getGrandTotal() - $order->getTaxAmount() - $order->getShippingAmount();
        $orderDetails['total'] = number_format($total, 2, '.', '');
        $orderDetails['tax'] = number_format($order->getTaxAmount(), 2, '.', '');
        $orderDetails['shipping'] = number_format($order->getShippingAmount(), 2, '.', '');

        $orderDetails['city'] = $address->getCity();
        $orderDetails['state'] = $this->region->load($address->getRegionId())->getCode();
        $orderDetails['country'] = $address->getCountryId();

        $orderDetails['items'] = array();
        /** if families are enabled, get all items */
        if ($this->helper->getConfig('general/families')) {
            $items = $order->getAllItems();
        } else {
            $items = $order->getAllVisibleItems();
        }
        foreach ($items as $itemId => $item) {
            $product = $this->helper->getReviewableProductFromOrderItem($item);
            $product = $this->productRepo->getById($product->getId());
            /** skip configurable items if families are enabled */
            if (
                $this->helper->getConfig('general/families')
                && $product->getTypeId() == Configurable::TYPE_CODE) continue;

            $itemDetails = array();
            $itemDetails['sku'] = $this->helper->getProductId($product);

            $itemDetails['name'] = $item->getName();
            /** 'category' is not included.  Mage products can be in 0 - many categories.  Should we try to include it? */
            $itemDetails['price'] = number_format($item->getPrice(), 2, '.', '');
            $itemDetails['quantity'] = number_format($item->getQtyOrdered(), 0);
            $itemDetails['imageURL'] = $this->imageHelper->init($product, 'product_page_image_small')->setImageFile($product->getImage())->getUrl();

            if ($this->helper->getConfig('general/families') && $item->getParentItem()) {
                if (strpos($itemDetails['imageURL'], 'placeholder/image.jpg')) {
                    /** if product families are enabled and product has no image, use configurable image */
                    $parentId = $item->getParentItem()->getProductId();
                    $parent = $this->productRepo->getById($parentId);
                    $itemDetails['imageURL'] = $this->imageHelper->init($parent, 'product_page_image_small')->setImageFile($parent->getImage())->getUrl();
                }
                /** also get price from parent item */
                $itemDetails['price'] = number_format($item->getParentItem()->getPrice(), 2, '.', '');
            }

            array_push($orderDetails['items'], $itemDetails);
        }
        if ($order->getCustomerId()) {
            $userId = $order->getCustomerId();
        } else {
            $userId = md5($order->getCustomerEmail());
        }
        $orderDetails['userId'] = $userId;
        $orderDetails['email'] = $order->getCustomerEmail();
        $orderDetails['nickname'] = $order->getCustomerFirstname()
	        ? $order->getCustomerFirstname()
            : $order->getBillingAddress()->getFirstname();
        /** There is no 'deliveryDate' yet */
        $orderDetails['locale'] = $this->helper->getConfig('general/locale', $order->getStoreId());

        /** Add partnerSource field */
        $orderDetails['partnerSource'] = 'Magento Extension r' . $this->helper->getExtensionVersion();
        $orderDetails['deploymentZone'] = $this->helper->getConfig('general/deployment_zone');

        $result = '
        <!--
        ' . print_r($orderDetails, 1) . '
        -->';
        $result .= '
        <script type="text/javascript">
            var transactionData = ' . json_encode($orderDetails, JSON_UNESCAPED_UNICODE) . ';
            BV.pixel.trackTransaction(transactionData);
        </script>';

        return $result;
    }

}