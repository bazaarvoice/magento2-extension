<?php

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Model\Source\Environment;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Framework\ObjectManagerInterface;
use Bazaarvoice\Connector\Model\Filesystem\Io\Sftp;
use Magento\Store\Model\Group;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;

class Feed
{

    protected $_objectManager;
    protected $_test;
    protected $_force;
    protected $_typeId;
    protected $_families;
	protected $_helper;
	protected $_logger;

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
    )
    {
        $this->_helper        = $helper;
        $this->_logger        = $logger;
        $this->_objectManager = $objectManager;
        $this->_families      = $helper->getConfig('general/families');
    }

    public function generateFeed($test = false, $force = false)
    {
        $this->_logger->debug('===============================');
        $this->_logger->debug( 'Start Bazaarvoice ' . $this->_typeId . ' Feed Generation');

        $this->_test = $test;
        if ($test) {
            $this->_logger->debug('TEST MODE');
        }

        $this->_force = $force;

        try {
            switch($this->_helper->getConfig('feeds/generation_scope')) {
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
        } Catch (\Exception $e) {
            $this->_logger->crit( $e->getMessage() . "\n" . $e->getTraceAsString());
        }
        $this->_logger->debug( 'End Bazaarvoice ' . $this->_typeId . ' Feed Generation');
    }

    public function exportFeedByStore()
    {
        $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed file for each store / store view');

        $stores = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();

        foreach ($stores as $store) {
            /* @var \Magento\Store\Model\Store $store */
            try {
                if ($this->_force || $this->_helper->getConfig( 'feeds/enable_' . $this->_typeId . '_feed', $store->getId()) === '1'
                    && $this->_helper->getConfig('general/enable_bv', $store->getId()) === '1'
                ) {
                    $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed for store: ' . $store->getCode());
                    $this->exportFeedForStore($store);
                }
                else {
                    $this->_logger->info( ucwords($this->_typeId) . ' feed disabled for store: ' . $store->getCode());
                }
            }
            catch (\Exception $e) {
                $this->_logger->error( 'Failed to export daily ' . $this->_typeId . ' feed for store: ' . $store->getCode());
                $this->_logger->crit( $e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }
    }

    public function exportFeedByStoreGroup()
    {
        $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed file for each store group');

        $storeGroups = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getGroups();

        foreach ($storeGroups as $storeGroup) {
            /* @var \Magento\Store\Model\Group $storeGroup */
            /** Default store, for config and product data */
            $store = $storeGroup->getDefaultStore();
            try {
                if ($this->_force || $this->_helper->getConfig( 'feeds/enable_' . $this->_typeId . '_feed', $store->getId()) === '1'
                    && $this->_helper->getConfig('general/enable_bv', $store->getId()) === '1'
                ) {
                    $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed for store group: ' . $storeGroup->getName());
                    $this->exportFeedForStoreGroup($storeGroup);
                }
                else {
                    $this->_logger->info( ucwords($this->_typeId) . ' feed disabled for store group: ' . $storeGroup->getName());
                }
            }
            catch (\Exception $e) {
                $this->_logger->error( 'Failed to export daily ' . $this->_typeId . ' feed for store group: ' . $storeGroup->getName());
                $this->_logger->crit( $e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }
    }

    public function exportFeedByWebsite()
    {
        $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed file for each website');

        $websites = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getWebsites();

        foreach ($websites as $website) {
            /* @var \Magento\Store\Model\Website $website */
            try {
                if ($this->_force
                    || ( $this->_helper->getConfig( 'feeds/enable_' . $this->_typeId . '_feed', $website->getId(), ScopeInterface::SCOPE_WEBSITE) === '1'
                    && $this->_helper->getConfig('general/enable_bv', $website->getId(), ScopeInterface::SCOPE_WEBSITE) === '1')
                ) {
                    $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed for website: ' . $website->getName());
                    $this->exportFeedForWebsite($website);
                }
                else {
                    $this->_logger->info( ucwords($this->_typeId) . ' feed disabled for website: ' . $website->getName());
                }
            }
            catch (\Exception $e) {
                $this->_logger->error( 'Failed to export daily ' . $this->_typeId . ' feed for website: ' . $website->getName());
                $this->_logger->crit( $e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }
    }

    public function exportFeedByGlobal()
    {
        $this->_logger->info( 'Exporting ' . $this->_typeId . ' feed file for entire Magento instance');

        try {
            if ($this->_force || $this->_helper->getConfig( 'feeds/enable_' . $this->_typeId . '_feed', 0) === '1'
                && $this->_helper->getConfig('general/enable_bv', 0) === '1'
            ) {
                $this->exportFeedForGlobal();
            }
            else {
                $this->_logger->info( ucwords($this->_typeId) . ' feed disabled.');
            }
        }
        catch (\Exception $e) {
            $this->_logger->error( 'Failed to export daily ' . $this->_typeId . ' feed.');
            $this->_logger->crit( $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    public function exportFeedForStore(Store $store)
    {

    }

    public function exportFeedForStoreGroup(Group $storeGroup)
    {

    }

    public function exportFeedForWebsite(Website $website)
    {

    }

    public function exportFeedForGlobal()
    {

    }

    /**
     * @param String $xmlns Bazaarvoice Feed xsd reference
     * @param String $clientName Bazaarvoice Client name
     * @return \Bazaarvoice\Connector\Model\XMLWriter
     */
    protected function openFile($xmlns, $clientName)
    {   
        $writer = $this->_objectManager->create('\Bazaarvoice\Connector\Model\XMLWriter');
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Feed');
        $writer->writeAttribute('xmlns', $xmlns);
        $writer->writeAttribute('name', $clientName);
        $writer->writeAttribute('incremental', 'false');
        $writer->writeAttribute('extractDate', date('Y-m-d\Th:i:s.u'));
        $writer->writeAttribute('generator', 'Magento Extension r' . $this->_helper->getExtensionVersion());
        
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
                
        $ioObject = $this->_objectManager->get('Magento\Framework\Filesystem\Io\File');
        
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
        $this->_logger->debug('Uploading file');
        $this->_logger->debug( 'Local file ' . basename($sourceFile));
        $this->_logger->debug( 'Remote file ' . $this->getSFTPHost($store) . $destinationFile);

        $params = array(
            'host'      => $this->getSFTPHost($store),
            'username'  => $this->_helper->getConfig('feeds/sftp_username', $store),
            'password'  => $this->_helper->getConfig('feeds/sftp_password', $store)
        );
        $this->_logger->debug( 'Username ' . $params['username']);

        /** @var Sftp $sftp */
        $sftp = new Sftp();
	    try {
		    $sftp->open( $params );

		    $result = $sftp->write($destinationFile, $sourceFile);
		    $this->_logger->info( 'File upload result: ' . ($result ? 'success!' : 'failure.'));
		    if ($result) {
			    /** @var \Magento\Framework\Filesystem\Io\File $ioObject */
			    $ioObject = $this->_objectManager->get('Magento\Framework\Filesystem\Io\File');

			    $sentFile = dirname($sourceFile) . '/sent/' . basename($sourceFile);

			    $ioObject->setAllowCreateFolders(true);
			    $ioObject->open(array('path' => dirname($sentFile)));
			    $ioObject->mv($sourceFile, $sentFile);
		    }
	    } catch ( \Exception $e ) {
	    	$this->_logger->err($e->getMessage());
	    }
    }

    /**
     * @param Store $store
     * @return string
     * If sftp host is set in config, use that.
     * Else use preset hosts based on staging or production mode.
     */
    private function getSFTPHost($store = null)
    {
        $environment = $this->_helper->getConfig('general/environment', $store);
        $hostSelection = trim($this->_helper->getConfig('feeds/sftp_host_name', $store));

        if ($environment == Environment::STAGING) {
            $sftpHost = $hostSelection . '-stg.bazaarvoice.com';
        }
        else {
            $sftpHost = $hostSelection . '.bazaarvoice.com';
        }
        return $sftpHost;
    }

}
