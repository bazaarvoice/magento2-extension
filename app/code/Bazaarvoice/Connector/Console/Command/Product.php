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


class Product extends Command
{

    protected function configure()
    {
        $this->setName('bv:product')->setDescription('Generates Bazaarvoice Product Feed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	    echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
        $productFeed = \Magento\Framework\App\ObjectManager::getInstance()->get('Bazaarvoice\Connector\Model\Feed\ProductFeed');
        try {
            $productFeed->generateFeed();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
    }

}