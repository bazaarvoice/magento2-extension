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

use Bazaarvoice\Connector\Model\Indexer\Flat;
use Magento\Backend\App\Action\Context;

class Rebuildproduct extends \Magento\Backend\App\Action
{

    /** @var  Flat $_indexer */
    protected $_indexer;

    /**
     * Runproduct constructor.
     * @param Context $context
     * @param Flat $indexer
     */
    public function __construct(Context $context, Flat $indexer)
    {
        parent::__construct($context);
        $this->_indexer = $indexer;
    }

    public function execute()
    {
        $result = $this->_indexer->executeFull();
        if ($result)
            $this->messageManager->addSuccess(__('Product Feed Index has been flagged for rebuild.'));

        $this->_redirect('adminhtml/bvindex/index');
    }
}