<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\ViewModel;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Header
 *
 * @package Bazaarvoice\Connector\ViewModel
 */
class Header implements ArgumentInterface
{
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    protected $bvLogger;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * Reviews constructor.
     *
     * @param \Bazaarvoice\Connector\Logger\Logger $bvLogger
     * @param ConfigProviderInterface              $configProvider
     * @param StringFormatterInterface             $stringFormatter
     */
    public function __construct(
        Logger $bvLogger,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter
    ) {
        $this->bvLogger = $bvLogger;
        $this->stringFormatter = $stringFormatter;
        $this->configProvider = $configProvider;
    }

    /**
     * @return ConfigProviderInterface
     */
    public function getConfigProvider()
    {
        return $this->configProvider;
    }

    /**
     * @return mixed
     */
    public function isBvEnabled()
    {
        return $this->configProvider->isBvEnabled();
    }

    /**
     * @return mixed|string|null
     */
    public function getExtensionVersion()
    {
        return $this->configProvider->getExtensionVersion();
    }
}
