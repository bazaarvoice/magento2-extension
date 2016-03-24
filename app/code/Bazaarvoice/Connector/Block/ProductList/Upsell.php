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

class Upsell
{

    public function afterGetProductPrice($subject, $result)
    {
        $result .= "HEY IT'S WORKING";
        
        return $result;
    }

}