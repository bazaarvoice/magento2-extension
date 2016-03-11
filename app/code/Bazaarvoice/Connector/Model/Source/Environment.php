<?php
namespace Bazaarvoice\Connector\Model\Source;

class Environment
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'staging',
                'label' => __('Staging')
            ),
            array(
                'value' => 'production',
                'label' => __('Production')
            ),
        );
    }
}
