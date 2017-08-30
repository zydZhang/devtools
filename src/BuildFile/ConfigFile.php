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

class ConfigFile extends File
{
    /**
     * 配置文件目录.
     *
     * @var array
     */
    protected $configDir = [
        'dev'   => 'var/config/dev/',
//         'test'  => 'var/config/test/',
//         'local' => 'var/config/local/',
        'prod'  => 'var/config/prod/',
    ];

    /**
     * 需生成的配置文件.
     *
     * @var array
     */
    protected $configFile = [
        'annotations.php',
        'cache.php',
        'easemob.php',
        'fastdfs.php',
        'mongodb.php',
        'mysql.php',
        'amqp.php',
        'oauth2Client.php',
    ];

    /**
     * 模块配置构建.
     *
     * @param string $moduleName
     *
     * @return array
     */
    public function run(string $moduleName): void
    {
        $moduleName = strtolower($moduleName);
        foreach ($this->configDir as $dir) {
            $filePath = $dir.$moduleName.$this->fileExt;
            $this->buildModuleFile($filePath);
            $configDir = $dir.$moduleName;
            !is_dir($configDir) && mkdir($configDir, 0755, true);

            foreach ($this->configFile as $file) {
                $configFilePath = $configDir.'/'.$file;
                !file_exists($configFilePath) && file_put_contents($configFilePath, $this->getConfigFileCode(strstr($file, '.', true)));
            }
        }
    }

    /**
     * 生成模块配置入口文件.
     *
     * @param string $filePath
     */
    private function buildModuleFile(string $filePath): void
    {
        !file_exists($filePath) && file_put_contents($filePath, $this->getTemplateFile('ModuleConfig'));
    }

    /**
     * 获取配置文件内容.
     *
     * @param string $fileName
     *
     * @return string
     */
    private function getConfigFileCode(string $fileName): string
    {
        $fileName = ucfirst($fileName).'Config';

        return $this->getTemplateFile($fileName);
    }
}
