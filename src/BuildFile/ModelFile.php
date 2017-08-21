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

use Phalcon\Db;
use Phalcon\Db\Adapter\Pdo\Mysql;

/**
 * Model生成类.
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class ModelFile extends File
{
    /**
     * model目录.
     *
     * @var string
     */
    protected $modelDir = '';

    /**
     * model命名空间.
     *
     * @var string
     */
    protected $modelNamespace = '';

    /**
     * model后缀
     *
     * @var string
     */
    protected $extName = '';

    /**
     * model构建.
     *
     * @param string $moduleName
     * @param string $dirInfo
     *
     * @return array
     */
    public function run(string $moduleName, array $dirInfo): void
    {
        $config = $this->config;
        $dbName = $config->dbPrefix ? $config->dbPrefix.strtolower($moduleName) : strtolower($moduleName);
        $this->setDirInfo($dirInfo['Mysql']);

        try {
            $db = new Mysql([
                'host' => $config->dbHost,
                'username' => $config->dbUser,
                'password' => $config->dbPass,
                'dbname' => $dbName,
                'port' => $config->dbPort,
                'charset' => $config->dbCharset
            ]);
        } catch (\PDOException $e) {
            $this->unlinkFile($this->baseDir . '/' . ucfirst($moduleName));
            exit($dbName . '生成Model失败,' . $e->getMessage() . ',检查数据库配置是否正确' . PHP_EOL . $dbName . '模块构建失败');
        }
        $this->di->setShared('db', $db);

        $tables = $this->getTables();
        if (empty($tables)) {
            echo $moduleName.'模块的表不存在,生成Model失败'.PHP_EOL;
        } else {
            $config->beforeLogic && (new LogicFile($this->di))->run($moduleName, $dirInfo['Logic'], $tables);
            foreach ($tables as $table) {
                $modelName = $this->getModelNameBytableName($table);
                $filePath = $this->modelDir.'/'.$modelName.$this->fileExt;
                !file_exists($filePath) && file_put_contents($filePath, $this->getModelFileCode($modelName, $this->getProperties($dbName, $table)));
            }
        }
    }

    /**
     * 设置模块目录信息.
     *
     * @param array $dirInfo
     */
    public function setDirInfo(array $dirInfo): void
    {
        $this->modelDir = $dirInfo['path'] ?? '';
        $this->modelNamespace = $dirInfo['namespace'] ?? '';
    }

    /**
     * 获取模型文件code.
     *
     * @param string $modelName
     *
     * @return string
     */
    private function getModelFileCode(string $modelName, array $properties): string
    {
        $templates = $this->getTemplateFile('Base');
        $namespace = $this->getNamespace($this->modelNamespace);
        $useNamespace = $this->getUseNamespace(['Eelly\Mvc\Model']);
        $className = $this->getClassName($modelName, 'Model');
        $pk = '';
        foreach ($properties as $pVal) {
            if ('PRI' == $pVal['COLUMN_KEY']) {
                $pk = $pVal['COLUMN_NAME'];
            }
            $p[$pVal['COLUMN_NAME']] = [
                'type'      => 'general',
                'qualifier' => 'public',
                'tips'      => $this->getCommentary($pVal),
            ];
        }
        !empty($pk) && $p['pk'] = [
            'type'      => 'general',
            'qualifier' => 'protected',
            'value'     => $pk,
            'valueType' => 'string',
            'tips'      => $this->getPropertiesCommentary('主键', 'int'),
        ];
        $p['tableName'] = [
            'type'      => 'general',
            'qualifier' => 'protected',
            'value'     => $this->getTableNameByModelName($modelName),
            'valueType' => 'string',
            'tips'      => $this->getPropertiesCommentary('表名', 'string'),
        ];
        $fields = implode(',', array_column($properties, 'COLUMN_NAME'));
        $fieldsValue = '['.PHP_EOL.str_repeat(' ', 8)."'base' => '{$fields}',".PHP_EOL.str_repeat(' ', 4).']';
        $p['FIELD_SCOPE'] = [
            'type'      => 'const',
            'qualifier' => 'protected',
            'value'     => $fieldsValue,
            'tips'      => $this->getPropertiesCommentary('字段空间', 'array'),
        ];

        $properties = $this->getClassProperties($p);

        return sprintf($templates, $namespace, $useNamespace, $className, $properties, '');
    }

    /**
     * 获取模块表.
     *
     * @return array
     */
    private function getTables(): array
    {
        $statement = $this->di->getDb()->query('SHOW TABLES');
        $tables = $statement->fetchAll(Db::FETCH_COLUMN);

        return $tables;
    }

    /**
     * 获取表字段.
     *
     * @param string $dbName
     * @param string $tableName
     *
     * @return array
     */
    private function getProperties(string $dbName, string $tableName): array
    {
        $statement = $this->di->getDb()->query("SELECT COLUMN_NAME,DATA_TYPE,COLUMN_KEY,COLUMN_COMMENT FROM
            information_schema. COLUMNS WHERE table_schema = '{$dbName}' AND table_name = '{$tableName}';");
        $properties = $statement->fetchAll(Db::FETCH_ASSOC);

        return $properties;
    }

    /**
     * 字段注释.
     *
     * @param array $properties
     *
     * @return string
     */
    private function getCommentary(array $properties): string
    {
        $type = 'unknown';
        if (preg_match('/char|text/', $properties['DATA_TYPE'])) {
            $type = 'string';
        } elseif (preg_match('/int/', $properties['DATA_TYPE'])) {
            $type = 'int';
        } elseif (preg_match('/decimal|float|double/', $properties['DATA_TYPE'])) {
            $type = 'float';
        }

        return $this->getPropertiesCommentary($properties['COLUMN_COMMENT'], $type);
    }

    /**
     * 通过表名获取模块名.
     *
     * @param string $tableName
     *
     * @return string
     */
    private function getModelNameBytableName(string $tableName): string
    {
        $modelName = '';
        if (false !== strpos($tableName, '_')) {
            $tableArr = explode('_', $tableName);
            $modelName = array_reduce($tableArr, function ($str, $val) {
                return $str .= ucfirst($val);
            });
        } else {
            $modelName = ucfirst($tableName);
        }

        return $modelName.$this->extName;
    }

    /**
     * 通过模型名获取表名.
     *
     * @param string $modelName
     *
     * @return string
     */
    private function getTableNameByModelName(string $modelName): string
    {
        $tableName = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_'.strtolower($matches[0]);
        }, strtr($modelName, [$this->extName => '']));

        return ltrim($tableName, '_');
    }
}
