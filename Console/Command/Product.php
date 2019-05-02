<?php

namespace Bazaarvoice\Connector\Console\Command;
 
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Product extends Command
{
    /** @var ProductFeed $_productFeed */
    protected $_productFeed;

    /**
     * Purchase constructor.
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


    // @codingStandardsIgnoreStart
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @codingStandardsIgnoreEnd
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
        try {
            $this->_productFeed->generateFeed();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
    }

}