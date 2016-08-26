<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model;

use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Notification\MessageInterface;

class Notification implements MessageInterface
{
    /** @var  ScheduleFactory $objectManger */
    protected $scheduleFactory;

    /**
     * Notification constructor.
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(ScheduleFactory $scheduleFactory)
    {
        $this->scheduleFactory = $scheduleFactory;
    }

    /**
     * @return mixed
     */
    public function getIdentity()
    {
        return md5("bazaarvoice_cron");
    }

    /**
     * @return mixed
     */
    public function isDisplayed()
    {
        /**
         * TODO: Cron schedule doesn't keep long enough records
         * for this to be reliable. Find another way to track it.
         */
        return false;
        /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $schedule */
        $schedule = $this->scheduleFactory->create()->getCollection();
        $schedule->addFieldToFilter('job_code', Cron::JOB_CODE)->setOrder('executed_at', 'desc');
        if($schedule->count() == 0) {
            return true;
        }
        /** @var Schedule $last */
        $last = $schedule->getFirstItem();
        if(
            $last->getExecutedAt() == null ||
            $last->getFinishedAt() == null
        )
            return true;

        $now = new \DateTime();
        $executed = new \DateTime($last->getExecutedAt());
        $finished = new \DateTime($last->getFinishedAt());

        if(
            $now->diff($executed)->format('%a') > 10 ||
            $now->diff($finished)->format('%a') > 10
        )
            return true;

        return false;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return __('Bazaarvoice PIE Feed doesn\'t appear to be running, please make sure your <a href="%2" target="_blank">Magento cron job</a> is running.',
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