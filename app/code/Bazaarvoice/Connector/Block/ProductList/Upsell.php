<?php
namespace Bazaarvoice\Connector\Block\ProductList;
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license 
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */

class Upsell extends Item
{
    protected $type = \Bazaarvoice\Connector\Model\Source\ProductList::UPSELL;
}