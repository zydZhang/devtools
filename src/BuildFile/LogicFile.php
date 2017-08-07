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

/**
 * Logic生成类.
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class LogicFile extends File
{
    /**
     * logic目录.
     *
     * @var string
     */
    protected $logicDir = '';

    /**
     * logic命名空间.
     *
     * @var string
     */
    protected $logicNamespace = '';

    /**
     * logic后缀名.
     *
     * @var string
     */
    protected $extName = 'Logic';

    /**
     * logic构建.
     *
     * @param array $dirInfo
     * @param array $tables
     */
    public function run(array $dirInfo, array $tables): void
    {
        $this->setDirInfo($dirInfo);
        $logics = $this->processLogicName($tables);
        $this->buildLogic($logics);
    }

    /**
     * 设置目录/命名空间.
     *
     * @param array $dirInfo
     */
    private function setDirInfo(array $dirInfo): void
    {
        $this->logicDir = $dirInfo['path'] ?? '';
        $this->logicNamespace = $dirInfo['namespace'] ?? '';
    }

    /**
     * 生成logic.
     *
     * @param array $logics
     */
    private function buildLogic(array $logics): void
    {
        foreach ($logics as $logic) {
            $fileName = $aggregateRoot = '';
            $aggregateArr = explode('_', $logic);
            $aggregate = array_reduce($aggregateArr, function ($str, $val) {
                return $str .= ucfirst($val);
            });

            if (3 <= count($aggregateArr)) {
                $aggregateRoot = array_reduce(array_slice($aggregateArr, 0, 2), function ($str, $val) {
                    return $str .= ucfirst($val);
                });
                $this->buildAggregateDir($aggregateRoot);
            }
            $this->buildLogicFile($aggregate, $aggregateRoot);
        }
    }

    /**
     * 生成聚合目录.
     *
     * @param string $aggregateRoot
     */
    private function buildAggregateDir(string $aggregateRoot): void
    {
        $dirParh = $this->logicDir.'/'.$aggregateRoot;
        !is_dir($dirParh) && mkdir($dirParh, 0755);
    }

    /**
     * 生成logic文件.
     *
     * @param string $aggregate
     * @param string $aggregateRoot
     */
    private function buildLogicFile(string $aggregate, string $aggregateRoot): void
    {
        $filePath = $this->logicDir.'/'.(!empty($aggregateRoot) ? $aggregateRoot.'/'.$aggregate.'Business' : $aggregate.$this->extName).$this->fileExt;
        if (!file_exists($filePath)) {
            $fileCode = !empty($aggregateRoot) ? $this->getBusinessLogicFileCode($aggregate, $aggregateRoot) : $this->getLogicFileCode($aggregate);
            $fp = fopen($filePath, 'w');
            fwrite($fp, $fileCode);
        }
    }

    /**
     * 获取logic文件的code.
     *
     * @param string $aggregate
     * @param string $aggregateRoot
     *
     * @return string
     */
    private function getLogicFileCode(string $aggregate): string
    {
        $templates = $this->getTemplateFile('Base');
        $namespace = $this->logicNamespace;
        $useNamespace = [
            'Eelly\\Mvc\\LogicController',
        ];
        $className = $aggregate.$this->extName;
        $extendsName = 'LogicController';
        $className = $this->getClassName($className, $extendsName);
        $namespace = $this->getNamespace($namespace);
        $useNamespace = $this->getUseNamespace($useNamespace);

        return sprintf($templates, $namespace, $useNamespace, $className, '', '');
    }

    /**
     * 获取BusinessLogic文件的code.
     *
     * @param string $aggregate
     * @param string $aggregateRoot
     *
     * @return string
     */
    private function getBusinessLogicFileCode(string $aggregate, string $aggregateRoot): string
    {
        $templates = $this->getTemplateFile('Base');
        $namespace = $this->logicNamespace.'\\'.$aggregateRoot;
        $useNamespace = [
            'Eelly\\Mvc\\User\Business',
        ];
        $className = $aggregate.'Business';
        $extendsName = 'Business';
        $className = $this->getClassName($className, $extendsName);
        $namespace = $this->getNamespace($namespace);
        $useNamespace = $this->getUseNamespace($useNamespace);

        return sprintf($templates, $namespace, $useNamespace, $className, '', '');
    }

    /**
     * 转换logic文件名称
     *
     * @param array $tables
     * @return array
     */
    private function processLogicName(array $tables): array
    {
        if(empty($tables)){
            return [];
        }

        $logics = [];
        foreach($tables as $table){
            $logics[] = substr_count($table, '_') ? ltrim(strchr($table, '_'), '_') : $table;
        }

        return $logics;
    }
}
