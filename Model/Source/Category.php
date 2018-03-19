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
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Model\Source;

class Category extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const SCOPE_GLOBAL = 'global';
    const WEBSITE = 'website';
    const STORE_GROUP = 'group';
    const STORE_VIEW = 'view';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::SCOPE_GLOBAL,
                'label' => __('Global')
            ),
            array(
                'value' => self::WEBSITE,
                'label' => __('Magento Website')
            ),
            array(
                'value' => self::STORE_GROUP,
                'label' => __('Magento Store / Store Group')
            ),
            array(
                'value' => self::STORE_VIEW,
                'label' => __('Magento Store View')
            ),
        );
    }

	/**
	 * @return array
	 */
	public function getAllOptions() {
		return $this->toOptionArray();
	}


}