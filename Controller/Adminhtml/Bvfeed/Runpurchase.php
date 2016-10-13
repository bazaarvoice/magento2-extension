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

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;
use Magento\Backend\App\Action\Context;

class Runpurchase extends \Magento\Backend\App\Action
{

    /** @var  PurchaseFeed $_purchaseFeed */
    protected $_purchaseFeed;

    /**
     * Runpurchase constructor.
     * @param Context $context
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(Context $context, PurchaseFeed $purchaseFeed)
    {
        parent::__construct($context);
        $this->_purchaseFeed = $purchaseFeed;
    }

    public function execute()
    {        
        echo '<pre>';
        $this->_purchaseFeed->generateFeed(false, true);
    }   
}