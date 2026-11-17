<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Logger;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Exception;
use Magento\Framework\App\State;
use Psr\Log\AbstractLogger;

/**
 * Logs connector messages to the configured handlers and console.
 */
class Logger extends AbstractLogger
{
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var bool
     */
    private $admin = false;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @param ConfigProviderInterface $configProvider
     * @param State $state
     * @param \Monolog\Logger $logger
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        State $state,
        \Monolog\Logger $logger
    ) {
        $this->configProvider = $configProvider;
        try {
            $this->admin = $state->getAreaCode() === 'adminhtml';
        } catch (Exception $e) {
            $this->admin = false;
        }
        $this->logger = $logger;
    }

    /**
     * Log a debug message when debug logging is enabled.
     *
     * @param string|array $message
     * @param array $context
     */
    public function debug($message, array $context = []): void
    {
        if ($this->configProvider->isDebugEnabled()) {
            if (is_array($message)) {
                $message = json_encode($message);
            }
            $this->addRecord(\Monolog\Logger::DEBUG, strval($message), $context);
        }
    }

    /**
     * Log a message at the given level.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->addRecord(\Monolog\Logger::toMonologLevel($level), (string)$message, $context);
    }

    /**
     * Write a record and echo messages in the console and admin area.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @param \DateTimeImmutable|null $datetime
     * @return bool
     */
    public function addRecord($level, string $message, array $context = [], $datetime = null): bool
    {
        if (PHP_SAPI === 'cli' || $this->admin) {
            print_r($message . "\n");
        }

        return $this->logger->addRecord($level, $message, $context);
    }
}
