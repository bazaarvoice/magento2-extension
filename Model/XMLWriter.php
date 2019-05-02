<?php

namespace Bazaarvoice\Connector\Model;

class XMLWriter extends \XMLWriter
{
    public function writeElement($name, $content = null, $cdata = false)
    {
    	$content = trim($content);
        if ($cdata) {
            $this->startElement($name);
            $this->writeCdata($content);
            $this->endElement();
        } else {
            parent::writeElement($name, $content);
        }
    }

    public function writeRaw($content = null, $cdata = false)
    {
	    $content = trim($content);
        if ($cdata) {
            $this->writeCdata($content);
        } else {
            parent::writeRaw($content);
        }
    }

}