<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Test\Unit\Ui\Component\Listing\Column;

use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Ui\Component\Listing\Column\Json;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testMultibyteValueTruncatedOnCharacterBoundary()
    {
        $value = str_repeat('a', 44) . 'ąęśćżź and more text';
        $column = $this->createColumn('locale_name');

        $result = $column->prepareDataSource([
            'data' => ['items' => [['locale_name' => json_encode(['en_US' => $value])]]]
        ]);

        $serialized = (new JsonSerializer())->serialize($result);
        $html = json_decode($serialized, true)['data']['items'][0]['locale_name'];
        $this->assertStringContainsString(str_repeat('a', 44) . 'ą...', $html);
    }

    public function testShortAndUrlValuesAreKeptAsIs()
    {
        $url = 'http://example.com/' . str_repeat('ą', 60);
        $column = $this->createColumn('locale_name');

        $result = $column->prepareDataSource([
            'data' => ['items' => [['locale_name' => json_encode(['short' => 'ąę', 'url' => $url])]]]
        ]);

        $html = $result['data']['items'][0]['locale_name'];
        $this->assertStringContainsString('<strong>short:</strong> ąę<br/>', $html);
        $this->assertStringContainsString($url, $html);
    }

    private function createColumn(string $name): Json
    {
        $formatter = $this->createStub(StringFormatterInterface::class);
        $formatter->method('jsonDecode')->willReturnCallback(
            function ($value) {
                return json_decode($value, true);
            }
        );
        $context = $this->createStub(ContextInterface::class);
        $factory = $this->createStub(UiComponentFactory::class);

        return new Json($context, $factory, $formatter, [], ['name' => $name]);
    }
}
