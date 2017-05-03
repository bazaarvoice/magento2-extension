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

namespace Bazaarvoice\Connector\Controller\Pixel;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Test extends Action
{
    protected $_checkoutSession;

    /**
     * Test constructor.
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