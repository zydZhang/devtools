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
use Eelly\DevTools\Traits\SDKDirTrait;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\DiInterface;
use Phalcon\Db;

/**
 * Api生成类.
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class ApiFile extends File
{
    use SDKDirTrait;

    /**
     * sdk目录.
     *
     * @var string
     */
    protected $sdkDir;

    /**
     * service目录.
     *
     * @var string
     */
    protected $serviceDir = '';

    /**
     * api目录.
     *
     * @var string
     */
    protected $apiDir = '';

    /**
     * sdk命名空间.
     *
     * @var string
     */
    protected $sdkNamespace = 'Eelly\\SDK\\';

    /**
     * service目录.
     *
     * @var string
     */
    protected $serviceNamespace = '';

    /**
     * api目录.
     *
     * @var string
     */
    protected $apiNamespace = '';

    /**
     * 导入的命名空间.
     *
     * @var array
     */
    protected $useNamespace = [];

    /**
     * 模块名称.
     *
     * @var string
     */
    protected $moduleName = '';

    /**
     * api名称.
     *
     * @var string
     */
    protected $apiName = '';

    /**
     * logic目录信息.
     */
    protected $logicDirInfo = [];

    /**
     * service名称.
     *
     * @var string
     */
    protected $serviceName = '';

    /**
     * 当前acl操作的Permission
     *
     * @var string
     */
    protected $currentPermission = '';

    /**
     * @param DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        parent::__construct($di);
        $this->sdkDir = rtrim($this->getEellySDKPath(), '/') . '/';
    }

    /**
     * api构建.
     *
     * @param string $moduleName
     * @param array  $logicDirInfo
     */
    public function run(string $moduleName, array $logicDirInfo): void
    {
        $this->setModuleName($moduleName)->setDir()->setNamespace()->setLogicDir($logicDirInfo)->buildApiFile();
    }

    /**
     * 设置模块名.
     *
     * @param string $moduleName
     *
     * @return self
     */
    public function setModuleName(string $moduleName): self
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * 设置目录信息.
     *
     * @return self
     */
    public function setDir(): self
    {
        $this->sdkDir .= ucfirst($this->moduleName);
        $this->serviceDir = $this->sdkDir.'/Service';
        $this->apiDir = $this->sdkDir.'/Api';

        return $this;
    }

    /**
     * 设置命名空间.
     *
     * @return self
     */
    public function setNamespace(): self
    {
        $this->serviceNamespace = $this->sdkNamespace.ucfirst($this->moduleName).'\\Service\\';
        $this->apiNamespace = $this->sdkNamespace.ucfirst($this->moduleName).'\\Api';

        return $this;
    }

    /**
     * 设置logic目录信息.
     *
     * @param array $logicDirInfo
     *
     * @return self
     */
    public function setLogicDir(array $logicDirInfo): self
    {
        $this->logicDirInfo = $logicDirInfo;

        return $this;
    }

    /**
     * 通过命令行构建api文件.
     *
     * @param string $apiName
     */
    public function buildApiFileInCli(string $apiName)
    {
        !$this->di->has('eellyAcl') && $this->initAcl();
        $this->apiName = ucfirst($apiName);
        $this->serviceName = $this->moduleName.'\\Logic\\'.ucfirst($apiName).'Logic';
        $templates = $this->getTemplateFile('Base');
        $apiPath = $this->apiDir.'/'.$this->apiName.$this->fileExt;
        $apiImplements = $this->apiName.'Interface';
        $apiFileCode = $this->getApiFileCode($apiImplements, $templates, false, true);
        !file_exists($apiPath) && file_put_contents($apiPath, $apiFileCode);
        $outStr = 'API======>' . $this->apiNamespace.'\\' . $apiName . '更新完成' . PHP_EOL;
        echo $outStr;
    }

    /**
     * 设置当前操作的permissionName
     *
     * @param string $permissionName
     * @return self
     */
    public function setCurrentPermission(string $permissionName): self
    {
        $this->currentPermission = $permissionName;

        return $this;
    }

    /**
     * 设置api导入的命名空间.
     *
     * @param array $namespace
     */
    private function setUseNamespace($namespace): void
    {
        if (is_string($namespace)) {
            !in_array($namespace, $this->useNamespace) && $this->useNamespace[] = $namespace;
        } elseif (is_array($namespace)) {
            $this->useNamespace = array_unique(array_merge($this->useNamespace, $namespace));
        }
    }

    /**
     * 生成api文件.
     *
     * @return string
     */
    private function buildApiFile(): void
    {
        if (!is_dir($this->serviceDir)) {
            mkdir($this->serviceDir, 0755, true);
            echo $this->serviceDir.'目录不存在,已自动生成service...'.PHP_EOL;
            $this->buildInterface();
        }

        !is_dir($this->apiDir) && mkdir($this->apiDir, 0755, true);
        $dirInfo = new \DirectoryIterator($this->serviceDir);
        $templates = $this->getTemplateFile('Base');
        foreach ($dirInfo as $file) {
            if (!$file->isDot() && $file->isFile()) {
                $apiImplements = strstr($file->getFilename(), '.', true);
                $this->apiName = strtr($apiImplements, ['Interface' => '']);
                $this->buildLogicFile($this->apiName, $apiImplements, $templates);
                $apiPath = $this->apiDir.'/'.$this->apiName.$this->fileExt;
                !is_file($apiPath) && file_put_contents($apiPath, $this->getApiFileCode($apiImplements, $templates));
            }
        }
    }

    /**
     * 初始化acl.
     */
    private function initAcl(): void
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
    }

    /**
     * 获取api文件code.
     *
     * @param string $apiImplements
     * @param string $templates
     * @param bool $isLogic
     * @param bool $isCliApi
     *
     * @return string
     */
    private function getApiFileCode(string $apiImplements, string $templates, bool $isLogic = false, bool $isCliApi = false): string
    {
        $this->initUseNamespace('Api');
        $interfaceName = $this->serviceNamespace.$apiImplements;
        $this->setUseNamespace($interfaceName);
        try{
            $interfaceReflection = new \ReflectionClass($interfaceName);
        }catch (\ReflectionException $e){
            exit($e->getMessage() . '，请确保Service内的Interface存在');
        }

        $methods = $interfaceReflection->getMethods();
        // 获取api需生成的方法code
        $classBody = $this->getApiMethodCode($methods, $isLogic, $isCliApi);
        $namespace = $this->getNamespace($this->apiNamespace);
        $useNamespace = $this->getUseNamespace($this->useNamespace);
        $className = $this->getClassName($this->apiName, '', [$apiImplements]);

        return sprintf($templates, $namespace, $useNamespace, $className, '', $classBody);
    }

    /**
     * 获取api方法code.
     *
     * @param array $methods
     * @param boole $isLogic
     * @param bool $isCliApi
     *
     * @return string
     */
    private function getApiMethodCode(array $methods, bool $isLogic = false, bool $isCliApi = false): string
    {
        $methodBuild = [];
        foreach ($methods as $method) {
            $methodDoc = $method->getDocComment();
            $methodParam = $this->getMethodParams($method->getParameters());
            // 在命令行下单独操作Permission时仅对当前操作的这个Permission进行数据库操作
            $isApi = $isCliApi && ('all' === $this->currentPermission ? true : $this->currentPermission === strtolower($method->getName()));
            if ($isLogic || $isApi) {
                $hashName = strtolower(str_replace(['\\', 'Logic/', 'Logic'], ['/'], $this->serviceName)).'/'.$method->getName();
                $descriptions = !empty($methodDoc) ? $this->getMethodDescription($methodDoc) : [];
                $this->addPermission($hashName, $methodParam, $descriptions);
            }

            $methodBuild[] = [
                'document' => $methodDoc,
                'modifier' => \Reflection::getModifierNames($method->getModifiers())[1],
                'name' => $method->getName(),
                'params' => $methodParam,
                'return' => ($method->getReturnType() instanceof \ReflectionType) ? $method->getReturnType()->getName() : ''
            ];
        }

        return $this->getMethodCode($methodBuild, $isLogic, $isCliApi);
    }

    /**
     * 获取方法参数.
     *
     * @param array $params
     *
     * @return array
     */
    private function getMethodParams(array $params): array
    {
        $methodParams = [];
        foreach ($params as $param) {
            $methodParams[] = [
                'name'          => $param->getName(),
                'position'      => $param->getPosition(),
                'type'          => ($param->getType() instanceof \ReflectionNamedType) ? $param->getType()->getName() : '',
                'hasDefaultVal' => $param->isDefaultValueAvailable(),
                'defaultVal'    => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : '',
            ];
        }

        return $methodParams;
    }

    /**
     * 获取方法code.
     *
     * @param array $methods
     * @param boole $isLogic
     * @param bool $isCliApi
     *
     * @return string
     */
    private function getMethodCode(array $methods, bool $isLogic = false, bool $isCliApi = false): string
    {
        $str = '';
        foreach ($methods as $method) {
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
        !$isLogic && $str .= $this->getInstanceCode();
        return $str;
    }

    /**
     * 获取方法参数的code.
     *
     * @param array $params
     *
     * @return string
     */
    private function getMethodParamCode(array $params): string
    {
        $str = '(';
        foreach ($params as $param) {
            !empty($param['type']) && $str .= $this->checkParamType($param['type']).' ';
            $str .= '$'.$param['name'];
            $param['hasDefaultVal'] && $str .= ' = '. (is_array($param['defaultVal']) ? $this->arrayConvertsString($param['defaultVal']): $this->valueConvertsString($param['defaultVal']));
            $str .= ', ';
        }

        return rtrim($str, ', ').')';
    }

    /**
     * 获取方法体code.
     *
     * @param string $methodName
     * @param array  $params
     *
     * @return string
     */
    private function getMethodBodyCode(string $methodName, array $params): string
    {
        $uri = strtolower($this->moduleName).'/'.strtolower($this->apiName);
        $args = '';
        if (!empty($params)) {
            $args = ', '.array_reduce($params, function ($str, $val) {
                return $str .= '$'.$val['name'].', ';
            });
            $args = rtrim((string) $args, ', ');
        }

        return <<<EOF
return EellyClient::request('{$uri}', '{$methodName}'{$args});
EOF;
    }

    /**
     * 验证参数类型.
     *
     * @param string $paramType
     *
     * @return string
     */
    private function checkParamType(string $paramType): string
    {
        if (false !== strpos($paramType, '\\')) {
            $this->setUseNamespace([$paramType]);
            $offset = strrpos($paramType, '\\');
            $paramType = substr($paramType, $offset + 1);
        }

        return $paramType;
    }

    /**
     * 生成logic文件.
     *
     * @param string $apiName
     * @param string $apiImplements
     * @param string $templates
     */
    private function buildLogicFile(string $apiName, string $apiImplements, string $templates): void
    {
        $className = $apiName.'Logic';
        $this->serviceName = $this->logicDirInfo['namespace'].'\\'.$className;
        $this->eellyAcl->addModuleService($this->serviceName, $this->moduleName);
        $classPath = $this->logicDirInfo['path'].'/'.$className.$this->fileExt;
        !file_exists($classPath) && file_put_contents($classPath, $this->getLogicFileCode($className, $apiImplements, $templates));
    }

    /**
     * 获取logic文件的code.
     *
     * @param string $className
     * @param string $apiImplements
     * @param string $templates
     *
     * @return string
     */
    private function getLogicFileCode(string $className, string $apiImplements, string $templates): string
    {
        $this->initUseNamespace('Logic');
        $interfaceName = $this->serviceNamespace.$apiImplements;
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
     * 初始化导入的命名空间.
     *
     * @param string $type
     */
    private function initUseNamespace(string $type): void
    {
        $this->useNamespace = 'Api' === $type ? ['Eelly\SDK\EellyClient'] : [];
    }

    /**
     * 获取方法描述.
     *
     * @param string $docComment
     *
     * @return array
     */
    private function getMethodDescription(string $docComment): array
    {
        if (empty($docComment)) {
            return [];
        }
        // 方法名和方法描述
        $methodDescription = $paramDescription = $requestExample = $returnExample = [];
        preg_match('/[\/*\s]+([^@\n]*)[*\s]+([^@\n]*)/', $docComment, $description);
        $methodDescription['methodName'] = $description[1] ?? '';
        $methodDescription['methodDescribe'] = $description[2] ?? '';
        // 参数描述
        preg_match_all('/@param.*\$[\w]*\s+([^\s][^\n\*]*)\n/U', $docComment, $paramArr);
        $methodDescription['paramDescribe'] = $paramArr[1] ?? [];
        // 请求参数示例
        preg_match('/@requestExample\((.*)\)/sU', $docComment, $requestExample);
        $methodDescription['requestExample'] = $requestExample[1] ?? '';
        $methodDescription['requestExample']= preg_replace(["/\s\*\s/", "/\s/"], '', $methodDescription['requestExample']);
        // 返回参数示例
        preg_match('/@returnExample\((.*)\)/sU', $docComment, $returnExample);
        $methodDescription['returnExample'] = $returnExample[1] ?? '';
        $methodDescription['returnExample']= preg_replace(["/\s\*\s/", "/\s/"], '', $methodDescription['returnExample']);
        // 返回参数和异常类型、描述
        preg_match_all('/@(return|throws)\s+([^\s]+)\s+([^\s\*]+[^\n]*)\s/', $docComment, $returnDescription);
        foreach($returnDescription[1] as $key => $val){
            $methodDescription['returnInfo'][] = [
                'type' => $val,
                'returnValue' => $returnDescription[2][$key] ?? '',
                'returnDescribe' => $returnDescription[3][$key] ?? '',
            ];
        }

        return $methodDescription;
    }

    /**
     * 添加到权限表.
     *
     * @param string $hashName
     * @param array $methodParam
     * @param array  $descriptions
     */
    private function addPermission(string $hashName, array $methodParam, array $descriptions): void
    {
        $requestData = $returnData = $permissionData = [];
        $isLogin = $this->checkPermissionIsLogin($methodParam);
        $permissionData = [
            'methodName' => $descriptions['methodName'] ?? '',
            'requestExample' => $descriptions['requestExample'] ?? '',
            'methodDescribe' => $descriptions['methodDescribe'] ?? '',
            'created_time' => time(),
            'isLogin' => (int)$isLogin,
        ];
        $this->eellyAcl->addPermission($hashName, $this->serviceName, $permissionData);
        $requestExample = json_decode($descriptions['requestExample'], true) ?: [];
        $returnExample = $descriptions['returnExample'];
        $paramExample = $this->getParamExample($methodParam, $requestExample);
        if(!empty($methodParam)){
            foreach ($methodParam as $paramId => $param) {
                $requestData[] = [
                    'data_type' => 2,
                    'param_id' => $paramId,
                    'param_type' => $param['type'],
                    'param_name' => $param['name'],
                    'param_example' => $paramExample[$paramId] ?? '',
                    'comment' => $descriptions['paramDescribe'][$paramId] ?? '',
                    'is_must' => (int)! $param['hasDefaultVal'],
                    'created_time' => time()
                ];
            }
            $this->eellyAcl->addPermissionRequest($requestData, $hashName);
        }

        if(isset($descriptions['returnInfo'])){
            foreach ($descriptions['returnInfo'] as $info){
                $dataType = 'return' == $info['type'] ? 1 : 2;
                $returnData[] = [
                    'return_type' => $info['returnValue'],
                    'data_type' => $dataType,
                    'comment' => $info['returnDescribe'],
                    'return_example' => 1 == $dataType ? $returnExample : $info['returnValue'],
                    'created_time' => time(),
                ];
            }
            $this->eellyAcl->addPermissionReturn($returnData, $hashName);
        }
    }

    /**
     * 生成service内的interface文件
     */
    private function buildInterface(): void
    {
        $statement = $this->di->getDb()->query('SHOW TABLES');
        $tables = $statement->fetchAll(Db::FETCH_COLUMN);
        $interfaces = [];
        // 转换interface名称
        foreach($tables as $table){
            $interfaceName = substr_count($table, '_') ? ltrim(strchr($table, '_'), '_') : $table;
            $interfaceNameArr = explode('_', $interfaceName);
            $interfaces[] = array_reduce($interfaceNameArr, function ($str, $val) {
                return $str .= ucfirst($val);
            });
        }

        $templates = $this->getTemplateFile('Base');
        foreach ($interfaces as $interface){
            $className = $interface . 'Interface';
            $classPath = $this->serviceDir . '/' . $className . $this->fileExt;
            if(file_exists($className)){
                continue;
            }
            $dtoNamespace = 'Eelly\DTO\\' . $interface .'DTO';
            $useNamespace = $this->getUseNamespace([$dtoNamespace]);
            $classBody = $this->getInterfaceInitializeCode($interface);
            $namespace = $this->sdkNamespace . ucfirst($this->moduleName) . '\\Service';
            $className = $this->getInterfaceName($className);
            $namespace = $this->getNamespace($namespace);

            file_put_contents($classPath, sprintf($templates, $namespace, $useNamespace, $className, '', $classBody));
        }
    }

    /**
     * 获取interface初始化的code
     *
     * @param string $interfaceName
     * @return string
     */
    private function getInterfaceInitializeCode(string $interfaceName): string
    {
        $getMethod = [
            'qualifier' => 'public',
            'params' => [
                ['type' => 'int', 'name' => '{InterfaceName}Id'],
            ],
            'return' => [
                'type' => '{InterfaceName}DTO',
            ],
        ];
        $addMethod = [
            'qualifier' => 'public',
            'params' => [
                ['type' => 'array', 'name' => 'data'],
            ],
            'return' => [
                'type' => 'bool',
            ],
        ];
        $updateMethod = [
            'qualifier' => 'public',
            'params' => [
                ['type' => 'int', 'name' => '{InterfaceName}Id'],
                ['type' => 'array', 'name' => 'data'],
            ],
            'return' => [
                'type' => 'bool',
            ],
        ];
        $deleteMethod = [
            'qualifier' => 'public',
            'params' => [
                ['type' => 'int', 'name' => '{InterfaceName}Id'],
            ],
            'return' => [
                'type' => 'bool',
            ],
        ];
        $listPageMethod = [
            'qualifier' => 'public',
            'params' => [
                ['type' => 'array', 'name' => 'condition', 'defaultValue' => '[]'],
                ['type' => 'int', 'name' => 'currentPage', 'defaultValue' => '1'],
                ['type' => 'int', 'name' => 'limit', 'defaultValue' => '10'],
            ],
            'return' => [
                'type' => 'array',
            ],
        ];
        $methodArr = [
            'get{InterfaceName}' => $getMethod,
            'add{InterfaceName}' => $addMethod,
            'update{InterfaceName}' => $updateMethod,
            'delete{InterfaceName}' => $deleteMethod,
            'list{InterfaceName}Page' => $listPageMethod,
        ];

        $methodDescribe = <<<EOF
    /**
     *
     * @author eellytools<localhost.shell@gmail.com>
     */\n
EOF;
        $initializeCode = '';
        foreach($methodArr as $methodName => $methodInfo){
            $qualifier = $methodInfo['qualifier'];
            $returnType = str_replace('{InterfaceName}', $interfaceName, $methodInfo['return']['type']);
            $params = $this->getInterfaceInitializeParams($interfaceName, $methodInfo['params']);

            $initializeCode .= $methodDescribe;
            $methodName = str_replace('{InterfaceName}', $interfaceName, $methodName);
            $methodStr = <<<EOF
    $qualifier function $methodName($params): $returnType;\n\n
EOF;
            $initializeCode .= $methodStr;
        }

        return $initializeCode;
    }

    /**
     * 获取interface初始化code内的参数
     *
     * @param string $interfaceName
     * @param array $params
     * @return string
     */
    private function getInterfaceInitializeParams(string $interfaceName, array $params): string
    {
        if(empty($params)){
            return '';
        }
        $paramStr = '';
        foreach($params as $param){
            $paramStr .= $param['type'] . ' $' . str_replace('{InterfaceName}', lcfirst($interfaceName), $param['name']) . (isset($param['defaultValue']) ? ' = ' . $param['defaultValue'] : '') . ', ';
        }

        return rtrim($paramStr, ', ');
    }

    /**
     * 获取参数的示例值
     *
     * @param array $methodParam
     * @param array $requestExample
     * @return array
     */
    private function getParamExample(array $methodParam, array $requestExample): array
    {
        if(empty($methodParam) || empty($requestExample)){
            return [];
        }
        $paramExample = [];
        foreach($methodParam as $param){
            $example = $requestExample[$param['position']] ?? ($requestExample[$param['name']] ?? '');
            is_array($example) && $example = json_encode($example);
            $paramExample[] = $example;
        }

        return $paramExample;
    }

    /**
     * 判断permission是否需要登录
     *
     * @param array $methodParam
     * @return bool
     */
    private function checkPermissionIsLogin(array $methodParam): bool
    {
        if(empty($methodParam)){
            return false;
        }
        $loginMark = [
            'Eelly\\DTO\\UidDTO',
        ];
        $paramType = array_column($methodParam, 'type');

        return (bool)array_intersect($loginMark, $paramType);
    }
}
