<?php
namespace SupraSpider\Interfaces;

interface SupraSpiderDBALInterface
{
    public function getLastJob();

    public function saveLog();

    public function connect();
}
