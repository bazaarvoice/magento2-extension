<?php
namespace Bazaarvoice\Connector\Console\Command;
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */

use Bazaarvoice\Connector\Model\Indexer\Flat;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Index extends Command
{
    /** @var Flat $indexer */
    protected $indexer;

    /**
     * Purchase constructor.
     * @param Flat $indexer
     */
    public function __construct(Flat $indexer)
    {
        parent::__construct();
        $this->indexer = $indexer;
    }

    protected function configure()
    {
        $this->setName('bv:index')->setDescription('Clear Bazaarvoice Product Feed Index.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->indexer->executeFull();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
    }

}