<?php

namespace Bazaarvoice\Connector\Console\Command;

use Bazaarvoice\Connector\Model\Indexer\Flat;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Index extends Command
{
    /** @var Flat $_indexer */
    protected $_indexer;

    /**
     * Purchase constructor.
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @codingStandardsIgnoreEnd
        try {
            $this->_indexer->executeFull();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
    }

}