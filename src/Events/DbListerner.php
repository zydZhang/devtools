<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\DevTools\Events;

use Phalcon\Db\Profiler;
use Phalcon\Events\Event;
use Phalcon\Db\Adapter\Pdo\Mysql;

class DbListerner
{
    /**
     * db分析类
     * @var object 
     */
    private $profiler = '';


    public function __construct() 
    {
        $this->profiler = new Profiler();
    }
	
    public function afterQuery(Event $event, Mysql $connection): void
    {        
        $this->profiler->stopProfile();
    }
    
    public function beforeQuery(Event $event, Mysql $connection): void
    {
        $sql = $connection->getSQLStatement();
        $sqlVerity = new VerifySql($connection);
        //校验sql
        $sqlVerity->sqlVerify($sql); 
        $this->profiler->startProfile($sql);
    } 
}
