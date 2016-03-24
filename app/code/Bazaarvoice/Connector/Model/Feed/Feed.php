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

use XMLWriter;

class Feed
{
    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }
    

    protected function openFile($xmlns, $clientName)
    {   
        $writer = new XMLWriter();
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
    
    protected function closeFile($writer, $filename)
    {   
        echo "$filename\n";
        $writer->endElement();
        $writer->endDocument();
                
        $ioObject = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Filesystem\Io\File');
        
        $ioObject->setAllowCreateFolders(true);
        $ioObject->open(array('path' => dirname($filename)));
        $ioObject->write($filename, $writer->outputMemory());

    }

}