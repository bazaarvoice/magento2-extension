<?php

/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Model\Config;

use Magento\Framework\App\Config\Value;

class Version extends Value
{
    /**
     * @var \Magento\Framework\Component\ComponentRegistrarInterface
     */
    protected $componentRegistrar;
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    protected $readFactory;

    /**
     * Version constructor
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $config
     * @param \Magento\Framework\App\Cache\TypeListInterface               $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param \Magento\Framework\Component\ComponentRegistrarInterface     $componentRegistrar
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory          $readFactory
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection,
        \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        array $data = []
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory        = $readFactory;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Get current module version from composer.json
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getModuleVersion()
    {
        /**
         * @var string $composerJsonData 
         */

        /**
         * @var string[] $data 
         */

        /**
         * @var ReadInterface $directoryRead 
         */

        /**
         * @var null|string $path 
         */

        /**
         * @var string $version 
         */

        $path = $this->componentRegistrar->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            'Bazaarvoice_Connector'
        );
        $directoryRead = $this->readFactory->create($path);
        $composerJsonData = $directoryRead->readFile('composer.json');
        $data = json_decode($composerJsonData);
        if (!empty($data->version)) {
            $version = $data->version;
        }

        return $version ?? __('Could not retrieve extension version.');
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function afterLoad()
    {
        $version = $this->getModuleVersion();
        $this->setValue($version);
    }
}
