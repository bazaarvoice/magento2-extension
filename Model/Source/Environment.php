<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Environment
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class Environment implements OptionSourceInterface
{
    const STAGING = 'staging';
    const PRODUCTION = 'production';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::STAGING,
                'label' => __('Staging'),
            ],
            [
                'value' => self::PRODUCTION,
                'label' => __('Production'),
            ],
        ];
    }
}
