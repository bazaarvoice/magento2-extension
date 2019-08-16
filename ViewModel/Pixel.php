<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\ViewModel;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Model\Source\Environment;
use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Region;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Pixel
 *
 * @package Bazaarvoice\Connector\ViewModel
 */
class Pixel implements ArgumentInterface
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;
    /**
     * @var \Magento\Directory\Model\Region
     */
    private $region;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;
    /**
     * @var
     */
    private $orderDetails;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;

    /**
     * Pixel constructor.
     *
     * @param ConfigProviderInterface                  $configProvider
     * @param StringFormatterInterface                 $stringFormatter
     * @param \Magento\Catalog\Helper\Image            $imageHelper
     * @param \Magento\Checkout\Model\Session          $checkoutSession
     * @param \Magento\Directory\Model\Region          $region
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        Image $imageHelper,
        Session $checkoutSession,
        Region $region,
        ProductRepository $productRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->imageHelper = $imageHelper;
        $this->region = $region;
        $this->productRepository = $productRepository;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
    }

    /**
     * @return mixed
     */
    public function getBvPixelEnabled()
    {
        return $this->configProvider->isBvPixelEnabled();
    }

    /**
     * @return false|string
     */
    public function getJsonOrderDetails()
    {
        return json_encode($this->getOrderDetails(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed
     */
    public function getOrderDetails()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->checkoutSession->getLastRealOrder();

        if ($this->orderDetails) {
            return $this->orderDetails;
        }

        $address = $order->getBillingAddress();

        $this->orderDetails['currency'] = $order->getOrderCurrencyCode();
        $this->orderDetails['orderId'] = $order->getIncrementId();

        $total = $order->getGrandTotal() - $order->getTaxAmount() - $order->getShippingAmount();
        $this->orderDetails['total'] = number_format((float)$total, 2, '.', '');
        $this->orderDetails['tax'] = number_format((float)$order->getTaxAmount() ?? 0.0, 2, '.', '');
        $this->orderDetails['shipping'] = number_format((float)$order->getShippingAmount() ?? 0.0, 2, '.', '');
        $this->orderDetails['discount'] = number_format((float)abs($order->getDiscountAmount()) ?? 0.0, 2, '.', '');

        if ($address) {
            $this->orderDetails['city'] = $address->getCity();
            $this->orderDetails['state'] = $this->region->load($address->getRegionId())->getCode();
            $this->orderDetails['country'] = $address->getCountryId();
        }

        $this->orderDetails['items'] = [];
        /** if families are enabled, get all items */
        if ($this->configProvider->isFamiliesEnabled()) {
            $items = $order->getAllItems();
        } else {
            $items = $order->getAllVisibleItems();
        }
        foreach ($items as $itemId => $item) {
            try {
                $product = $this->getReviewableProductFromOrderItem($item);
            } catch (NoSuchEntityException $e) {
                continue;
            }
            /** skip configurable items if families are enabled */
            if ($this->configProvider->isFamiliesEnabled()
                && $product->getTypeId() == Configurable::TYPE_CODE) {
                continue;
            }

            $itemDetails = [];
            $itemDetails['productId'] = $this->stringFormatter->getFormattedProductSku($product);

            $itemDetails['name'] = $item->getName();
            /** 'category' is not included.
             * Mage products can be in 0 - many categories.
             * Should we try to include it?
             */
            $itemDetails['price'] = number_format((float)$item->getPrice(), 2, '.', '');
            $itemDetails['discount'] = number_format((float)$item->getDiscountAmount(), 2, '.', '');
            $itemDetails['quantity'] = number_format((float)$item->getQtyOrdered(), 0);
            $itemDetails['imageURL'] = $this->imageHelper->init($product, 'product_small_image')
                ->setImageFile($product->getSmallImage())->getUrl();

            if ($this->configProvider->isFamiliesEnabled() && $item->getParentItem()) {
                if (strpos($itemDetails['imageURL'], 'placeholder/image.jpg') !== false) {
                    /** if product families are enabled and product has no image, use configurable image */
                    $parentId = $item->getParentItem()->getProductId();
                    try {
                        $parent = $this->productRepository->getById($parentId);
                        $itemDetails['imageURL'] = $this->imageHelper->init($parent, 'product_small_image')
                            ->setImageFile($parent->getSmallImage())->getUrl();
                    } catch (NoSuchEntityException $e) {
                    }
                }
                /** also get price from parent item */
                $itemDetails['price'] = number_format((float)$item->getParentItem()->getPrice(), 2, '.', '');
            }

            array_push($this->orderDetails['items'], $itemDetails);
        }
        if ($order->getCustomerId()) {
            $userId = $order->getCustomerId();
        } elseif ($order->getCustomerEmail()) {
            $userId = md5($order->getCustomerEmail());
        }
        if (!empty($userId)) {
            $this->orderDetails['userId'] = $userId;
        }
        $this->orderDetails['email'] = $order->getCustomerEmail();
        $this->orderDetails['nickname'] = $order->getCustomerFirstname()
            ? $order->getCustomerFirstname()
            : $order->getBillingAddress() ? $order->getBillingAddress()->getFirstname() : '';
        /** There is no 'deliveryDate' yet */
        $this->orderDetails['locale'] = $this->configProvider->getLocale($order->getStoreId());

        /** Add partnerSource field */
        $this->orderDetails['source'] = 'Magento_BV_Extension';
        $this->orderDetails['partnerVersion'] = $this->configProvider->getExtensionVersion();
        $this->orderDetails['partnerSource'] = 'Magento Extension r'.$this->configProvider->getExtensionVersion();
        $this->orderDetails['deploymentZone'] = strtolower(str_replace(
            ' ',
            '_',
            $this->configProvider->getDeploymentZone()
        ));

        return $this->orderDetails;
    }

    /**
     * Returns the product unless the product visibility is
     * set to not visible.  In this case, it will try and pull
     * the parent/associated product from the order item.
     *
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return \Magento\Catalog\Model\Product
     * @throws NoSuchEntityException
     */
    public function getReviewableProductFromOrderItem($item)
    {
        $product = $this->productRepository->getById($item->getProductId());
        $product->setStoreId($item->getStoreId());

        if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
            $options = $item->getProductOptions();
            if (isset($options['super_product_config']['product_id'])) {
                try {
                    $parentId = $options['super_product_config']['product_id'];
                    $product = $this->productRepository->getById($parentId);
                } catch (Exception $ex) {
                }
            }
        }

        return $product;
    }

    /**
     * @return bool
     */
    public function canShowDebugDetails()
    {
        return ($this->configProvider->getEnvironment() == Environment::STAGING);
    }

    /**
     * @return mixed
     */
    public function getDebugDetails()
    {
        return print_r($this->getOrderDetails(), true);
    }
}
