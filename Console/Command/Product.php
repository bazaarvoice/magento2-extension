<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Console\Command;

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Product
 *
 * @package Bazaarvoice\Connector\Console\Command
 */
class Product extends Command
{
    /** @var ProductFeed $_productFeed */
    protected $_productFeed;

    /**
     * Purchase constructor.
     *
     * @param ProductFeed $productFeed
     */
    public function __construct(ProductFeed $productFeed)
    {
        parent::__construct();
        $this->_productFeed = $productFeed;
    }

    protected function configure()
    {
        $this->setName('bv:product')->setDescription('Generates Bazaarvoice Product Feed.');
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
        try {
            $this->_productFeed->generateFeed();
        } catch (Exception $e) {
            print_r($e->getMessage()."\n".$e->getTraceAsString());
        }
        print_r("\n".'Memory usage: '.memory_get_usage()."\n");
    }
}
