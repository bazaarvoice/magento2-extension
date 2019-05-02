<?php

namespace Bazaarvoice\Connector\Controller\Pixel;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Test extends Action
{
    protected $_checkoutSession;

    /**
     * Test constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        Context $context
    ) {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }



    public function execute()
    {
        $this->_checkoutSession->setLastRealOrderId(12);
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}