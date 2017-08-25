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

namespace Eelly\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Eelly\Acl\Adapter\Database;
use Eelly\DevTools\Traits\SDKDirTrait;
use Eelly\DevTools\BuildFile\ApiFile;

class ModuleAclCommand extends BaseCommand
 {
     use SDKDirTrait;

     /**
      * @var \Eelly\Acl\Adapter\Database
      */
     private $eellyAcl = null;

     /**
      * 接口服务名称
      *
      * @var string
      */
     private $serviceName = '{moduleName}\\Logic\\{serviceName}Logic';

     protected function configure()
     {
         $helpStr = <<<EOT
module all 更新所有的模块
module goods 更新goods模块
EOT;
         $this->setName('module')
            ->setDescription('新增/更新模块的acl信息')
            ->setHelp($helpStr)
            ->addArgument('moduleName', InputArgument::REQUIRED, '模块名');
     }

     protected function execute(InputInterface $input, OutputInterface $output)
     {
         /** @var \Phalcon\Config $config */
         $config = $this->dependencyInjector->getConfig();
         $moduleName = $input->getArgument('moduleName');
         $modules = 'all' == $moduleName ? $config->devModules->toArray() : [$moduleName];
         $this->initAcl();
         foreach($modules as $module){
             $this->addModuleService($module);
         }

     }

     /**
      * 初始化ACL
      *
      * @author wangjiang<wangjiang@eelly.net>
      * @since 2017年8月25日
      */
     private function initAcl(): void
     {
         $oauthDb = $this->dependencyInjector->getConfig()->oauthDb->toArray();
         $this->dependencyInjector->setShared('oauthDb', function () use ($oauthDb) {
             return $db = new Mysql($oauthDb);
         });
         $this->dependencyInjector->setShared('eellyAcl', [
             'className'  => Database::class,
             'properties' => [
                 [
                     'name'  => 'db',
                     'value' => [
                         'type' => 'service',
                         'name' => 'oauthDb',
                     ],
                 ],
             ],
         ]);
         $this->eellyAcl = $this->dependencyInjector->getEellyAcl();
     }

     /**
      * 添加模块服务信息
      *
      * @param string $moduleName
      * @throws \Symfony\Component\Console\Exception\RuntimeException
      * @author wangjiang<wangjiang@eelly.net>
      * @since 2017年8月25日
      */
     private function addModuleService(string $moduleName): void
     {
         $sdkDir = rtrim($this->getEellySDKPath(), '/') . '/';
         $sdkDir .= ucfirst($moduleName);
         if(!file_exists($sdkDir)){
             throw new \Symfony\Component\Console\Exception\RuntimeException('not found ' . $sdkDir);
         }

         $serviceDir = $sdkDir.'/Service';
         if(!file_exists($serviceDir)){
             throw new \Symfony\Component\Console\Exception\RuntimeException('not found ' . $serviceDir);
         }

         $symbol = '=-';
         $symbolStr = str_repeat($symbol, 10);
         echo $symbolStr . $moduleName . '更新开始'. $symbolStr . PHP_EOL . PHP_EOL;
         $this->addModuleAndClient($moduleName);
         $dirInfo = new \DirectoryIterator($serviceDir);
         foreach ($dirInfo as $file) {
             if (!$file->isDot() && $file->isFile()) {
                 $apiImplements = strstr($file->getFilename(), '.', true);
                 $serviceName = strtr($apiImplements, ['Interface' => '']);
                 $searchArr = ['{moduleName}', '{serviceName}'];
                 $replaceArr = [ucfirst($moduleName),$serviceName];
                 $fullServiceName = str_replace($searchArr, $replaceArr, $this->serviceName);
                 $this->eellyAcl->addModuleService($fullServiceName, $moduleName);
                 echo '';
                 $this->addPermission($moduleName, $serviceName);
             }
         }
         echo PHP_EOL . $symbolStr . $moduleName . '更新完成'. $symbolStr . PHP_EOL . PHP_EOL;
     }

     /**
      * 添加模块和客户端信息
      *
      * @param string $moduleName
      * @author wangjiang<wangjiang@eelly.net>
      * @since 2017年8月25日
      */
     private function addModuleAndClient(string $moduleName): void
     {
         $this->eellyAcl->addModule($moduleName);
         $this->eellyAcl->addModuleClient($moduleName);
         $this->eellyAcl->addRole($moduleName, null, $moduleName.'/*/*');
         $this->eellyAcl->addRoleClient($moduleName, $this->eellyAcl->getClientKeyByModuleName($moduleName));
     }

     /**
      * 添加Permission
      *
      * @param string $moduleName
      * @param string $serviceName
      * @param string $permissionName
      * @author wangjiang<wangjiang@eelly.net>
      * @since 2017年8月25日
      */
     private function addPermission(string $moduleName, string $serviceName, string $permissionName = 'all'): void
     {
         (new ApiFile($this->dependencyInjector))->setModuleName($moduleName)
         ->setDir()
         ->setNamespace()
         ->setCurrentPermission($permissionName)
         ->buildApiFileInCli($serviceName);
     }
 }