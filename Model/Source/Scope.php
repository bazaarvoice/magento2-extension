<?php

namespace Bazaarvoice\Connector\Model\Source;

class Scope
{
    const SCOPE_GLOBAL = 'global';
    const WEBSITE = 'website';
    const STORE_GROUP = 'group';
    const STORE_VIEW = 'view';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::SCOPE_GLOBAL,
                'label' => __('Global')
            ),
            array(
                'value' => self::WEBSITE,
                'label' => __('Magento Website')
            ),
            array(
                'value' => self::STORE_GROUP,
                'label' => __('Magento Store / Store Group')
            ),
            array(
                'value' => self::STORE_VIEW,
                'label' => __('Magento Store View')
            ),
        );
    }
}