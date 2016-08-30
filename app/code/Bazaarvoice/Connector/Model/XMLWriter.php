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

namespace Bazaarvoice\Connector\Model;

class XMLWriter extends \XMLWriter
{
    public function writeElement($name, $content = null, $cdata = false)
    {
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
        if ($cdata) {
            $this->writeCdata($content);
        } else {
            parent::writeRaw($content);
        }
    }

}