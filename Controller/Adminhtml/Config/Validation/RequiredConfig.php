<?php


namespace Bazaarvoice\Connector\Controller\Adminhtml\Config\Validation;


use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class RequiredConfig extends Action
{
    protected $_messages = [];
    protected $_success = true;

    public function __construct(
        Context $context,
        ConfigProviderInterface $configProvider,
        StoreInterface $store,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory
    ) {
        $this->storeManager = $storeManager;

        $this->configProvider = $configProvider;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->dccAndProducFeedEnabledCheck();

        }catch (LocalizedException $e) {
            $this->_success = false;
            $this->_messages[] = [
                'success'   => false,
                'message'   => $e->getMessage()
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'success' => $this->_success,
            'messages' => $this->_messages
        ]);
    }

    protected function dccAndProducFeedEnabledCheck() {
        if(
            $this->configProvider->isDccEnabled($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE)
            && $this->configProvider->canSendProductFeed($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE))
        {
            throw new LocalizedException(__('You can only choose between Product Feed and DCC, but not both.'));
        }else if(
            !$this->configProvider->isDccEnabled($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE)
            && !$this->configProvider->canSendProductFeed($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE))
        {
            throw new LocalizedException(__('Product feed is not enabled.'));
        }else if($this->configProvider->isDccEnabled($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE)) {
            $this->_messages[] = [
                'success'   => true,
                'message'   => "DCC cofiguration looks good!"
            ];
        }else {
            $this->_messages[] = [
                'success'   => true,
                'message'   => "Product feed cofiguration looks good!"
            ];
        }

    }
}
