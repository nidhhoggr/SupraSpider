<?php

class JobQueueManager extends BaseSpider 
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
