<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Formatter\LineFormatter;

/**
 * Class Handler
 *
 * @package Bazaarvoice\Connector\Logger
 */
class Handler extends Base
{
    /**
     * Logging level
     *
     * @var int
     * @codingStandardsIgnoreStart
     */
    protected $loggerType = \Monolog\Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/bazaarvoice.log';
    /** @codingStandardsIgnoreEnd */

    /**
     * Format string
     *
     * @var string
     */
    protected $_format = "[%datetime%] %level_name%: %message%\n";

    /**
     * @param DriverInterface $filesystem
     *
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem
    ) {
        $this->filesystem = $filesystem;
        parent::__construct($filesystem);
        $this->setFormatter(new LineFormatter($this->_format, null, true));
    }
}
