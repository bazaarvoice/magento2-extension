<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Test\Unit\Logger;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Framework\App\State;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests connector logging with the installed Monolog version.
 */
class LoggerTest extends TestCase
{
    /**
     * Disabled debug logging produces no output or records.
     */
    public function testDebugDisabled()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler, false);
        $this->expectOutputString('');

        $logger->debug(['product_id' => 12]);

        $this->assertFalse($handler->hasDebugRecords());
    }

    /**
     * Debug arrays are encoded and their context is preserved.
     */
    public function testDebugArrayWithContext()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler, true);
        $this->expectOutputString('{"product_id":12}' . "\n");

        $logger->debug(['product_id' => 12], ['store_id' => 3]);

        $this->assertTrue($handler->hasDebugRecords());
        $record = $handler->getRecords()[0];
        $this->assertSame('{"product_id":12}', $record['message']);
        $this->assertSame(['store_id' => 3], $record['context']);
    }

    /**
     * Errors are written even when debug logging is disabled.
     */
    public function testErrorWithDebugDisabled()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler, false);
        $this->expectOutputString("Feed failed\n");

        $logger->error('Feed failed', ['store_id' => 3]);

        $this->assertTrue($handler->hasErrorRecords());
        $this->assertSame(['store_id' => 3], $handler->getRecords()[0]['context']);
    }

    /**
     * Direct record writes retain the Monolog boolean result.
     */
    public function testAddRecord()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler, false);
        $this->expectOutputString("Feed ready\n");

        $this->assertTrue($logger->addRecord(\Monolog\Logger::INFO, 'Feed ready'));
        $this->assertTrue($handler->hasInfoRecords());
    }

    /**
     * @param TestHandler $handler
     * @param bool $debugEnabled
     * @return Logger
     */
    private function createLogger(TestHandler $handler, $debugEnabled)
    {
        $config = $this->createStub(ConfigProviderInterface::class);
        $config->method('isDebugEnabled')->willReturn($debugEnabled);
        $state = $this->createStub(State::class);
        $state->method('getAreaCode')->willReturn('frontend');

        return new Logger($config, $state, new \Monolog\Logger('bvLogger', [$handler]));
    }
}
