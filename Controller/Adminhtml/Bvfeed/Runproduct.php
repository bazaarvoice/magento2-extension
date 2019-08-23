<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Runproduct
 *
 * @package Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed
 */
class Runproduct extends Action
{
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\ProductFeed
     */
    private $productFeed;

    /**
     * Runproduct constructor.
     *
     * @param Context     $context
     * @param ProductFeed $productFeed
     */
    public function __construct(Context $context, ProductFeed $productFeed)
    {
        $this->productFeed = $productFeed;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        //todo: Is there a better way to do this?
        // phpcs:ignore
        echo '<pre>';
        $this->productFeed->setForce(true)->generateFeed();
    }
}
