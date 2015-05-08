<?php
namespace SupraSpider;

use SupraSpider\SupraSpider;

/**
 * JobQueueManager 
 * 
 * @uses SupraSpider
 * @package SupraSpider 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
class JobQueueManager extends SupraSpider 
{
    const JOB_QUEUE_FAILURE_THRESHOLD = 10;

    function runLastJob()
    {
        $job = $this->dbal->getLastJob();

        $times_ran = $job->getTimesRan();

        if($times_ran <= self::JOB_QUEUE_FAILURE_THRESHOLD)
        {
            return shell_exec('php ' . dirname(__FILE__) . '/' . self::RUN_SCRIPT); 
        }
    }
}
