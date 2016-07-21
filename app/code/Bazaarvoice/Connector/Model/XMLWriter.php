<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model;

class XMLWriter extends \XMLWriter
{
    public function writeElement($name, $content = null, $cdata = false)
    {
        if($cdata) {
            $this->startElement($name);
            $this->writeCdata($content);
            $this->endElement();
        } else {
            parent::writeElement($name, $content);
        }
    }

    public function writeRaw($content = null, $cdata = false)
    {
        if($cdata) {
            $this->writeCdata($content);
        } else {
            parent::writeRaw($content);
        }
    }

}