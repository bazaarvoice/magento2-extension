<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Submission;

use Magento\Framework\App\Action\Action;

/**
 * Class Container
 *
 * @package Bazaarvoice\Connector\Controller\Submission
 */
class Container extends Action
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
