<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Export
 *
 * @package Bazaarvoice\Connector\Console\Command
 */
class Export extends Command
{
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\Export
     */
    protected $_exporter;

    /**
     * Export constructor.
     *
     * @param \Bazaarvoice\Connector\Model\Feed\Export $exporter
     */
    public function __construct(\Bazaarvoice\Connector\Model\Feed\Export $exporter)
    {
        parent::__construct('bv:export');
        $this->_exporter = $exporter;
    }

    protected function configure()
    {
        $this->setDescription('Generates Bazaarvoice formatted Magento reviews.');
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
            $this->_exporter->exportReviews();
        } catch (Exception $e) {
            print_r($e->getMessage()."\n".$e->getTraceAsString());
        }
        print_r("\n".'Memory usage: '.memory_get_usage()."\n");
    }
}
