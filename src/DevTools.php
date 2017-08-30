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
use Eelly\DevTools\Events\InterceptCenter;
use Phalcon\Di\Injectable;
use Phalcon\DiInterface;
use Symfony\Component\Console\Application;

class DevTools extends Injectable
{
    public function __construct(DiInterface $dependencyInjector)
    {
        $this->setDI($dependencyInjector);
    }

    public function run(): void
    {
        if (in_array($this->config->env, [\Eelly\Application\ApplicationConst::ENV_TEST, \Eelly\Application\ApplicationConst::ENV_DEVELOPMENT])) {
            $eventsManager = $this->di->getEventsManager();
            $interceptCenter = new InterceptCenter($eventsManager);
            $interceptCenter->registAnnotation();
            $this->config->mysqlMode && $interceptCenter->registDbListener();       
        }

        $buildMode = $GLOBALS['buildMode'] ?? false;
        if (\Eelly\Application\ApplicationConst::ENV_DEVELOPMENT == $this->config->env && $buildMode) {
            $this->cliTools();
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
            !is_callable([$this, $actionName.'Action']) && $this->symfonyConsole();
            array_shift($argv);
            empty($argv) && 'helps' !== $actionName && exit('参数不能为空');
            call_user_func([$this, $actionName.'Action'], $argv);
        } else {
            echo <<<EOF
*************************************
运行失败

1、缺少参数
    示例:
    eellyTools build all
2、报警吧
*************************************\n
EOF;
            $this->helpsAction();
            $this->symfonyConsole();
            exit;
        }
    }

    /**
     * 模块构建.
     *
     * @param array $params
     */
    protected function buildAction(array $params): void
    {
        $originalTime = $startTime = microtime(true);
        $startDate = date('Y-m-d H:i:s');
        echo <<<EOF
*************************************
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
*************************************
EOF;
        exit;
    }

    /**
     * api构建.
     *
     * @param array $params
     */
    protected function apiAction(array $params): void
    {
        $apiInfo = $params[0] ?? '';
        $permissionName = $params[1] ?? '';
        if (false === strpos($apiInfo, ':') || empty($permissionName)) {
            exit('参数有误请查看帮助文档===>vendor/bin/eellyTools');
        }

        list($moduleName, $apiName) = explode(':', $apiInfo);
        (new ApiFile($this->di))->setModuleName($moduleName)
            ->setDir()
            ->setNamespace()
            ->setCurrentPermission($permissionName)
            ->buildApiFileInCli($apiName);
    }

    protected function helpsAction(): void
    {
        echo <<<EOF

help document
*************************************
eellyTools [action] [params]\n
    build all  --构建配置文件(devtools.php)内的所有模块
    build user --构建user模块

    api user:index  all              --模块名:接口名  新增/修改api下的全部permission
    api user:index  getUserInfo      --模块名:接口名  新增/修改api下的getUserInfo

*************************************\n
EOF;
    }

    /**
     * 注册运行symfonyConsole
     *
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     * @author wangjiang<wangjiang@eelly.net>
     * 2017年8月24日
     */
    protected function symfonyConsole()
    {
        $application = new Application();
        $registerCommand = $this->config->registerCommand->toArray();
        if(empty($registerCommand)){
            throw new \Symfony\Component\Console\Exception\RuntimeException('not found register command');
        }
        $registerCommands = array_map(function($command){
            return $this->di->getShared($command);
        }, $registerCommand);
        $application->addCommands($registerCommands);
        $application->run();
    }
}
