<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Filesystem\Io;

/**
 * Class Sftp
 */
class Sftp extends \Magento\Framework\Filesystem\Io\Sftp
{
    /**
     * @var \phpseclib3\Net\SFTP $_connection
     */
    protected $_connection = null;

    /**
     * Fixes Magento 2.1 phpseclib notice.
     *
     * @param $filename
     * @param $source
     * @param null $mode
     *
     * @return mixed
     */
    public function write($filename, $source, $mode = null)
    {
        $mode = is_readable($source) ? \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE : \phpseclib3\Net\SFTP::SOURCE_STRING;
        return $this->_connection->put($filename, $source, $mode);
    }
}
