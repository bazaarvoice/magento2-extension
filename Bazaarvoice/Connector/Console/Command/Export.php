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

use Symfony\Component\Console\Command\Command;

class Export extends Command
{
    protected function configure()
    {
        $this->setName('bv:export')->setDescription('Generates Bazaarvoice formatted Magento reviews.');
    }

    protected function execute()
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