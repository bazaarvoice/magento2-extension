<?php
namespace Bazaarvoice\Connector\Model\Source;

class Environment
{
    const STAGING = 'staging';
    const PRODUCTION = 'production';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::STAGING,
                'label' => __('Staging')
            ),
            array(
                'value' => self::PRODUCTION,
                'label' => __('Production')
            ),
        );
    }
}
