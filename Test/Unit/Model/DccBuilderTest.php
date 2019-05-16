<?php

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
class DccBuilderTest extends TestCase
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
        $className = Dcc::class;
        $arguments = $this->objectManager->getConstructArguments($className);
        $dccBuilder = $this->objectManager->getObject(Dcc::class, $arguments);
        $result = $dccBuilder->build();

        $this->assertEmpty($result);
    }

    public function testBuildProductExistsSomeResult()
    {
        $currentProductProviderMock = $this->createPartialMock(CurrentProductProvider::class, ['getProduct']);
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $currentProductProviderMock->method('getProduct')->willReturn($productMock);

        $className = Dcc::class;
        $arguments = $this->objectManager->getConstructArguments($className);
        $arguments['currentProductProvider'] = $currentProductProviderMock;
        $dccBuilder = $this->objectManager->getObject(Dcc::class, $arguments);
        $result = $dccBuilder->build();

        $this->assertEmpty($result);
    }
}
