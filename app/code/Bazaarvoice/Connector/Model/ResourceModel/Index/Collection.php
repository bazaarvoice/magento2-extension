<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright   (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package     Bazaarvoice_Connector
 * @author      Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\ResourceModel\Index;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Bazaarvoice\Connector\Model\Index','Bazaarvoice\Connector\Model\ResourceModel\Index');
    }
}