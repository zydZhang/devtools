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

use Phalcon\Di\Injectable;
use Phalcon\DiInterface;

/**
 * 生成基础类
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class File extends Injectable
{
    /**
     * 基础目录.
     *
     * @var string
     */
    protected $baseDir = 'src/';

    /**
     * 模板目录.
     *
     * @var string
     */
    protected $templateDir = 'eelly/devtools/templates/';

    /**
     * 模板后缀
     *
     * @var string
     */
    protected $templateExt = 'Template';

    /**
     * 文件后缀
     *
     * @var string
     */
    protected $fileExt = '.php';

    public function __construct(DiInterface $dependencyInjector)
    {
        $this->setDI($dependencyInjector);
    }

    /**
     * 获取命名空间.
     *
     * @param string $namespace
     *
     * @return string
     */
    protected function getNamespace(string $namespace): string
    {
        $namespace = <<<EOF
namespace {$namespace};
EOF;

        return $namespace.PHP_EOL;
    }

    /**
     * 获取使用的命名空间.
     *
     * @param array $namespace
     *
     * @return string
     */
    protected function getUseNamespace(array $namespace): string
    {
        if (empty($namespace)) {
            return '';
        }

        $space = '';
        foreach ($namespace as $use) {
            $space .= "use {$use};".PHP_EOL;
        }

        return $space;
    }

    /**
     * 获取类名.
     *
     * @param string $className
     * @param string $extends
     * @param array  $implement
     *
     * @return string
     */
    protected function getClassName(string $className, string $extends = '', array $implement = []): string
    {
        $class = 'class '.$className;
        !empty($extends) && $class .= ' extends '.$extends;
        if (!empty($implement)) {
            $class .= ' implements ';
            foreach ($implement as $imple) {
                $class .= $imple.',';
            }
        }

        return rtrim($class, ',');
    }

    /**
     * 获取类的成员属性.
     *
     * @param array $properties
     *
     * @return string
     */
    protected function getClassProperties(array $properties): string
    {
        if (empty($properties)) {
            return '';
        }
        $propertiesStr = '';
        foreach ($properties as $propertie => $propertieInfo) {
            $tips = !empty($propertieInfo['tips']) ? $propertieInfo['tips'] : '';
            $valueStr = !empty($propertieInfo['value'])
            ? ' = '.(isset($propertieInfo['valueType']) && 'string' == $propertieInfo['valueType'] ? "'{$propertieInfo['value']}'" : $propertieInfo['value'])
            : '';

            if ('const' == $propertieInfo['type']) {
                $propertiesStr .= <<<EOF
$tips
    {$propertieInfo['qualifier']} const {$propertie}{$valueStr}; \n\n
EOF;
            } else {
                $propertiesStr .= <<<EOF
$tips
    {$propertieInfo['qualifier']} \${$propertie}{$valueStr}; \n\n
EOF;
            }
        }

        return $propertiesStr;
    }

    /**
     * 获取类成员属性注释.
     *
     * @param string $commentary
     * @param string $type
     *
     * @return string
     */
    protected function getPropertiesCommentary(string $commentary, string $type): string
    {
        return <<<EOF
    /**
     * {$commentary}
     *
     * @var {$type}
     */
EOF;
    }

    /**
     * 获取模板文件内容.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function getTemplateFile(string $fileName): string
    {
        $templateFile = $this->templateDir.$fileName.$this->templateExt.$this->fileExt;

        return file_exists($templateFile) ? file_get_contents($templateFile) : '';
    }
}
