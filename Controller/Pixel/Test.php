<?php
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
        $orderId = $this->getRequest()->getParam('order_id') ?? '000000001';
        $session = $this->getOnepage()->getCheckout();
        $session->setLastRealOrderId($orderId);
        $session->setLastSuccessQuoteId('dummy_value');
        return parent::execute();
    }
}
