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

namespace Bazaarvoice\Connector\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $_loggerType = \Monolog\Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $_fileName = '/var/log/bazaarvoice.log';

    protected $_format = "[%datetime%] %level_name%: %message%\n";

    /**
     * @param DriverInterface $filesystem
     */
    public function __construct(
        DriverInterface $filesystem
    )
    {
        $this->filesystem = $filesystem;
        parent::__construct($filesystem);
        $this->setFormatter(new LineFormatter($this->_format, null, true));
    }
    
}