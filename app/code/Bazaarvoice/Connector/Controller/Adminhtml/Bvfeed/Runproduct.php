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

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Backend\App\Action\Context;

class Runproduct extends \Magento\Backend\App\Action
{

    /** @var  ProductFeed $_productFeed */
    protected $_productFeed;

    /**
     * Runproduct constructor.
     * @param Context $context
     * @param ProductFeed $productFeed
     */
    public function __construct(Context $context, ProductFeed $productFeed)
    {
        parent::__construct($context);
        $this->_productFeed = $productFeed;
    }

    public function execute()
    {
        echo '<pre>';
        $this->_productFeed->generateFeed(false, true);
    }
}