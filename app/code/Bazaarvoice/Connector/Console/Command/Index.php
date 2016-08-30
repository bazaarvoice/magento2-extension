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

use Bazaarvoice\Connector\Model\Indexer\Flat;
use Symfony\Component\Console\Command\Command;

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

    protected function execute()
    {
        try {
            $this->_indexer->executeFull();
        } Catch (\Exception $e) {
            echo $e->getMessage() . "\n" . $e->getTraceAsString();
        }
    }

}