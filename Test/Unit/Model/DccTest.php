<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Test\Unit\Model\Dcc;

use Bazaarvoice\Connector\Model\CurrentProductProvider;
use Bazaarvoice\Connector\Model\Dcc;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class BuilderTest
 *
 * @package Bazaarvoice\Connector\Test\Unit\Model\Dcc
 */
class DccTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
    }

    public function testBuildProductDoesNotExistEmptyResult()
    {
        /** @var \Bazaarvoice\Connector\Model\Dcc $dcc */

        $className = Dcc::class;
        $arguments = $this->objectManager->getConstructArguments($className);
        $dcc = $this->objectManager->getObject($className, $arguments);
        $result = $dcc->getJson();

        $this->assertEmpty($result);
    }

    public function testBuildProductExistsSomeResult()
    {
        /** @var \Bazaarvoice\Connector\Model\Dcc $dcc */

        $currentProductProviderMock = $this->createPartialMock(CurrentProductProvider::class, ['getProduct']);
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $currentProductProviderMock->method('getProduct')->willReturn($productMock);

        $dccCatalogDataMock = $this->createPartialMock(\Bazaarvoice\Connector\Model\Dcc\CatalogData::class, ['getData']);
        $dccCatalogDataMock->method('getData')->willReturn('{}');

        $dccCatalogDataBuilderMock = $this->createPartialMock(\Bazaarvoice\Connector\Model\Dcc\CatalogDataBuilder::class, ['build']);
        $dccCatalogDataBuilderMock->method('build')->willReturn($dccCatalogDataMock);

        $stringFormatterMock = $this->createPartialMock(\Bazaarvoice\Connector\Model\StringFormatter::class, ['jsonEncode']);
        $stringFormatterMock->method('jsonEncode')->willReturn('{}');

        $arguments = $this->objectManager->getConstructArguments(Dcc::class);
        $arguments['currentProductProvider'] = $currentProductProviderMock;
        $arguments['catalogDataBuilder'] = $dccCatalogDataBuilderMock;
        $arguments['stringFormatter'] = $stringFormatterMock;
        $dcc = $this->objectManager->getObject(Dcc::class, $arguments);
        $result = $dcc->getJson();

        $this->assertNotEmpty($result);
    }
}
