<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

/**
 * Class XMLWriter
 *
 * @package Bazaarvoice\Connector\Model
 */
class XMLWriter extends \XMLWriter
{
    /**
     * @param string $name
     * @param null   $content
     * @param bool   $cdata
     *
     * @return bool|void
     */
    public function writeElement($name, $content = null, $cdata = false)
    {
        $content = trim((string)$content);
        if ($cdata) {
            $this->startElement($name);
            $this->writeCdata($content);
            $this->endElement();
        } else {
            parent::writeElement($name, $content);
        }
    }

    /**
     * @param null $content
     * @param bool $cdata
     *
     * @return bool|void
     */
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
