<?php
namespace Bazaarvoice\Connector\Model\Feed;

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

use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Helper\Data;
use Magento\Framework\ObjectManagerInterface;

class Feed
{
    protected $objectManager;

    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        ObjectManagerInterface $objectManager
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
    }


    /**
     * @param String $xmlns Bazaarvoice Feed xsd reference
     * @param String $clientName Bazaarvoice Client name
     * @return \Bazaarvoice\Connector\Model\XMLWriter
     */
    protected function openFile($xmlns, $clientName)
    {   
        $writer = $this->objectManager->create('\Bazaarvoice\Connector\Model\XMLWriter');
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Feed');
        $writer->writeAttribute('xmlns', $xmlns);
        $writer->writeAttribute('name', $clientName);
        $writer->writeAttribute('incremental', 'false');
        $writer->writeAttribute('extractDate', date('Y-m-d\Th:i:s.u'));
        
        return $writer;
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param String $filename
     */
    protected function closeFile($writer, $filename)
    {   
        echo "$filename\n";
        $writer->endElement();
        $writer->endDocument();
                
        $ioObject = $this->objectManager->get('Magento\Framework\Filesystem\Io\File');
        
        $ioObject->setAllowCreateFolders(true);
        $ioObject->open(array('path' => dirname($filename)));
        $ioObject->write($filename, $writer->outputMemory());

    }

}