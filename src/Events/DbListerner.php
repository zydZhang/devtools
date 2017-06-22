<?php

declare(strict_types=1);
/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\DevTools\Events;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Db\Profiler;
use Phalcon\Events\Event;

class DbListerner
{
    /**
     * db分析类.
     *
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
