<?php

namespace Bazaarvoice\Connector\Block;

use Bazaarvoice\Connector\Helper\Data;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Region;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Pixel extends Template
{
    protected $helper;
    protected $checkoutSession;
    protected $imageHelper;
    protected $region;
    protected $productRepository;
    private $orderDetails;

    /**
     * Pixel constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Bazaarvoice\Connector\Helper\Data               $helper
     * @param \Magento\Catalog\Helper\Image                    $imageHelper
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Directory\Model\Region                  $region
     * @param \Magento\Catalog\Model\ProductRepository         $productRepo
     * @param array                                            $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Image $imageHelper,
        Session $checkoutSession,
        Region $region,
        ProductRepository $productRepo,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->imageHelper = $imageHelper;
        $this->region = $region;
        $this->productRepository = $productRepo;
        parent::__construct($context, $data);
    }

    public function getBvPixelEnabled()
    {
        return $this->helper->getConfig('general/enable_bvpixel');
    }

    public function getJsonOrderDetails()
    {
        return json_encode($this->getOrderDetails(), JSON_UNESCAPED_UNICODE);
    }

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
        $this->orderDetails['total'] = number_format($total, 2, '.', '');
        $this->orderDetails['tax'] = number_format($order->getTaxAmount(), 2, '.', '');
        $this->orderDetails['shipping'] = number_format($order->getShippingAmount(), 2, '.', '');

        $this->orderDetails['city'] = $address->getCity();
        $this->orderDetails['state'] = $this->region->load($address->getRegionId())->getCode();
        $this->orderDetails['country'] = $address->getCountryId();

        $this->orderDetails['items'] = array();
        /** if families are enabled, get all items */
        if ($this->helper->getConfig('general/families')) {
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
            if (
                $this->helper->getConfig('general/families')
                && $product->getTypeId() == Configurable::TYPE_CODE) {
                continue;
            }

            $itemDetails = array();
            $itemDetails['sku'] = $this->helper->getProductId($product);

            $itemDetails['name'] = $item->getName();
            /** 'category' is not included.  Mage products can be in 0 - many categories.  Should we try to include it? */
            $itemDetails['price'] = number_format($item->getPrice(), 2, '.', '');
            $itemDetails['quantity'] = number_format($item->getQtyOrdered(), 0);
            $itemDetails['imageURL'] = $this->imageHelper->init($product, 'product_small_image')
                ->setImageFile($product->getSmallImage())->getUrl();

            if ($this->helper->getConfig('general/families') && $item->getParentItem()) {
                if (strpos($itemDetails['imageURL'], 'placeholder/image.jpg')) {
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
                $itemDetails['price'] = number_format($item->getParentItem()->getPrice(), 2, '.', '');
            }

            array_push($this->orderDetails['items'], $itemDetails);
        }
        if ($order->getCustomerId()) {
            $userId = $order->getCustomerId();
        } else {
            $userId = md5($order->getCustomerEmail());
        }
        $this->orderDetails['userId'] = $userId;
        $this->orderDetails['email'] = $order->getCustomerEmail();
        $this->orderDetails['nickname'] = $order->getCustomerFirstname()
            ? $order->getCustomerFirstname()
            : $order->getBillingAddress()->getFirstname();
        /** There is no 'deliveryDate' yet */
        $this->orderDetails['locale'] = $this->helper->getConfig('general/locale', $order->getStoreId());

        /** Add partnerSource field */
        $this->orderDetails['partnerSource'] = 'Magento Extension r'.$this->helper->getExtensionVersion();
        $this->orderDetails['deploymentZone'] = strtolower(str_replace(' ', '_',
            $this->helper->getConfig('general/deployment_zone')));

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
                } catch (\Exception $ex) {
                }
            }
        }

        return $product;
    }

    public function canShowDebugDetails()
    {
        return ($this->helper->getConfig('general/environment') == 'staging');
    }

    public function getDebugDetails()
    {
        return print_r($this->getOrderDetails(), true);
    }
}
