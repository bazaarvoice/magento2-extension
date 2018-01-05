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
 * @package   bvmage2
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2017 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

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