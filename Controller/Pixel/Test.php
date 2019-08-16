<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Pixel;

use Magento\Checkout\Controller\Onepage\Success;

/**
 * Class Test
 *
 * @package Bazaarvoice\Connector\Controller\Pixel
 */
class Test extends Success
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $orderIncrementId = $this->getRequest()->getParam('order_increment_id');
        $orderId = $this->getRequest()->getParam('order_id');
        $session = $this->getOnepage()->getCheckout();
        $session->setLastRealOrderId($orderIncrementId);
        $session->setLastOrderId($orderId);
        $session->setLastSuccessQuoteId('dummy_value');
        $session->setLastQuoteId('dummy_value');
        return parent::execute();
    }
}
