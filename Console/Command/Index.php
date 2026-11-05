<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Console\Command;

use Bazaarvoice\Connector\Model\Indexer\Indexer;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Console\Cli;

/**
 * Class Index
 *
 * @package Bazaarvoice\Connector\Console\Command
 */
class Index extends Command
{
    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * Purchase constructor.
     *
     * @param Indexer       $indexer
     */
    public function __construct(
        Indexer $indexer
    ) {
        parent::__construct();
        $this->indexer = $indexer;
    }

    protected function configure()
    {
        $this->setName('bv:index')->setDescription('Clear Bazaarvoice Product Feed Index.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $returnValue = Cli::RETURN_SUCCESS;
        try {
            $this->indexer->executeFull();
        } catch (Exception $e) {
            print_r($e->getMessage()."\n".$e->getTraceAsString());
            $returnValue = Cli::RETURN_FAILURE;
        }
        return $returnValue;
    }
}
