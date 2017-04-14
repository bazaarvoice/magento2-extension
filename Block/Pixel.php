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

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Pixel
{

    /**
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->objectManager = $objectManager;
    }

    /** @codingStandardsIgnoreStart */
    public function afterToHtml(
        /** @noinspection PhpUnusedParameterInspection */
        $subject,
        $result
    ) {
        /** @codingStandardsIgnoreEnd */
        if ($this->helper->getConfig('general/enable_bvpixel') != true) {
            return $result;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->checkoutSession->getLastRealOrder();

        $orderDetails = [];

        $address = $order->getBillingAddress();

        $orderDetails['orderId'] = $order->getIncrementId();
        $orderDetails['tax'] = number_format($order->getTaxAmount(), 2, '.', '');
        $orderDetails['shipping'] = number_format($order->getShippingAmount(), 2, '.', '');
        $orderDetails['total'] = number_format($order->getGrandTotal(), 2, '.', '');
        $orderDetails['city'] = $address->getCity();
        /** @var \Magento\Directory\Model\Region $region */
        $region = $this->objectManager->get('\Magento\Directory\Model\Region');
        $orderDetails['state'] = $region->load($address->getRegionId())->getCode();
        $orderDetails['country'] = $address->getCountryId();
        $orderDetails['currency'] = $order->getOrderCurrencyCode();

        $orderDetails['items'] = [];
        /** if families are enabled, get all items */
        if ($this->helper->getConfig('general/families')) {
            $items = $order->getAllItems();
        } else {
            $items = $order->getAllVisibleItems();
        }
        foreach ($items as $itemId => $item) {
            $product = $this->helper->getReviewableProductFromOrderItem($item);
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->objectManager->get('\Magento\Catalog\Model\Product')->load($product->getId());
            /** skip configurable items if families are enabled */
            if (
                $this->helper->getConfig('general/families')
                && $product->getTypeId() == Configurable::TYPE_CODE
            ) {
                continue;
            }

            $itemDetails = [];
            $itemDetails['sku'] = $this->helper->getProductId($product);
            $itemDetails['name'] = $item->getName();
            /** 'category' is not included.  Mage products can be in 0 - many categories.  Should we try to include it? */
            $itemDetails['price'] = number_format($item->getPrice(), 2, '.', '');
            $itemDetails['quantity'] = number_format($item->getQtyOrdered(), 0);
            $itemDetails['imageUrl'] = $product->getStore()->getUrl() . 'pub/media/catalog/product' . $product->getImage();

            if ($this->helper->getConfig('general/families') && $item->getParentItem()) {
                if (strpos($itemDetails['imageUrl'], 'placeholder/image.jpg')) {
                    /** if product families are enabled and product has no image, use configurable image */
                    $parentId = $item->getParentItem()->getProductId();
                    /** @var \Magento\Catalog\Model\Product $parent */
                    $parent = $this->objectManager->get('\Magento\Catalog\Model\Product')->load($parentId);
                    $itemDetails['imageUrl'] = $parent->getStore()->getUrl() . 'pub/media/catalog/product' . $parent->getImage();
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
        $orderDetails['nickname'] = $order->getCustomerFirstname();
        /** There is no 'deliveryDate' yet */
        $orderDetails['locale'] = $this->helper->getConfig('general/locale', $order->getStoreId());

        /** Add partnerSource field */
        $orderDetails['partnerSource'] = 'Magento Extension r' . $this->helper->getExtensionVersion();

        $result .= '
        <script type="text/javascript">
        $BV.SI.trackTransactionPageView(' . json_encode($orderDetails, JSON_UNESCAPED_UNICODE) . '); 
        </script>';

        return $result;
    }

}
