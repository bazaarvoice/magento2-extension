<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class SftpHost
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class SftpHost implements OptionSourceInterface
{
    const BASE = 'sftp';
    const EUROPE = 'sftp7';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::BASE,
                'label' => __('Non-European Clients (C1-C6)'),
            ],
            [
                'value' => self::EUROPE,
                'label' => __('European Clients (C7)'),
            ],
        ];
    }
}
