<?php
namespace Bazaarvoice\Connector\Logger;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license 
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */
 
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = \Monolog\Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/bazaarvoice.log';

    protected $format = "[%datetime%] %level_name%: %message%\n";

    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null
    ) {
        $this->filesystem = $filesystem;
        parent::__construct($filesystem);
        $this->setFormatter(new LineFormatter($this->format, null, true));
    }
    
}