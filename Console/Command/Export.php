<?php

namespace Bazaarvoice\Connector\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Export extends Command
{
    protected $_exporter;

    /**
     * Export constructor.
     *
     * @param \Bazaarvoice\Connector\Model\Feed\Export $exporter
     */
    public function __construct( \Bazaarvoice\Connector\Model\Feed\Export $exporter ) {
        parent::__construct( 'bv:export' );
        $this->_exporter = $exporter;
    }

    protected function configure()
    {
        $this->setDescription('Generates Bazaarvoice formatted Magento reviews.');
    }

    // @codingStandardsIgnoreStart
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @codingStandardsIgnoreEnd
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";

        try {
            $this->_exporter->exportReviews();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
    }
}