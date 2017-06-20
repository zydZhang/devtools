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
 * Api生成类
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class ApiFile extends File
{
    /**
     * sdk目录
     *
     * @var string
     */
    protected $sdkDir = 'eelly/eelly-sdk-php/src/';

    /**
     * service目录
     *
     * @var string
     */
    protected $serviceDir=  '';

    /**
     * api目录
     *
     * @var string
     */
    protected $apiDir = '';

    /**
     * sdk命名空间
     *
     * @var string
     */
    protected $sdkNamespace = 'Eelly\\SDK\\';

    /**
     * service目录
     *
     * @var string
     */
    protected $serviceNamespace = '';

    /**
     * api目录
     *
     * @var string
     */
    protected $apiNamespace = '';


    /**
     * 导入的命名空间
     *
     * @var array
     */
    protected $useNamespace = [];


    /**
     * 模块名称
     *
     * @var string
     */
    protected $moduleName = '';

    /**
     * api名称
     *
     * @var string
     */
    protected $apiName = '';

    /**
     * logic目录信息
     */
    protected $logicDirInfo = [];

    /**
     * service名称
     *
     * @var string
     */
    protected $serviceName = '';

    /**
     * api构建
     *
     * @param string $moduleName
     * @param array  $logicDirInfo
     */
    public function run(string $moduleName, array $logicDirInfo): void
    {
        $this->moduleName = $moduleName;
        $this->sdkDir .= ucfirst($this->moduleName);
        $this->serviceDir = $this->sdkDir . '/Service';
        $this->apiDir = $this->sdkDir . '/Api';
        $this->serviceNamespace = $this->sdkNamespace . ucfirst($this->moduleName) . '\\Service\\';
        $this->apiNamespace = $this->sdkNamespace . ucfirst($this->moduleName) . '\\Api';
        $this->logicDirInfo = $logicDirInfo;

        if(!is_dir($this->apiDir)){
            mkdir($this->apiDir, 0755, true);
        }
        $this->buildApiFile();
    }

    /**
     * 设置api导入的命名空间
     *
     * @param array $namespace
     */
    private function setUseNamespace($namespace): void
    {
        if(is_string($namespace)){
            !in_array($namespace, $this->useNamespace) && $this->useNamespace[] = $namespace;
        }elseif(is_array($namespace)){
            $this->useNamespace = array_unique(array_merge($this->useNamespace, $namespace));
        }
    }

    /**
     * 生成api文件
     *
     * @return string
     */
    private function buildApiFile()
    {
        if(!is_dir($this->serviceDir)){
            echo $this->serviceDir . '目录不存在,生成Api失败...' . PHP_EOL;
            return '';
        }

        $dirInfo = new \DirectoryIterator($this->serviceDir);
        $templates = $this->getTemplateFile('Base');
        foreach($dirInfo as $file){
            if(!$file->isDot() && $file->isFile()){
                $apiImplements = strchr($file->getFilename(), '.', true);
                $this->apiName = strtr($apiImplements, ['Interface' => '']);
                $this->buildLogicFile($this->apiName, $apiImplements, $templates);
                $apiPatn = $this->apiDir . '/' . $this->apiName . $this->fileExt;
                !is_file($apiPatn) && file_put_contents($apiPatn, $this->getApiFileCode($apiImplements, $templates));
            }
        }
    }

    /**
     * 获取api文件code
     *
     * @param string $apiImplements
     * @param string $templates
     * @return string
     */
    private function getApiFileCode(string $apiImplements, string $templates): string
    {
        $this->initUseNamespace('Api');
        $interfaceName = $this->serviceNamespace . $apiImplements;
        $this->setUseNamespace($interfaceName);
        $interfaceReflection = new \ReflectionClass($interfaceName);
        $methods = $interfaceReflection->getMethods();
        // 获取api需生成的方法code
        $classBody = $this->getApiMethodCode($methods);
        $namespace = $this->getNamespace($this->apiNamespace);
        $useNamespace = $this->getUseNamespace($this->useNamespace);
        $className = $this->getClassName($this->apiName, '', [$apiImplements]);

        return sprintf($templates, $namespace, $useNamespace, $className, '', $classBody);
    }

    /**
     * 获取api方法code
     *
     * @param array $methods
     * @param boole $isLogic
     * @return string
     */
    private function getApiMethodCode(array $methods, bool $isLogic = false): string
    {
        $methodBuild = [];
        foreach($methods as $method){
            $methodDoc = $method->getDocComment();
            if($isLogic){
                $hashName = strtolower(str_replace(['\\', 'Logic/', 'Logic'], ['/'], $this->serviceName)). '/' . $method->getName();
                $descriptions = !empty($methodDoc) ? $this->getMethodDescription($methodDoc) : [];
                $this->addPermission($hashName, $descriptions);
                //$this->eellyAcl->addPermission($descriptions['method'], $hashName, $this->serviceName);
            }

            $methodBuild[] = [
                'document' => $methodDoc,
                'modifier' => \Reflection::getModifierNames($method->getModifiers())[1],
                'name' => $method->getName(),
                'params' => $this->getMethodParams($method->getParameters()),
                'return' => ($method->getReturnType() instanceof \ReflectionType) ? $method->getReturnType()->getName() : '',
            ];
        }

        return $this->getMethodCode($methodBuild, $isLogic);
    }

    /**
     * 获取方法参数
     *
     * @param array $params
     * @return array
     */
    private function getMethodParams(array $params): array
    {
        $methodParams = [];
        foreach($params as $param){
            $methodParams[] = [
                'name' => $param->getName(),
                'position'=> $param->getPosition(),
                'type' => ($param->getType() instanceof \ReflectionNamedType) ? $param->getType()->getName() : '',
                'hasDefaultVal' => $param->isDefaultValueAvailable(),
                'defaultVal' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : '',
            ];
        }

        return $methodParams;
    }

    /**
     * 获取方法code
     *
     * @param array $methods
     * @param boole $isLogic
     * @return string
     */
    private function getMethodCode(array $methods, bool $isLogic): string
    {
        $str = '';
        foreach($methods as $method){
            $paramCode = $this->getMethodParamCode($method['params']);
            $returnType = !empty($method['return']) ? $this->checkParamType($method['return']) : 'void';
            $methodBody = $isLogic ? '' : $this->getMethodBodyCode($method['name'], $method['params']);
            $str .= <<<EOF
    {$method['document']}
    {$method['modifier']} function {$method['name']}{$paramCode}: {$returnType}
    {
        {$methodBody}
    }\n\n
EOF;
        }
        return $str;

    }

    /**
     * 获取方法参数的code
     *
     * @param array $params
     * @return string
     */
    private function getMethodParamCode(array $params): string
    {
        $str = '(';
        foreach($params as $param){
            !empty($param['type']) && $str .= $this->checkParamType($param['type']) . ' ';
            $str .= '$' . $param['name'];
            $param['hasDefaultVal'] && $str .= ' = ' . $param['defaultVal'];
            $str .= ',';
        }

        return rtrim($str, ',') . ')';
    }

    /**
     * 获取方法体code
     *
     * @param string $methodName
     * @param array $params
     * @return string
     */
    private function getMethodBodyCode(string $methodName, array $params): string
    {
        $uri = strtolower($this->moduleName) . '/' . strtolower($this->apiName);
        $args = '';
        if(!empty($params)){
            $args = ', ' . array_reduce($params, function($str, $val){
                return $str .= '$' . $val['name'] . ', ';
            });
            $args = rtrim((string)$args, ', ');
        }
        return <<<EOF
return EellyClient::request('{$uri}', '{$methodName}'{$args});
EOF;
    }

    /**
     * 验证参数类型
     *
     * @param string $paramType
     * @return string
     */
    private function checkParamType(string $paramType): string
    {
        if(false !== strpos($paramType, '\\')){
            $this->setUseNamespace([$paramType]);
            $offset = strrpos($paramType, '\\');
            $paramType = substr($paramType, $offset + 1);
        }

        return $paramType;
    }

    /**
     * 生成logic文件
     *
     * @param string $apiName
     * @param string $apiImplements
     * @param string $templates
     */
    private function buildLogicFile(string $apiName, string $apiImplements, string $templates): void
    {
        $className = $apiName . 'Logic';
        $this->serviceName = $this->logicDirInfo['namespace'] . '\\' . $className;
        $this->eellyAcl->addModuleService($this->serviceName, $this->moduleName);
        $classPath = $this->logicDirInfo['path'] . '/' . $className . $this->fileExt;
        !file_exists($classPath) && file_put_contents($classPath, $this->getLogicFileCode($className, $apiImplements, $templates));
    }

    /**
     * 获取logic文件的code
     *
     * @param string $className
     * @param string $apiImplements
     * @param string $templates
     * @return string
     */
    private function getLogicFileCode(string $className, string $apiImplements, string $templates): string
    {
        $this->initUseNamespace('Logic');
        $interfaceName = $this->serviceNamespace . $apiImplements;
        $this->setUseNamespace([$interfaceName, 'Eelly\\Mvc\\LogicController']);
        $interfaceReflection = new \ReflectionClass($interfaceName);
        $methods = $interfaceReflection->getMethods();
        // 获取api需生成的方法code
        $classBody = $this->getApiMethodCode($methods, true);
        $namespace = $this->getNamespace($this->logicDirInfo['namespace']);
        $useNamespace = $this->getUseNamespace($this->useNamespace);
        $className = $this->getClassName($className, 'LogicController', [$apiImplements]);

        return sprintf($templates, $namespace, $useNamespace, $className, '', $classBody);
    }

    /**
     * 初始化导入的命名空间
     *
     * @param string $type
     */
    private function initUseNamespace(string $type): void
    {
        $this->useNamespace = 'Api' === $type ? ['Eelly\SDK\EellyClient'] : [];
    }

    /**
     * 获取方法描述
     *
     * @param string $docComment
     * @return array
     */
    private function getMethodDescription(string $docComment): array
    {
        if(empty($docComment)){
            return '';
        }
        $methodDescription = $paramDescription = $paramArr = $returnArr = [];
        $description= strchr($docComment, '.', true);
        $methodDescription['method'] = !empty($description) ? strtr($description, ['/' => '', PHP_EOL => '', '*' => '', ' ' => '']) : '';

        preg_match_all('/@param.*/', $docComment, $paramArr);
        !empty($paramArr[0]) && array_walk($paramArr[0], function($val) use(&$paramDescription){
            $paramDescription[] = explode(' ', $val);
        });
        $methodDescription['param'] = $paramDescription;

        preg_match('/@return\s+.*/', $docComment, $returnArr);
        isset($returnArr[0]) && $methodDescription['return']['dto'] = strchr($returnArr[0], ' ');

        preg_match('/@returnExample\((.*)\)/', $docComment, $returnArr);
        $methodDescription['return']['example'] = $returnArr[1] ?? '';


        return $methodDescription;
    }

    /**
     * 添加到权限表
     *
     * @param string $hashName
     * @param array $descriptions
     */
    private function addPermission(string $hashName, array $descriptions): void
    {
        $requestData = $returnData = [];
        $this->eellyAcl->addPermission($descriptions['method'] ?? '', $hashName, $this->serviceName);

        if(isset($descriptions['param'])){
            foreach ($descriptions['param'] as $paramId => $param) {
                $requestData[] = [
                    'param_id' => $paramId,
                    'type' => $param[1] ?? '',
                    'comment' => $param[3] ?? '',
                    'created_time' => time()
                ];
            }
        }
        $this->eellyAcl->addPermissionRequest($requestData, $hashName);

        $returnData = [
            'dto_name' => $descriptions['return']['dto'] ?? '',
            'return_example' => $descriptions['return']['example'] ?? '',
            'created_time' => time(),
        ];
        $this->eellyAcl->addPermissionReturn($returnData, $hashName);
    }


}