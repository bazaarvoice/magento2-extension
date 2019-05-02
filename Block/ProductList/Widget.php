<?php

namespace Bazaarvoice\Connector\Block\ProductList;

class Widget extends Item
{
    protected $_type = \Bazaarvoice\Connector\Model\Source\ProductList::WIDGET;

	// @codingStandardsIgnoreStart
	public function beforeGetProductPriceHtml(
		/** @noinspection PhpUnusedParameterInspection */
		$subject, $product)
	{
		// @codingStandardsIgnoreEnd
		if ($this->isEnabled()) {
			$this->_product = $product;
		}
	}

	// @codingStandardsIgnoreStart
	public function afterGetProductPriceHtml(
		/** @noinspection PhpUnusedParameterInspection */
		$subject, $result)
	{
		// @codingStandardsIgnoreEnd
		if ($this->isEnabled()) {
			$productIdentifier = $this->helper->getProductId($this->_product);
			$productUrl = $this->_product->getProductUrl();
			$result = '
            <div data-bv-show="inline_rating"
				 data-bv-product-id="' . $productIdentifier . '"
				 data-bv-redirect-url="' . $productUrl . '"></div>' . $result;
		}
		return $result;
	}

}
