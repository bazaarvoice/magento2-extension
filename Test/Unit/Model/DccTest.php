<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Test\Unit\Model;

use Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataBuilderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\CurrentProductProvider;
use Bazaarvoice\Connector\Model\Dcc;
use Bazaarvoice\Connector\Model\Dcc\CatalogData;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests the product data exposed by DCC.
 */
class DccTest extends TestCase
{
    /**
     * Missing products produce an empty result.
     */
    public function testBuildProductDoesNotExistEmptyResult()
    {
        $currentProductProvider = $this->createStub(CurrentProductProvider::class);
        $currentProductProvider->method('getProduct')->willReturn(false);
        $dcc = new Dcc(
            $this->createStub(CatalogDataBuilderInterface::class),
            $this->createStub(ProductRepositoryInterface::class),
            $currentProductProvider,
            $this->createStub(StringFormatterInterface::class),
            $this->createStub(Logger::class)
        );

        $this->assertSame('', $dcc->getJson());
    }

    /**
     * Existing products are built and encoded as catalog data.
     */
    public function testBuildProductExistsSomeResult()
    {
        $product = $this->createStub(ProductInterface::class);
        $currentProductProvider = $this->createStub(CurrentProductProvider::class);
        $currentProductProvider->method('getProduct')->willReturn($product);
        $catalogData = new CatalogData(['locale' => 'en_US']);
        $catalogDataBuilder = $this->createMock(CatalogDataBuilderInterface::class);
        $catalogDataBuilder->expects($this->once())->method('build')->with($product)->willReturn($catalogData);
        $stringFormatter = $this->createMock(StringFormatterInterface::class);
        $stringFormatter->expects($this->once())->method('jsonEncode')
            ->with(['catalogData' => ['locale' => 'en_US']])
            ->willReturn('{"catalogData":{"locale":"en_US"}}');
        $dcc = new Dcc(
            $catalogDataBuilder,
            $this->createStub(ProductRepositoryInterface::class),
            $currentProductProvider,
            $stringFormatter,
            $this->createStub(Logger::class)
        );

        $this->assertSame('{"catalogData":{"locale":"en_US"}}', $dcc->getJson());
    }
}
