<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Console\Command;
 
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Symfony\Component\Console\Command\Command;

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

    protected function execute()
    {
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
        try {
            $this->_productFeed->generateFeed();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        echo "\n" . 'Memory usage: ' . memory_get_usage() . "\n";
    }

}