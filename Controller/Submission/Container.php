<?php

namespace Bazaarvoice\Connector\Controller\Submission;

use Magento\Framework\App\Action\Action;

class Container extends Action
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}