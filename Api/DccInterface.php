<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Api;

/**
 * Interface DccInterface
 *
 * @package Bazaarvoice\Connector\Api
 */
interface DccInterface
{
    /**
     * @param int|null $productId
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getJson($productId = null, $storeId = null): ?string;
}
