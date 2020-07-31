<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Indexer\Flat;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Rebuildproduct
 *
 * @package Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed
 */
class Rebuildproduct extends Action
{

    /** @var  Flat $indexer */
    protected $indexer;

    /**
     * Runproduct constructor.
     *
     * @param Context $context
     * @param Flat    $indexer
     */
    public function __construct(Context $context, Flat $indexer)
    {
        parent::__construct($context);
        $this->indexer = $indexer;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $this->indexer->executeFull();
            $this->messageManager->addSuccessMessage(__('Product Feed Index is being rebuilt.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        $this->_redirect('adminhtml/bvindex/index');
    }
}
