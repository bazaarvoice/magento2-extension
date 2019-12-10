<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Console\Command;

use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PurchaseTest
 *
 * @package Bazaarvoice\Connector\Console\Command
 */
class PurchaseTest extends Command
{
    /** @var PurchaseFeed $_purchaseFeed */
    protected $_purchaseFeed;

    /**
     * Purchase constructor.
     *
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(PurchaseFeed $purchaseFeed)
    {
        parent::__construct();
        $this->_purchaseFeed = $purchaseFeed;
    }

    protected function configure()
    {
        $this->setName('bv:purchasetest')->setDescription('Generates Bazaarvoice Test Purchase Feed.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        print_r("\n".'Memory usage: '.memory_get_usage()."\n");
        $this->_purchaseFeed->setTest(true)->generateFeed();
        print_r("\n".'Memory usage: '.memory_get_usage()."\n");
    }
}
