<?php


namespace Bazaarvoice\Connector\Controller\Adminhtml\Config\Validation;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;

class Cron extends Action
{
    const BV_CRONJOBS = ['bazaarvoice_send_orders', 'bazaarvoice_send_products'];
    const MAX_PAGE_SIZE = 35000;
    const STATUS_ERROR = 'error';
    const STATUS_SUCCESS    = 'success';
    const STATUS_RUNNING    = 'running';
    const STATUS_PENDING    = 'pending';

    protected $_jobs = [];
    protected $_messages = [];
    protected $_success = true;


    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    protected $cronCollection;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        StoreInterface $store,
        JsonFactory $resultJsonFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cronCollection = $collectionFactory;


        parent::__construct($context);
    }

    public function execute()
    {
        $this->_jobs = $this->config->getJobs();

        if ($this->configurationCheck()) {
           $this->_messages[] = [
               'success'    => true,
               'message'    => "All BV cron jobs were configured properly"
           ];

           $collection = $this->getSchedules();
           if ($collection) {
               // make sure scheduled jobs were running successfully
               // make sure there's no failed jobs

               $items = $collection->getItems();
               $sendProductsStatus = false;
               // bazaarvoice_send_orders
               foreach($items as $item) {
                   if ($item->getData('job_code') == self::BV_CRONJOBS[0]) {
                       if($item->getData('status') == self::STATUS_ERROR) {
                           $this->_success = false;
                           $this->_messages[] = [
                               'success'    => false,
                               'message'    => 'An error was occured when running' . self::BV_CRONJOBS[0] . ' last ' . $item->getData('messages')
                           ];
                       }

                       if($item->getData('status') == self::STATUS_SUCCESS) {
                           $this->_messages[] = [
                               'success'    => true,
                               'message'    => 'bazaarvoice_send_orders cron job is working properly'
                           ];
                       }

                       if($item->getData('status') == self::STATUS_RUNNING) {
                           $this->_messages[] = [
                               'success'    => true,
                               'message'    => 'bazaarvoice_send_orders cron job is running'
                           ];
                       }
                   }
               }

               // bazaarvoice_send_products
               $collection->addFieldToFilter('job_code', self::BV_CRONJOBS[1]);
               switch($collection->getLastItem()->getData('status')) {
                   case self::STATUS_RUNNING:
                           $this->_messages[] = [
                               'success'    => true,
                               'message'    => 'bazaarvoice_send_products cron job is running'
                           ];
                       break;
                   case self::STATUS_SUCCESS:
                           $this->_messages[] = [
                               'success'    => true,
                               'message'    => 'bazaarvoice_send_products is working properly.'
                           ];
                           break;
                   case self::STATUS_ERROR:
                        $this->_success = false;
                       $this->_messages[] = [
                           'success'    => false,
                           'message'    => 'An error occurred when running the last scheduled task for bazaarvoice_send_products cron job.'
                       ];
                       break;
               }


               $this->_messages[] = [
                   'success'    => true,
                   'message'    => "All BV cron jobs are running successfully!"
               ];
           }else {
               $this->_messages[] = [
                   'success'    => 'warning',
                   'message'    => "No BV cron jobs are currently in the queue. Please try again later!"
               ];
           }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'success' => $this->_success,
            'messages' => $this->_messages
        ]);
    }

    public function configurationCheck() {

        foreach(self::BV_CRONJOBS as $cronJob) {
            if(!$this->in_array_r($cronJob, $this->_jobs)) {
                $this->_success = false;
                $this->_messages = [
                    'success'    => false,
                    'message'    => $cronJob + " does not exist on the cron job list of the system."
                ];
            }
        }

        if (!$this->_success) {
            return false;
        }

        return true;
    }

    private function in_array_r($item , $array){
        return preg_match('/"'.preg_quote($item, '/').'"/i' , json_encode($array));
    }

    protected function getSchedules() {
        $collection = $this->cronCollection->create()
            ->addFieldToFilter('job_code', ['in' => implode(",",self::BV_CRONJOBS)])
            ->addOrder('scheduled_at', 'DESC')
            ->addOrder('job_code', 'ASC')
            ->setPageSize(self::MAX_PAGE_SIZE)
            ->addFieldToFilter(
                'scheduled_at', [
                    'gt' => date(
                        'Y-m-d H:m:s',
                        strtotime(date('Y-m-d H:m:s') . ' -7 day')
                    )
                ]
            );

        if (!$collection->count()) {
            return false;
        }

        return $collection;
    }
}
