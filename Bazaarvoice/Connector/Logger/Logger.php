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

namespace Bazaarvoice\Connector\Logger;

use Bazaarvoice\Connector\Helper\Data;

class Logger extends \Monolog\Logger
{
    protected $_helper;

    /**
     * Logger constructor.
     * @param string $name
     * @param array|\Monolog\Handler\HandlerInterface[] $handlers
     * @param Data $helper
     */
    /** @codingStandardsIgnoreStart */
    public function __construct($name, array $handlers = array(), Data $helper)
    {
        /** @codingStandardsIgnoreEnd */
        $this->_helper = $helper;
        parent::__construct($name, $handlers);
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, array $context = array())
    {
        if ($this->_helper->getConfig('general/debug') == true)
            return $this->addRecord(static::DEBUG, $message, $context);

        return true;
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (is_array($message))
            $message = print_r($message, 1);

        return parent::addRecord($level, $message, $context);
    }


}