<?php

namespace Bazaarvoice\Connector\Controller\Adminhtml\Bvindex;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Backend\Model\View\Result\Page;

class Index extends \Magento\Backend\App\Action
{
    protected $_scopeConfig;
    protected $_resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();

        if ($this->_scopeConfig->getValue('catalog/frontend/flat_catalog_product') == false
            || $this->_scopeConfig->getValue('catalog/frontend/flat_catalog_category') == false) {
            $url = $this->getUrl('*/system_config/edit/section/catalog');
            $this->messageManager->addErrorMessage(
                __(
                    'Bazaarvoice Product feed requires Catalog Flat Tables to be enabled. Please check your <a href="%1">Store Config</a>.',
                    $url
                )
            );
        }

        $resultPage->setActiveMenu('Magento_Catalog::inventory');
        $resultPage->getConfig()->getTitle()->prepend(__('Bazaarvoice Product Feed'));

        return $resultPage;
    }
}