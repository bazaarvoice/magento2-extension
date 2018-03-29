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
use Magento\Framework\App\State;

class Logger extends \Monolog\Logger
{
    protected $_helper;
	protected $admin = false;

	/**
	 * Logger constructor.
	 *
	 * @param string $name
	 * @param array|\Monolog\Handler\HandlerInterface[] $handlers
	 * @param Data $helper
	 * @param State $state
	 *
	 * @codingStandardsIgnoreStart
	 */
    public function __construct(
    	$name,
	    array $handlers = array(),
	    Data $helper,
		State $state
    ) {
        /** @codingStandardsIgnoreEnd */
        $this->_helper = $helper;
        try {
	        $this->admin = $state->getAreaCode() === 'adminhtml';
        } Catch (\Exception $e) { }
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

        if (
        	php_sapi_name() == "cli"
	        || $this->admin
        ) {
            echo $message."\n";
        }

        return parent::addRecord($level, $message, $context);
    }


}
