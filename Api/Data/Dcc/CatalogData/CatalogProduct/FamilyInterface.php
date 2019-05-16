<?php

namespace Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct;

/**
 * @method string getId()
 * @method bool getExpand()
 * @method array getMembers()
 * @method FamilyInterface setId(string $id)
 * @method FamilyInterface setExpand(bool $expand)
 * @method FamilyInterface setMembers(array $members)
 */
interface FamilyInterface
{
    /**
     * @param string     $key
     * @param string|int $index
     * @return mixed
     */
    public function getData($key = '', $index = null);
}
