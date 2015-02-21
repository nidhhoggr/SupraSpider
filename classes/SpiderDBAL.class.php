<?php

interface SpiderDBALInterface 
{

    public function getLastJob();

}

/**
 * SpiderDBAL 
 * 
 * The purpose of this class is to server as an abstraction layer
 * between the primary DBAL
 *
 * @uses SpiderDBALInterface
 * @package 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
class SpiderDBAL implements SpiderDBALInterface 
{


    public function getLastJob()
    {
        Throw new RuntimeException("Unimplemented method");
    }
}
