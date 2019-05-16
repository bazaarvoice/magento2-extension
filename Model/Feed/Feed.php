<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Model\Filesystem\Io\Sftp;
use Bazaarvoice\Connector\Model\Source\Scope;
use Exception;
use Magento\Store\Model\Group;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;

/**
 * Class Feed
 *
 * @package Bazaarvoice\Connector\Model\Feed
 */
abstract class Feed
{
    /**
     * @var bool
     */
    protected $test;
    /**
     * @var bool
     */
    protected $force;
    /**
     * @var string
     */
    protected $typeId;
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;
    /**
     * @var StringFormatterInterface
     */
    protected $stringFormatter;
    /**
     * @var \Bazaarvoice\Connector\Model\XMLWriter
     */
    protected $xmlWriter;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $filesystem;

    /**
     */
    public function generateFeed()
    {
        $this->logger->debug('===============================');
        $this->logger->debug('Start Bazaarvoice '.ucfirst($this->typeId).' Feed Generation');

        if ($this->test) {
            $this->logger->debug('TEST MODE');
        }

        try {
            switch ($this->configProvider->getFeedGenerationScope()) {
                case Scope::STORE_GROUP:
                    $this->exportFeedByStoreGroup();
                    break;
                case Scope::STORE_VIEW:
                    $this->exportFeedByStore();
                    break;
                case Scope::WEBSITE:
                    $this->exportFeedByWebsite();
                    break;
                case Scope::SCOPE_GLOBAL:
                    $this->exportFeedByGlobal();
                    break;
            }
        } catch (Exception $e) {
            $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
        }
        $this->logger->debug('End Bazaarvoice '.$this->typeId.' Feed Generation');
    }

    public function exportFeedByStoreGroup()
    {
        $this->logger->info('Exporting '.$this->typeId.' feed file for each store group');

        $storeGroups = $this->storeManager->getGroups();

        foreach ($storeGroups as $storeGroup) {
            /* @var \Magento\Store\Model\Group $storeGroup */
            /** Default store, for config and product data */
            $store = $storeGroup->getDefaultStore();
            try {
                if ($this->force || $this->configProvider->canSendFeed($this->typeId, $store->getId())) {
                    $this->logger->info('Exporting '.$this->typeId.' feed for store group: '.$storeGroup->getName());
                    $this->exportFeedForStoreGroup($storeGroup);
                } else {
                    $this->logger->info(ucwords($this->typeId).' feed disabled for store group: '
                        .$storeGroup->getName());
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to export daily '.$this->typeId.' feed for store group: '
                    .$storeGroup->getName());
                $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            }
        }
    }

    public function exportFeedByStore()
    {
        $this->logger->info('Exporting '.$this->typeId.' feed file for each store / store view');

        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            /* @var \Magento\Store\Model\Store $store */
            try {
                if ($this->force || $this->configProvider->canSendFeed($this->typeId, $store->getId())) {
                    $this->logger->info('Exporting '.$this->typeId.' feed for store: '.$store->getCode());
                    $this->exportFeedForStore($store);
                } else {
                    $this->logger->info(ucwords($this->typeId).' feed disabled for store: '.$store->getCode());
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to export daily '.$this->typeId.' feed for store: '.$store->getCode());
                $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            }
        }
    }
    public function exportFeedByWebsite()
    {
        $this->logger->info('Exporting '.$this->typeId.' feed file for each website');

        $websites = $this->storeManager->getWebsites();

        foreach ($websites as $website) {
            /* @var \Magento\Store\Model\Website $website */
            try {
                if ($this->force || $this->configProvider->canSendFeed($this->typeId, $website->getId(), ScopeInterface::SCOPE_WEBSITE)) {
                    $this->logger->info('Exporting '.$this->typeId.' feed for website: '.$website->getName());
                    $this->exportFeedForWebsite($website);
                } else {
                    $this->logger->info(ucwords($this->typeId).' feed disabled for website: '.$website->getName());
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to export daily '.$this->typeId.' feed for website: '.$website->getName());
                $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            }
        }
    }

    public function exportFeedByGlobal()
    {
        $this->logger->info('Exporting '.$this->typeId.' feed file for entire Magento instance');

        try {
            if ($this->force || $this->configProvider->canSendFeed($this->typeId, 0)) {
                $this->exportFeedForGlobal();
            } else {
                $this->logger->info(ucwords($this->typeId).' feed disabled.');
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to export daily '.$this->typeId.' feed.');
            $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
        }
    }

    /**
     * @param \Magento\Store\Model\Store $store
     */
    public function exportFeedForStore(Store $store)
    {
    }

    /**
     * @param \Magento\Store\Model\Group $storeGroup
     */
    public function exportFeedForStoreGroup(Group $storeGroup)
    {
    }

    /**
     * @param \Magento\Store\Model\Website $website
     */
    public function exportFeedForWebsite(Website $website)
    {
    }
    public function exportFeedForGlobal()
    {
    }

    /**
     * @param bool $force
     *
     * @return $this
     */
    public function setForce(bool $force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * @param bool $test
     *
     * @return $this
     */
    public function setTest(bool $test)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * @param String $xmlns      Bazaarvoice Feed xsd reference
     * @param String $clientName Bazaarvoice Client name
     *
     * @return \Bazaarvoice\Connector\Model\XMLWriter
     */
    protected function openFile($xmlns, $clientName)
    {
        $writer = $this->xmlWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Feed');
        $writer->writeAttribute('xmlns', $xmlns);
        $writer->writeAttribute('name', $clientName);
        $writer->writeAttribute('incremental', 'false');
        $writer->writeAttribute('extractDate', date('Y-m-d\Th:i:s.u'));
        $writer->writeAttribute('generator', 'Magento Extension r'.$this->configProvider->getExtensionVersion());

        return $writer;
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param String                                 $filename
     */
    protected function closeFile($writer, $filename)
    {
        $writer->endElement();
        $writer->endDocument();

        $ioObject = $this->filesystem;
        $ioObject->setAllowCreateFolders(true);
        $ioObject->open(['path' => dirname($filename)]);
        $ioObject->write($filename, $writer->outputMemory());
    }

    /**
     * @param       $sourceFile
     * @param       $destinationFile
     * @param Store $store
     */
    protected function uploadFeed($sourceFile, $destinationFile, $store = null)
    {
        $this->logger->debug('Uploading file');
        $this->logger->debug('Local file '.basename($sourceFile));
        $this->logger->debug('Remote file '.$this->configProvider->getSftpHost($store->getId()).$destinationFile);

        $params = [
            'host'     => $this->configProvider->getSftpHost($store->getId()),
            'username' => $this->configProvider->getSftpUsername($store->getId()),
            'password' => $this->configProvider->getSftpPassword($store->getId()),
        ];
        $this->logger->debug('Username '.$params['username']);

        /** @var Sftp $sftp */
        $sftp = new Sftp();
        try {
            $sftp->open($params);
            $result = $sftp->write($destinationFile, $sourceFile);
            $sftp->close();
            $this->logger->info('File upload result: '.($result ? 'success!' : 'failure.'));
            if ($result) {
                /** @var \Magento\Framework\Filesystem\Io\File $ioObject */
                $ioObject = $this->filesystem;
                $sentFile = dirname($sourceFile).'/sent/'.basename($sourceFile);
                $ioObject->setAllowCreateFolders(true);
                $ioObject->open(['path' => dirname($sentFile)]);
                $ioObject->mv($sourceFile, $sentFile);
            }
        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
        }
    }
}
