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

namespace Eelly\DevTools\BuildFile;

use Eelly\Acl\Adapter\Database;
use Phalcon\Db\Adapter\Pdo\Mysql;

/**
 * Module生成类.
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class ModuleFile extends File
{
    /**
     * 模块目录.
     *
     * @var string
     */
    protected $moduleDir = '';

    /**
     * 模块生成的目录信息.
     *
     * @var array
     */
    protected $moduleDirInfo = [];

    /**
     * 模块名称.
     *
     * @var string
     */
    protected $moduleName = '';

    /**
     * 模块文件导入命名空间.
     *
     * @var array
     */
    protected $moduleFileUserNamespace = [
        'Eelly\Events\Listener\AclListener',
        'Eelly\Events\Listener\ApiLoggerListener',
        'Eelly\Events\Listener\AsyncAnnotationListener',
        'Eelly\Events\Listener\CacheAnnotationListener',
        'Eelly\Events\Listener\ValidationAnnotationListener',
        'Eelly\Mvc\AbstractModule',
        'Member\Command\TestCommand',
        'Phalcon\DiInterface as Di',
        'Symfony\Component\Console\ConsoleEvents',
        'Symfony\Component\Console\Event\ConsoleCommandEvent',
        'Symfony\Component\Console\Event\ConsoleErrorEvent',
        'Symfony\Component\Console\Event\ConsoleTerminateEvent',
    ];

    /**
     * 模块构建.
     *
     * @param string $moduleName
     *
     * @return array
     */
    public function run(string $moduleName): array
    {
        $oauthDb = $this->config->oauthDb->toArray();
        $this->di->setShared('oauthDb', function () use ($oauthDb) {
            return $db = new Mysql($oauthDb);
        });
        $this->di->setShared('eellyAcl', [
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

        $this->setModuleName($moduleName);

        return $this->buildModuleDir();
    }

    /**
     * 返回模块生成的目录信息.
     *
     * @return array
     */
    public function returnModuleDirInfo(): array
    {
        return [
            'moduleDir'     => $this->moduleDir,
            'moduleDirInfo' => $this->moduleDirInfo,
        ];
    }

    /**
     * 设置模块名.
     *
     * @param string $moduleName
     */
    private function setModuleName(string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    /**
     * 模块目录构建.
     *
     * @return array
     */
    private function buildModuleDir(): array
    {
        $moduleName = ucfirst($this->moduleName);
        $this->moduleDir = $this->baseDir.$moduleName;

        if (!is_dir($this->moduleDir)) {
            mkdir($this->moduleDir, 0755);
        }

        // 生成模块文件
        $this->buildModuleFile();
        // 生成模块子目录
        $this->buildChildDir();
        // 模块下model生成
        $this->buildModuleModel();
        // 生成api
        $this->buildModuleApi();
        // 生成模块配置文件
        $this->buildConfigDir();
        // 添加到acl库 TODO (此处不再进行处理,移步到 eellyTools module 命令内进行)
        // $this->insertModuleAndClient();

        return $this->returnModuleDirInfo();
    }

    /**
     * 构建模块文件.
     */
    private function buildModuleFile(): void
    {
        $filePath = $this->moduleDir.'/Module'.$this->fileExt;
        !file_exists($filePath) && file_put_contents($filePath, $this->getModuleFileCode());
    }

    /**
     * 获取模块文件code.
     *
     * @return string
     */
    private function getModuleFileCode(): string
    {
        $templates = file_get_contents($this->templateDir.'BaseTemplate.php');

        $namespace = $this->getNamespace(ucfirst($this->moduleName));
        $useNamespace = $this->getUseNamespace($this->moduleFileUserNamespace);
        $className = $this->getClassName('Module', 'AbstractModule');
        $properties = [
            'NAMESPACE' => [
                'type'      => 'const',
                'qualifier' => 'public',
                'value'     => '__NAMESPACE__',
            ],
            'NAMESPACE_DIR' => [
                'type'      => 'const',
                'qualifier' => 'public',
                'value'     => '__DIR__',
            ],
        ];
        $properties = $this->getClassProperties($properties);
        $body = $this->getClassBody();

        return sprintf($templates, $namespace, $useNamespace, $className, $properties, $body);
    }

    /**
     * 生成子目录.
     */
    private function buildChildDir(): void
    {
        $child = [
            'Model.Mysql',
            'Model.MongoDB',
            'Logic',
            'Validation',
            'Repository',
            'EventListener',
        ];

        foreach ($child as $dirName) {
            if (false !== strpos($dirName, '.')) {
                $dirInfo = explode('.', $dirName);
                $dirName = $dirInfo[1];
                $dirPath = $this->moduleDir.'/'.$dirInfo[0].'/'.$dirInfo[1];
            } else {
                $dirPath = $this->moduleDir.'/'.$dirName;
            }
            $this->moduleDirInfo[$dirName]['path'] = $dirPath;
            $this->moduleDirInfo[$dirName]['namespace'] = ltrim(str_replace('/', '\\', $dirPath), 'src/\\');
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }
        }
    }

    /**
     * 获取类的主体.
     *
     * @return string
     */
    private function getClassBody(): string
    {
        return $this->getTemplateFile('ModuleFileBody');
    }

    /**
     * 模块model生成.
     */
    private function buildModuleModel(): void
    {
        (new ModelFile($this->di))->run($this->moduleName, $this->moduleDirInfo);
    }

    /**
     * 模块配置文件生成.
     */
    private function buildConfigDir(): void
    {
        (new ConfigFile($this->di))->run($this->moduleName);
    }

    /**
     * 模块api生成.
     */
    private function buildModuleApi(): void
    {
        (new ApiFile($this->di))->run($this->moduleName, $this->moduleDirInfo['Logic']);
    }

    private function insertModuleAndClient(): void
    {
        $this->eellyAcl->addModule($this->moduleName);
        $this->eellyAcl->addModuleClient($this->moduleName);
        $this->eellyAcl->addRole($this->moduleName, null, $this->moduleName.'/*/*');
        $this->eellyAcl->addRoleClient($this->moduleName, $this->eellyAcl->getClientKeyByModuleName($this->moduleName));
    }
}
