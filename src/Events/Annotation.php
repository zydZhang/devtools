<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Eelly\DevTools\Events;

use Phalcon\Di\Injectable;
use Phalcon\Annotations\Adapter\Memory as MemoryAdapter;

class Annotation extends Injectable
{
    /**
     * 注解检验类型
     * 
     * @var array 
     */
    private $methodVerifyType = ['param', 'return', 'requestExample', 'returnExample', 'throws', 'author'];
    
    /**
     * 注解检验类型
     * 
     * @var array 
     */
    private $fittlerMethod = ['__construct'];
    
    /**
     * 配置信息
     * @var object 
     */
    private $config;
    
    /**
     * 路由信息
     * @var object 
     */
    private $route;
    
    /**
     * http请求
     * @var object 
     */
    private $request;
    
    /**
     * 反射对象
     * @var \ReflectionClass
     */
    private $reflector;
    
    /**
     * 注解对象
     * @var object
     */
    private $annotations;
    
    /**
     * 根目录路径
     * @var array
     */
    private $dirPath;
    
    /**
     * 模块类名
     * @var string
     */
    private $moduleClassName;
    
    public function __construct()
    {
        $this->config = $this->di->get("config");
        $this->route = $this->di->get("router");
        $this->dirPath = getcwd();
        $this->annotations = new MemoryAdapter();
    }
    
    /**
     * 注解校验
     * 
     * @throws \Exception
     */
    public function verify()
    { 
        $this->setModuleClassName();
        if(!empty($this->reflector)) {
            //验证方法规范
            $this->verifyMethod();
        }
    }
    
    /**
     * 设置模块名
     * 
     * @throws \Exception
     */
    public function setModuleClassName()
    {
        $moduleName = $this->route->getModuleName();
        $controllerName = $this->route->getControllerName();
        $actionName = $this->route->getActionName();
        if(empty($controllerName) || empty($actionName)) {
            return '';
        }
        $moduleName = '/'. ucfirst($moduleName.'/Logic/'.ucfirst($controllerName).'Logic');
        $fileName = $this->dirPath.'/src'.$moduleName.'.php';
        if(file_exists($fileName)) {
            $this->moduleClassName = str_replace('/', '\\', $moduleName);
            $this->reflector = new \ReflectionClass($this->moduleClassName);
        }
        return '';
    }
    
    /**
     * 验证方法相关规范
     * 
     * @throws \Exception
     */
    private function verifyMethod()
    {
        foreach ($this->reflector->getMethods() as $method) {
            if(in_array($method->name, $this->fittlerMethod)){
                continue;
            }
            if ('\\'.$method->class == $this->moduleClassName) {
                $this->fittlerParams($method);
                $annotations = $this->annotations->getMethod($this->moduleClassName, $method->name);
                foreach ($this->methodVerifyType as $value){
                    if (!$annotations->has($value)) {
                        $example = $this->annotationExample();
                        dump('您的备注缺少注解:@'. $value . '请补全, 类名:'. $this->moduleClassName."\n\n    例子:".$example.PHP_EOL);die();                  
                    }
                }
            }
        }
    }
    
    /**
     * 如果不用传参数时, param不用校验
     * 
     * @throws \Exception
     */
    public function fittlerParams($method)
    {
        if($method->getNumberOfParameters() == 0){
            $offset = array_search('param', $this->methodVerifyType);
            unset($this->methodVerifyType[$offset]);
        }
    }
    
    /**
     * 注释事例
     * 
     * @throws \Exception
     */
    public function annotationExample()
    {
        $example = '    
        /**
         * 修改店铺地址
         * 修改店铺的店铺地址和退货地址
         *
         * @param array  $addrData                 地址数据
         * @param int    $addrData["storeId"]      店铺id
         * @param int    $addrData["addrId"]       地址id
         * @param string $addrData["consignee"]    联系人姓名
         * @param string $addrData["gbCode"]       地区编码
         * @param string $addrData["zipcode"]      邮政编码
         * @param string $addrData["address"]      详细地址
         * @param string $addrData["mobile"]       手机号
         * @param string $addrData["deliveryType"] 送货类型1只送工作日2只双休日、假日3工作日、双休日或假日均可
         * @param int    $addrData["isDefault"]    是否为默认地址 0非默认 1默认
         * @param UidDTO $user                     登录用户信息
         *
         * @throws \Eelly\SDK\Store\Exception\StoreException
         *
         * @return bool 修改结果
         * @requestExample({"addrData":{"storeId":1,"addrId":1,"consignee":"联系人姓名","gdCode":"123","zipcode":"123","address":"详细地址","mobile":"123456789","deliveryType":1,"isDefault":1}})
         * @returnExample(true)
         *
         * @author xxxx<xxxx@eelly.net>
         *
         * @since 2017年08月10日
         */';
        return $example;
    }
}

