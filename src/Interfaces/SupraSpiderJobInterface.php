<?php
namespace SupraSpider\Interfaces;

interface SupraSpiderJobInterface
{
    public function getFailed();

    public function getId();

    public function getTimesRan(); 
}
