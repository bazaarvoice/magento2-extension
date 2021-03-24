<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Indexer\Indexer;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Rebuildproduct extends Action
{
    /**
     * @var \Bazaarvoice\Connector\Model\Indexer\Indexer
     */
    protected $indexer;

    /**
     * Runproduct constructor.
     *
     * @param Context                                      $context
     * @param \Bazaarvoice\Connector\Model\Indexer\Indexer $indexer
     */
    public function __construct(Context $context, Indexer $indexer)
    {
        parent::__construct($context);
        $this->indexer = $indexer;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $this->indexer->executeFull();
            $this->messageManager->addSuccessMessage(__('Product Feed Index is being rebuilt.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->__toString());
        }

        $this->_redirect('adminhtml/bvindex/index');
    }
}
