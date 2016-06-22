<?php
namespace Bazaarvoice\Connector\Model\Feed;

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

use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Model\Source\Environment;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Framework\Exception;
use Magento\Store\Model\Website;

class Feed
{    
    protected $objectManager;
    protected $test;
    protected $type_id;
    protected $families;

    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        ObjectManagerInterface $objectManager
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->families = $helper->getConfig('general/families');
    }

    public function generateFeed($test = false)
    {
        $this->log('Start Bazaarvoice ' . $this->type_id . ' Feed Generation');

        $this->test = $test;
        if($test) {
            $this->log('TEST MODE');
        }

        switch($this->helper->getConfig('feeds/generation_scope')) {
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
        $this->log('End Bazaarvoice ' . $this->type_id . ' Feed Generation');
    }

    public function exportFeedByStore()
    {
        $this->log('Exporting ' . $this->type_id . ' feed file for each store / store view');

        $stores = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();

        foreach ($stores as $store) {
            /* @var \Magento\Store\Model\Store $store */
            try {
                if ($this->helper->getConfig('feeds/enable_' . $this->type_id . '_feed', $store->getId()) === '1'
                    && $this->helper->getConfig('general/enable_bv', $store->getId()) === '1'
                ) {
                    $this->log('Exporting ' . $this->type_id . ' feed for store: ' . $store->getCode());
                    $this->exportFeedForStore($store);
                }
                else {
                    $this->log(ucwords($this->type_id) . ' feed disabled for store: ' . $store->getCode());
                }
            }
            catch (Exception $e) {
                $this->logger->error('Failed to export daily ' . $this->type_id . ' feed for store: ' . $store->getCode());
                $this->logger->error('Error message: ' . $e->getMessage());
            }
        }
    }

    public function exportFeedByStoreGroup()
    {
        $this->log('Exporting ' . $this->type_id . ' feed file for each store group');

        $storeGroups = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getGroups();

        foreach ($storeGroups as $storeGroup) {
            /* @var \Magento\Store\Model\Group $storeGroup */
            // Default store, for config and product data
            $store = $storeGroup->getDefaultStore();
            try {
                if ($this->helper->getConfig('feeds/enable_' . $this->type_id . '_feed', $store->getId()) === '1'
                    && $this->helper->getConfig('general/enable_bv', $store->getId()) === '1'
                ) {
                    $this->log('Exporting ' . $this->type_id . ' feed for store group: ' . $storeGroup->getName());
                    $this->exportFeedForStoreGroup($storeGroup);
                }
                else {
                    $this->log(ucwords($this->type_id) . ' feed disabled for store group: ' . $storeGroup->getName());
                }
            }
            catch (Exception $e) {
                $this->logger->error('Failed to export daily ' . $this->type_id . ' feed for store group: ' . $storeGroup->getName());
                $this->logger->error('Error message: ' . $e->getMessage());
            }
        }
    }

    public function exportFeedByWebsite()
    {
        $this->log('Exporting ' . $this->type_id . ' feed file for each website');

        $websites = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getWebsites();

        foreach ($websites as $website) {
            /* @var \Magento\Store\Model\Website $website */
            try {
                if ($this->helper->getConfig('feeds/enable_' . $this->type_id . '_feed', $website->getId(), \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE) === '1'
                    && $this->helper->getConfig('general/enable_bv', $website->getId(), \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE) === '1'
                ) {
                    $this->log('Exporting ' . $this->type_id . ' feed for website: ' . $website->getName());
                    $this->exportFeedForWebsite($website);
                }
                else {
                    $this->log(ucwords($this->type_id) . ' feed disabled for website: ' . $website->getName());
                }
            }
            catch (Exception $e) {
                $this->logger->error('Failed to export daily ' . $this->type_id . ' feed for website: ' . $website->getName());
                $this->logger->error('Error message: ' . $e->getMessage());
            }
        }
    }

    public function exportFeedByGlobal()
    {
        $this->log('Exporting ' . $this->type_id . ' feed file for entire Magento instance');

        try {
            if ($this->helper->getConfig('feeds/enable_' . $this->type_id . '_feed', 0) === '1'
                && $this->helper->getConfig('general/enable_bv', 0) === '1'
            ) {
                $this->exportFeedForGlobal();
            }
            else {
                $this->log(ucwords($this->type_id) . ' feed disabled.');
            }
        }
        catch (Exception $e) {
            $this->logger->error('Failed to export daily ' . $this->type_id . ' feed.');
            $this->logger->error('Error message: ' . $e->getMessage());
        }
    }

    public function exportFeedForStore(Store $store) {}

    public function exportFeedForStoreGroup(Group $storeGroup) {}

    public function exportFeedForWebsite(Website $website) {}

    public function exportFeedForGlobal() {}

    /**
     * @param String $xmlns Bazaarvoice Feed xsd reference
     * @param String $clientName Bazaarvoice Client name
     * @return \Bazaarvoice\Connector\Model\XMLWriter
     */
    protected function openFile($xmlns, $clientName)
    {   
        $writer = $this->objectManager->create('\Bazaarvoice\Connector\Model\XMLWriter');
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Feed');
        $writer->writeAttribute('xmlns', $xmlns);
        $writer->writeAttribute('name', $clientName);
        $writer->writeAttribute('incremental', 'false');
        $writer->writeAttribute('extractDate', date('Y-m-d\Th:i:s.u'));
        
        return $writer;
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param String $filename
     */
    protected function closeFile($writer, $filename)
    {
        $writer->endElement();
        $writer->endDocument();
                
        $ioObject = $this->objectManager->get('Magento\Framework\Filesystem\Io\File');
        
        $ioObject->setAllowCreateFolders(true);
        $ioObject->open(array('path' => dirname($filename)));
        $ioObject->write($filename, $writer->outputMemory());
    }

    /**
     * @param $sourceFile
     * @param $destinationFile
     * @param Store $store
     */
    protected function uploadFeed($sourceFile, $destinationFile, $store = null)
    {
        $this->log("Uploading file $sourceFile to SFTP server ($destinationFile).");

        $params = array(
            'host'      => $this->getSFTPHost($store),
            'username'  => $this->helper->getConfig('feeds/sftp_username', $store),
            'password'  => $this->helper->getConfig('feeds/sftp_password', $store)
        );

        /** @var Sftp $sftp */
        $sftp = new Sftp();
        $sftp->open($params);

        $result = $sftp->write($destinationFile, $sourceFile);
        $this->log('result ' . $result);
    }

    /**
     * @param Store $store
     * @return string
     * If sftp host is set in config, use that.
     * Else use preset hosts based on staging or production mode.
     */
    private function getSFTPHost($store = null)
    {
        $environment = $this->helper->getConfig('general/environment', $store);
        $hostSelection = trim($this->helper->getConfig('feeds/sftp_host_name', $store));

        if ($environment == Environment::STAGING) {
            $sftpHost = $hostSelection . '-stg.bazaarvoice.com';
        }
        else {
            $sftpHost = $hostSelection . '.bazaarvoice.com';
        }
        return $sftpHost;
    }
    
    public function log($message) {
        echo $message."\n";
        $this->logger->info($message);
    }

}