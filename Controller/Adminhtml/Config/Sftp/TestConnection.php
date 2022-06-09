<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Controller\Adminhtml\Config\Sftp;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class TestConnection
 *
 * @package Bazaarvoice\Connector\Controller\Adminhtml\Config\Sftp\TestConnection
 */
class TestConnection extends Action
{
   
    /**
     * TestConnection constructor.
     *
     * @param Context      $context
     * @param PurchaseFeed $purchaseFeed
     */
    public function __construct(
        Context $context,
        \Bazaarvoice\Connector\Model\Filesystem\Io\Sftp $sftp,
        ConfigProviderInterface $configProvider,
        StoreInterface $store,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory,
        StripTags $tagFilter
    ) {
        $this->sftp = $sftp;
        $this->storeManager = $storeManager;

        $this->configProvider = $configProvider;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tagFilter = $tagFilter;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];

        $options = $this->getRequest()->getParams();

        try {
            if(empty($options['username']) || empty($options['password']) || empty($options['host'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Missing SFTP credentials.')
                );
            }

            $params = [
                'host'  => $this->configProvider->getSftpHost($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE, $options['host']),
                'username'  => $options['username'],
                'password' => $this->configProvider->getSftpPassword($this->storeManager->getStore()->getId())
            ];

            if($options['password'] != '******') {
                $params['password'] = $options['password'];
            }

            $this->sftp->open($params);
            $this->sftp->close();

            $result['success'] = true;

        }catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $message = __($e->getMessage());
            $result['errorMessage'] = $this->tagFilter->filter($message);
        }


        /**
        * @var \Magento\Framework\Controller\Result\Json $resultJson 
        */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }

}