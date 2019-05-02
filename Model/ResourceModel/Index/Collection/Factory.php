<?php

namespace Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;

class Factory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Return newly created instance of the index collection
     *
     * @return \Bazaarvoice\Connector\Model\ResourceModel\Index\Collection
     */
    public function create()
    {
        return $this->_objectManager->create('Bazaarvoice\Connector\Model\ResourceModel\Index\Collection');
    }
}
