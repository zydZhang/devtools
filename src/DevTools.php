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

namespace Eelly\DevTools;

use Eelly\DevTools\BuildFile\ApiFile;
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
        if ('cli' === PHP_SAPI && $this->config->buildMode) {
            $this->cliTools();
        } elseif (\Eelly\Mvc\Application::ENV_PRODUCTION == $this->config->env && $this->config->mysqlMode) {
            $eventsManager = $this->di->getEventsManager();
            $eventsManager->attach('db', new DbListerner());
        }
    }

    /**
     * 命令行工具.
     */
    public function cliTools(): void
    {
        $argv = isset($GLOBALS['argv']) ? array_map('strtolower', $GLOBALS['argv']) : [];
        if (!empty($argv)) {
            $actionName = $argv[0] ?? '';
            !is_callable([$this, $actionName.'Action']) && exit($actionName.'操作不存在');
            array_shift($argv);
            empty($argv) && 'help' !== $actionName && exit('参数不能为空');
            call_user_func([$this, $actionName.'Action'], $argv);
        } else {
            echo <<<EOF
*************************************
运行失败

1、请检查devtools.php内buildMode是否开启
2、缺少参数
    示例:
    eellyTools build all
3、报警吧
*************************************
EOF;
            exit;
        }
    }

    /**
     * 模块构建.
     *
     * @param array $params
     */
    public function buildAction(array $params): void
    {
        $originalTime = $startTime = microtime(true);
        $startDate = date('Y-m-d H:i:s');
        echo <<<EOF
****************************************************
build模式已开启===>开始时间:({$startDate})\n\n
EOF;
        $modules = 'all' === $params[0] ? $this->config->devModules->toArray() : $params;
        empty($modules) && exit('没有可用的模块可进行构建');
        foreach ($modules as $moduleName) {
            // 模块生成
            $dirInfo[$moduleName] = (new ModuleFile($this->di))->run($moduleName);
            $endTime = microtime(true);
            $expendTime = sprintf('%0.3f', $endTime - $startTime);
            echo <<<EOF
{$moduleName}模块构建完成===>耗时.({$expendTime}s)
----------------------------------------------------\n\n
EOF;
            $startTime = $endTime;
        }
        $totlaTime = sprintf('%0.3f', $endTime - $originalTime);
        echo <<<EOF
build模式完成===>总耗时({$totlaTime}s)\n
****************************************************
EOF;
        exit;
    }

    /**
     * api构建.
     *
     * @param array $params
     */
    public function apiAction(array $params): void
    {
        $apiInfo = $params[0] ?? '';
        $action = $params[1] ?? '';
        if (false === strpos($apiInfo, '\\') || !in_array($action, ['update'])) {
            exit('参数有误请查看帮助文档===>eellyTools help');
        }
        list($moduleName, $apiName) = explode('\\', $apiInfo);
        (new ApiFile($this->di))->setModuleName($moduleName)->setDir()->setNamespace()->buildApiFileInCli($apiName);
    }

    public function helpAction(): void
    {
        echo <<<EOF
****************************************************
eellyTools [action] [params]\n
    build all --构建配置文件(devtools.php)内的所有模块
    build user --构建user模块

    api user\\\index  update --新增/修改api

****************************************************
EOF;
    }
}
