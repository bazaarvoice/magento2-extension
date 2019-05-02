<?php

namespace Bazaarvoice\Connector\Model\Source;

class SftpHost
{
    const BASE = 'sftp';
    const EUROPE = 'sftp7';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::BASE,
                'label' => __('Non-European Clients (C1-C6)')
            ),
            array(
                'value' => self::EUROPE,
                'label' => __('European Clients (C7)')
            ),
        );
    }
}
