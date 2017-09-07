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

namespace Eelly\DevTools\Events;

use Phalcon\Di\Injectable;

/**
 * mySql验证类.
 */
class VerifySql extends Injectable
{
    /**
     * sql 规则.
     */
    private $sqlRule = [
        'likeVerify'    => ['/WHERE(.*)(\s+)LIKE(\s+)\'%([^%]*)%\'/i', 'SQL查询条件中LIKE不能同时使用左右%号：'],
        'allVerify'     => ['/SELECT(\s+)(((\`(\w+)\`)|(\w+))\.)?\*/i', 'SQL查询禁用*符号：'],
        'randVerify'    => ['/order\s+rand/i', 'SQL查询禁用rand排序：'],
        'regularVerify' => ['/where.+\sregexp\s+(\'|")/i', 'SQL查询禁用正则：'],
    ];
    
    /**
     * 需要过滤的表名
     * 
     * @var array 
     */
    private $fitterTable = ['INFORMATION_SCHEMA'];

    /**
     * sql连接池对象
     *
     * @param object $connection
     */
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * sql语句规则校验.
     *
     * @param string $sql
     */
    public function sqlVerify($sql)
    {
        //不校验含过滤表名的语句
        if($this->checkFittlerTable($sql)){
            return '';
        }
        //检验sql查询语句
        foreach ($this->sqlRule as $value) {
            if (preg_match($value[0], $sql)) {
                throw new \Exception($value[1].$sql);
            }
        }
        //校验join数量
        $this->joinVerify($sql);
        //如果是select 情况下 分析sql语句
        if (preg_match('/^SELECT/i', $sql)) {
            $this->explainSql($sql);
        }
    }

    /**
     * join数量校验.
     *
     * @param string $sql
     *
     * @throws \Exception
     */
    public function joinVerify($sql)
    {
        // 验证*查询
        if (preg_match('/ join /i', $sql, $matchs)) {
            if (count($matchs[0]) > 5) {
                throw new \Exception('SQL联表不能超过5个：'.$sql);
            }
        }
    }

    /**
     * sql语句性能分析.
     *
     * @param string $sql
     *
     * @throws \Exception
     */
    public function explainSql($sql)
    {
        $explainSql = 'explain '.$sql;
        $variables = $this->connection->getSqlVariables();
        if (!empty($variables)) {
            foreach ($variables as $key => $value) {
                $variables[':'.$key] = $value;
                unset($variables[$key]);
                $key = ':'.$key;
                is_string($value) && $variables[$key] = "'".$value."'";              
            }
            $explainSql = str_replace(array_keys($variables), array_values($variables), $explainSql);
        }
        $result = $this->connection->query($explainSql);
        $result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
        $explainResult = $result->fetchAll();
        //获取路由注解信息
        /*$router = $this->di->get("router");
        $nameSpace = $router->getNamespaceName();
        $controllerName = $router->getControllerName();
        $methodName = $router->getActionName();
        $controllerClass = $nameSpace.'\\'.ucfirst($controllerName).'Logic';*/
        $trace = debug_backtrace();
        foreach ($trace as $value) {
            if (strpos($value['class'], '\\Logic\\') !== false || strpos($value['class'], '\\Model\\') !== false) {
                $annotations = $this->annotations->getMethod(
                    $value['class'],
                    $value['function']
                );
                if ($annotations->has('badSql')) {
                    return true;
                }
            }
        }
        if (!empty($explainResult)) {
            foreach ($explainResult as $key => $value) {
                if (isset($value['type']) && strtoupper($value['type']) == 'ALL') {
                    throw new \Exception('你的SQL有点问题,是不是应该优化优化,sql语句:'.$sql.' 类型:'.$value['type']);
                }
            }
        }
    }
    
    /**
     * 判断是否含不校验的表
     *
     * @param string $sql
     *
     * @throws \Exception
     */
    private function checkFittlerTable($sql)
    {
        foreach($this->fitterTable as $value){
            if(strpos($sql, $value) > 0){
                return true;
            }
        }
    }
}
