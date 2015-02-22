<?php

interface SpiderDBALInterface 
{

    public function getLastJob();

    public function connect();

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
abstract class SpiderDBAL implements SpiderDBALInterface 
{
    public function __construct()
    {
        $this->connect();
    }       

    abstract public function connect();

    public function getLastJob()
    {
        Throw new RuntimeException("Unimplemented method");
    }
}
