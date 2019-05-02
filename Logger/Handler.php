<?php

namespace Bazaarvoice\Connector\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     * @codingStandardsIgnoreStart
     */
    protected $loggerType = \Monolog\Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/bazaarvoice.log';
    /** @codingStandardsIgnoreEnd */

    /**
     * Format string
     * @var string
     */
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