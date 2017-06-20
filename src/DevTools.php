<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\DevTools;

use Eelly\DevTools\BuildFile\ModuleFile;
use Eelly\DevTools\Events\DbListerner;
use Phalcon\Di\Injectable;
use Phalcon\DiInterface;

class DevTools extends Injectable
{
    public function __construct(DiInterface $dependencyInjector)
    {
        $this->setDI($dependencyInjector);
    }

    public function run(): void
    {
        if ($this->config->buildMode) {
            $startTime = microtime(true);
            echo 'build模式已开启===>开启时间:'.$startTime.PHP_EOL;
            $modules = $this->config->devModules->toArray();
            foreach ($modules as $moduleName) {
                // 模块生成
                $dirInfo[$moduleName] = (new ModuleFile($this->di))->run($moduleName);
                echo $moduleName . '模块构建完成...' . PHP_EOL;
            }
            echo 'build模式完成===>耗时'.(microtime(true) - $startTime).PHP_EOL;
            exit;
        }
        if($this->config->mysqlMode) {
            $eventsManager = $this->di->getEventsManager();
            $eventsManager->attach('db', new DbListerner);
        }
    }
}
