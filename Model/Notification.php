<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Notification\MessageInterface;

/**
 * Class Notification
 *
 * @package Bazaarvoice\Connector\Model
 */
class Notification implements MessageInterface
{
    /** @var  ScheduleFactory $objectManger */
    protected $_scheduleFactory;

    /**
     * Notification constructor.
     *
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(ScheduleFactory $scheduleFactory)
    {
        $this->_scheduleFactory = $scheduleFactory;
    }

    /**
     * @return mixed
     */
    public function getIdentity()
    {
        return md5('bazaarvoice_cron');
    }

    /**
     * @return mixed
     */
    public function isDisplayed()
    {
        /**
         * TODO: Cron schedule does not keep long enough records
         * for this to be reliable. Find another way to track it.
         */
        return false;
        /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $schedule
         * $schedule = $this->_scheduleFactory->create()->getCollection();
         * $schedule->addFieldToFilter('job_code', Cron::JOB_CODE)->setOrder('executed_at', 'desc');
         * if ($schedule->count() == 0) {
         * return true;
         * }
         * /** @var Schedule $last
         * $last = $schedule->getFirstItem();
         * if (
         * $last->getExecutedAt() == null ||
         * $last->getFinishedAt() == null
         * )
         * return true;
         *
         * $now = new \DateTime();
         * $executed = new \DateTime($last->getExecutedAt());
         * $finished = new \DateTime($last->getFinishedAt());
         *
         * if (
         * $now->diff($executed)->format('%a') > 10 ||
         * $now->diff($finished)->format('%a') > 10
         * )
         * return true;
         *
         * return false;
         * */
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return __(
            'Bazaarvoice PIE Feed doesn\'t appear to be running, please make sure your <a href="%2" target="_blank">Magento cron job</a> is running.',
            'http://devdocs.magento.com/guides/v2.0/config-guide/cli/config-cli-subcommands-cron.html#config-cli-cron-bkg'
        );
    }

    /**
     * @return mixed
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_MAJOR;
    }
}
