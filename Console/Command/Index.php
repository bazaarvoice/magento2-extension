<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Console\Command;

use Bazaarvoice\Connector\Model\Indexer\Flat;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Index
 *
 * @package Bazaarvoice\Connector\Console\Command
 */
class Index extends Command
{
    /** @var Flat $_indexer */
    protected $_indexer;

    /**
     * Purchase constructor.
     *
     * @param Flat $indexer
     */
    public function __construct(Flat $indexer)
    {
        parent::__construct();
        $this->_indexer = $indexer;
    }

    protected function configure()
    {
        $this->setName('bv:index')->setDescription('Clear Bazaarvoice Product Feed Index.');
    }

    // @codingStandardsIgnoreStart

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @codingStandardsIgnoreEnd
        try {
            $this->_indexer->executeFull();
        } catch (Exception $e) {
            // phpcs:ignore
            echo $e->getMessage()."\n".$e->getTraceAsString();
        }
    }
}
