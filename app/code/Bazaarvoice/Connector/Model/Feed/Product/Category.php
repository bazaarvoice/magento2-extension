<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package        Bazaarvoice_Connector
 * @author      Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Model\Feed\Feed;
use Magento\Store\Model\Store;

class Category extends Feed
{

    public function processCategoriesForStore(\XMLWriter $writer, Store $store)
    {
        $writer->startElement('Categories');

        // !TODO Categories Processing

            $writer->startElement('Category');

            $writer->writeElement('ExternalId', '');
            $writer->writeElement('Name', '');

            $writer->endElement(); // Brand


        $writer->endElement(); // Categories
    }

}