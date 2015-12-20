<?php
namespace SupraSpider;

use SupraSpider\Interfaces\SupraSpiderDBALInterface;
/**
 * SpiderDBAL 
 * 
 * The purpose of this class is to server as an abstraction layer
 * between the primary DBAL
 *
 * @uses SpiderDBALInterface
 * @package SupraSpider 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
abstract class SupraSpiderDBAL implements SupraSpiderDBALInterface 
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
