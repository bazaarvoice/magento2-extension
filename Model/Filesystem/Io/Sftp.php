<?php

namespace Bazaarvoice\Connector\Model\Filesystem\Io;


class Sftp extends \Magento\Framework\Filesystem\Io\Sftp
{
	/**
	 * @var \phpseclib\Net\SFTP $_connection
	 */
	protected $_connection = null;
	public function write($filename, $source, $mode = null)
	{
		$mode = is_readable($source) ? \phpseclib\Net\SFTP::SOURCE_LOCAL_FILE : \phpseclib\Net\SFTP::SOURCE_STRING;
		return $this->_connection->put($filename, $source, $mode);
	}
}