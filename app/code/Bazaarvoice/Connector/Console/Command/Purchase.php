<?php
namespace Bazaarvoice\Connector\Console\Command;
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license \
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */
 
use Bazaarvoice\Connector\Model\Feed\PurchaseFeed;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputInterface;


class Purchase extends Command
{
    /** @var PurchaseFeed $purchaseFeed */
    protected $purchaseFeed;

    /**
     * Purchase constructor.
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(PurchaseFeed $purchaseFeed)
    {
        parent::__construct();
        $this->purchaseFeed = $purchaseFeed;
    }

    protected function configure()
    {
        $this->setName('bv:purchase')->setDescription('Generates Bazaarvoice Purchase Feed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	    echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
        $this->purchaseFeed->generateFeed();
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
    }

}