<?php
namespace Bazaarvoice\Connector\Logger;
use Bazaarvoice\Connector\Helper\Data;

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

class Logger extends \Monolog\Logger
{
    protected $helper;

    /**
     * Logger constructor.
     * @param string $name
     * @param array|\Monolog\Handler\HandlerInterface[] $handlers
     * @param Data $helper
     */
    public function __construct($name, array $handlers = array(), Data $helper)
    {
        $this->helper = $helper;
        parent::__construct($name, $handlers);
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, array $context = array())
    {
        if($this->helper->getConfig('general/debug') == true)
            return $this->addRecord(static::DEBUG, $message, $context);

        return true;
    }


}