<?php

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

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
	 * @throws \Magento\Setup\Exception
	 */
	public function execute()
    {
        $result = $this->_indexer->executeFull();
        if ($result)
            $this->messageManager->addSuccessMessage(__('Product Feed Index is being rebuilt.'));

        $this->_redirect('adminhtml/bvindex/index');
    }
}
