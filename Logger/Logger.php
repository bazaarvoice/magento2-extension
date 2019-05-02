<?php

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
     * @param string|array $message
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
