<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Index
 *
 * @package Bazaarvoice\Connector\Model\ResourceModel
 */
class Index extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bazaarvoice_index_product', 'entity_id');
    }

    /**
     * @param array $attributes
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadBy($attributes)
    {
        $adapter = $this->_resources->getConnection('core_read');
        $where = [];
        foreach ($attributes as $attributeCode => $value) {
            $where[] = sprintf('%s=:%s', $attributeCode, $attributeCode);
        }

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where(implode(' AND ', $where));

        $binds = $attributes;

        return $adapter->fetchRow($select, $binds);
    }
}
