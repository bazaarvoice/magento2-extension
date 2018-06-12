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

namespace Bazaarvoice\Connector\Block;


class Header extends Reviews {

    /**
     * Bypass product checking from Product block
     *
     * @return string
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _toHtml() {
        if (!$this->getTemplate()) {
            return '';
        }
        return $this->fetchView($this->getTemplateFile());
    }

}