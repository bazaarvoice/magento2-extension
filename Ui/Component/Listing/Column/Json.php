<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Ui\Component\Listing\Column;

use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Json
 *
 * @package Bazaarvoice\Connector\Ui\Component\Listing\Column
 */
class Json extends Column
{
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;

    /**
     * Json constructor.
     *
     * @param ContextInterface         $context
     * @param UiComponentFactory       $uiComponentFactory
     * @param StringFormatterInterface $stringFormatter
     * @param array                    $components
     * @param array                    $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StringFormatterInterface $stringFormatter,
        array $components = [],
        array $data = []
    ) {
        $this->stringFormatter = $stringFormatter;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (empty($item[$fieldName])) {
                    continue;
                }
                $valueData = $this->stringFormatter->jsonDecode($item[$fieldName]);
                if (is_object($valueData) == true
                    || is_array($valueData) == true) {
                    $html = '';
                    foreach ($valueData as $key => $value) {
                        if (!is_numeric($key)) {
                            $html .= "<strong>$key:</strong> ";
                        }
                        $html .= $this->truncate($value).'<br/>';
                    }
                    $item[$fieldName] = $html;
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param $string
     *
     * @return string
     */
    private function truncate($string)
    {
        if (strlen($string) > 45 && substr($string, 0, 4) != 'http') {
            $string = substr($string, 0, 45).'...';
        }

        return $string;
    }
}
