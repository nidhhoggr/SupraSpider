<?php
namespace SupraSpider;

use SupraSpider\Interfaces\SupraSpiderJobInterface;
/**
 * SupraSpiderJob 
 * 
 * @package SupraSpider 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
class SupraSpiderJob extends SupraSpiderJobInterface
{
    public
        $id,
        $date_range,
        $report,
        $is_batch,
        $is_successful;

    private $error_count;

    function __construct()
    {
        $this->error_count = 0;
    }

    public function init($primaryDBALJob)
    {
        $this->error_count = 0;

        $job_id = $primaryDBALJob->getId();

        if(empty($job_id))
        {
            Throw new \Exception('The job object from the primary DBAL must have an id'); 
        }
        else
        {
            $this->id = $job_id;
            
            $this->date_range = $primaryDBALJob->getDateRange();
        }
    }

    public function getHasFailed()
    {
        return $this->error_count > 0;
    }

    public function hasFailed()
    {
        $this->error_count = $this->error_count + 1;
    }

    public function generateReport() 
    {
        return "\r\nError count: {$this->error_count}\r\n";
    }

    public function isBatch()
    {
        $this->is_batch = TRUE;
    }
}
