<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Scope
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class Scope implements OptionSourceInterface
{
    const SCOPE_GLOBAL = 'global';
    const WEBSITE = 'website';
    const STORE_GROUP = 'group';
    const STORE_VIEW = 'view';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SCOPE_GLOBAL,
                'label' => __('Global'),
            ],
            [
                'value' => self::WEBSITE,
                'label' => __('Magento Website'),
            ],
            [
                'value' => self::STORE_GROUP,
                'label' => __('Magento Store / Store Group'),
            ],
            [
                'value' => self::STORE_VIEW,
                'label' => __('Magento Store View'),
            ],
        ];
    }
}
