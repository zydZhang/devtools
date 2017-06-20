<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\DevTools\BuildFile;

/**
 * Logic生成类
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class LogicFile extends File
{

    /**
     * logic目录
     *
     * @var string
     */
    protected $logicDir = '';

    /**
     * logic命名空间
     *
     * @var string
     */
    protected $logicNamespace= '';

    /**
     * logic后缀名
     *
     * @var string
     */
    protected $extName = 'Logic';

    /**
     * logic构建
     *
     * @param array $dirInfo
     * @param array $tables
     */
    public function run(array $dirInfo, array $tables): void
    {
        $this->setDirInfo($dirInfo);
        $this->buildLogic($tables);
    }

    /**
     * 设置目录/命名空间
     *
     * @param array $dirInfo
     */
    private function setDirInfo(array $dirInfo): void
    {
        $this->logicDir = $dirInfo['path'] ?? '';
        $this->logicNamespace = $dirInfo['namespace'] ?? '';
    }

    /**
     * 生成logic
     * @param array $tables
     */
    private function buildLogic(array $tables): void
    {
        foreach ($tables as $table) {
            $fileName = $aggregateRoot = '';
            $aggregateArr = explode('_', $table);
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
     * 生成聚合目录
     *
     * @param string $aggregateRoot
     */
    private function buildAggregateDir(string $aggregateRoot): void
    {
        $dirParh = $this->logicDir . '/' . $aggregateRoot;
        !is_dir($dirParh) && mkdir($dirParh, 0755);
    }

    /**
     * 生成logic文件
     *
     * @param string $aggregate
     * @param string $aggregateRoot
     */
    private function buildLogicFile(string $aggregate, string $aggregateRoot): void
    {
        $filePath = $this->logicDir . '/' . (! empty($aggregateRoot) ? $aggregateRoot . '/' . $aggregate . 'Business' : $aggregate . $this->extName) . $this->fileExt;
        if (! file_exists($filePath)) {
            $fileCode = ! empty($aggregateRoot) ? $this->getBusinessLogicFileCode($aggregate, $aggregateRoot) : $this->getLogicFileCode($aggregate);
            $fp = fopen($filePath, 'w');
            fwrite($fp, $fileCode);
        }
    }

    /**
     * 获取logic文件的code
     *
     * @param string $aggregate
     * @param string $aggregateRoot
     * @return string
     */
    private function getLogicFileCode(string $aggregate): string
    {
        $templates = $this->getTemplateFile('Base');
        $namespace = $this->logicNamespace;
        $useNamespace = [
            'Eelly\\Mvc\\LogicController',
        ];
        $className = $aggregate . $this->extName;
        $extendsName = 'LogicController';
        $className = $this->getClassName($className, $extendsName);
        $namespace = $this->getNamespace($namespace);
        $useNamespace = $this->getUseNamespace($useNamespace);

        return sprintf($templates, $namespace, $useNamespace, $className, '' ,'');
    }

    /**
     * 获取BusinessLogic文件的code
     *
     * @param string $aggregate
     * @param string $aggregateRoot
     * @return string
     */
    private function getBusinessLogicFileCode(string $aggregate, string $aggregateRoot): string
    {
        $templates = $this->getTemplateFile('Base');
        $namespace = $this->logicNamespace . '\\' . $aggregateRoot;
        $useNamespace = [
            'Eelly\\Mvc\\User\Business'
        ];
        $className = $aggregate . 'Business';
        $extendsName = 'Business';
        $className = $this->getClassName($className, $extendsName);
        $namespace = $this->getNamespace($namespace);
        $useNamespace = $this->getUseNamespace($useNamespace);

        return sprintf($templates, $namespace, $useNamespace, $className, '', '');
    }
}