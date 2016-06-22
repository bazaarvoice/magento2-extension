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
 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Export extends Command
{
    protected function configure()
    {
        $this->setName('bv:export')->setDescription('Generates Bazaarvoice formatted Magento reviews.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	    echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";

        /** @var \Bazaarvoice\Connector\Model\Feed\Export $exporter */
        $exporter = \Magento\Framework\App\ObjectManager::getInstance()->get('Bazaarvoice\Connector\Model\Feed\Export');
        try {
            $exporter->exportReviews();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
    }

}