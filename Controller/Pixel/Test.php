<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Pixel;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Class Test
 *
 * @package Bazaarvoice\Connector\Controller\Pixel
 */
class Test extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Test constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Context                         $context
     */
    public function __construct(
        Session $checkoutSession,
        Context $context
    ) {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id') ?? 1;
        $this->_checkoutSession->setLastRealOrderId($orderId);
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
