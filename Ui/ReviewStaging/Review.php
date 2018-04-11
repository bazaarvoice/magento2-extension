<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   bvmage2
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2018 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Ui\ReviewStaging;


use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

class Review extends AbstractModifier {

	/**
	 * @param array $meta
	 *
	 * @return array
	 */
	public function modifyMeta( array $meta ) {
		return $meta;
	}

    /**
     * @param array $data
     *
     * @return array
     */
    public function modifyData( array $data ) {
        return $data;
    }


}