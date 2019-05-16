<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;

/**
 * Class Disable
 *
 * @package Bazaarvoice\Connector\Plugin
 */
class Disable
{
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * Disable constructor.
     *
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        ConfigProviderInterface $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param $subject
     * @param $result
     *
     * @return false|string
     */
    public function afterToHtml($subject, $result)
    {
        if ($this->configProvider->isBvEnabled()) {
            return '';
        }

        return $result;
    }
}
