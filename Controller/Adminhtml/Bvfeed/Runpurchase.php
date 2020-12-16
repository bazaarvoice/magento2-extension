<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed;

use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Runpurchase
 *
 * @package Bazaarvoice\Connector\Controller\Adminhtml\Bvfeed
 */
class Runpurchase extends Action
{

    /** @var  PurchaseFeed $purchaseFeed */
    protected $purchaseFeed;

    /**
     * Runpurchase constructor.
     *
     * @param Context      $context
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(Context $context, PurchaseFeed $purchaseFeed)
    {
        parent::__construct($context);
        $this->purchaseFeed = $purchaseFeed;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        echo 'sdfs';exit;
        print_r('<pre>');
        $this->purchaseFeed->setForce(true)->generateFeed();
    }
}
