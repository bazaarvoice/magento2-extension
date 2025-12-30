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

class Sftp extends Action
{
    protected $_messages = [];
    protected $_success = true;

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


        try {
            $params = [
                'host'  => $this->configProvider->getSftpHost($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE),
                'username'  => $this->configProvider->getSftpUsername($this->storeManager->getStore()->getId(), ScopeInterface::SCOPE_STORE),
                'password' => $this->configProvider->getSftpPassword($this->storeManager->getStore()->getId())
            ];

            foreach ($params as $key => $param) {
                if(!$param) {
                    throw new LocalizedException(__(ucfirst($key) . " is missing. Kindly check the configuration above."));
                }
            }

            $this->sftp->open($params);
            $this->sftp->close();

            $this->_messages[] = [
                'success'   => true,
                'message'   => 'SFTP has been configured and connected successfully'
            ];
        }catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_success = false;
            $this->_messages[] = [
                'success'   => false,
                'message'   => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->_success = false;
            $message = __($e->getMessage());
            $this->_messages[] = [
                'success'   => false,
                'message'   => $this->tagFilter->filter($message)
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'success' => $this->_success,
            'messages' => $this->_messages
        ]);

    }
}
