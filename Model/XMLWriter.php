<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

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
     * @return bool
     */
    public function writeElement($name, $content = null, $cdata = false): bool
    {
        $content = trim((string)$content);
        if ($cdata) {
            $this->startElement($name);
            $this->writeCdata($content);
            $this->endElement();
            return true;
        } else {
            return parent::writeElement($name, $content);
        }
        return false;
    }

    /**
     * @param null $content
     * @param bool $cdata
     *
     * @return bool
     */
    public function writeRaw($content = null, $cdata = false): bool
    {
        $content = trim((string)$content);
        if ($cdata) {
            return $this->writeCdata($content);
        } else {
            return parent::writeRaw($content);
        }
        return false;
    }
}
