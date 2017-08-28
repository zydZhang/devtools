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
 * phpunit生成类.
 *
 * @author eellytools<localhost.shell@gmail.com>
 */
class PhpunitFile extends File
{
    /**
     * 模块名称.
     *
     * @var string
     */
    protected $moduleName = '';

    /**
     * logic目录
     *
     * @var string
     */
    protected $logicPath = 'src/{moduleName}/Logic';

    /**
     * logic命名空间
     *
     * @var string
     */
    protected $logicNamespace = '{moduleName}\\Logic';

    /**
     * 单元测试目录
     *
     * @var string
     */
    protected $phpunitDir = 'tests/api/src/{moduleName}';

    /**
     * 单元测试类后缀
     *
     * @var string
     */
    protected $phpunitExt = 'Test';

    /**
     * phpunit构建.
     *
     * @param string $moduleName
     */
    public function run(string $moduleName): void
    {
        $this->moduleName = $moduleName;
        $this->buildPhpunitFile();
    }

    private function buildPhpunitFile(): void
    {
        $this->phpunitDir = strtr($this->phpunitDir, ['{moduleName}' => ucfirst($this->moduleName)]) . '/Logic/';
        !is_dir($this->phpunitDir) && mkdir($this->phpunitDir, 0755, true);
        $logicFiles = $this->getLogicFile();
        foreach($logicFiles as $logicFile){
            $templates = $this->getTemplateFile('Base');
            $className = $logicFile . $this->phpunitExt;
            $phpunitPath = $this->phpunitDir . $className . $this->fileExt;
            !file_exists($phpunitPath) && file_put_contents($phpunitPath, $this->getPhpunitCode($className, $templates));
        }
    }

    private function getLogicFile(): array
    {
        $moduleName = ucfirst($this->moduleName);
        $logicFiles = [];
        $logicPath = strtr($this->logicPath, ['{moduleName}' => $moduleName]);
        $this->logicNamespace = strtr($this->logicNamespace, ['{moduleName}' => $moduleName]);
        /** @var \Phalcon\Loader $loader */
        $loader = $this->di->get('loader');
        $loader->registerNamespaces([
            $moduleName => 'src/' . $moduleName,
        ]);
        $loader->register();

        $dirInfo = new \DirectoryIterator($logicPath);
        foreach($dirInfo as $file){
            if (!$file->isDot() && $file->isFile()) {
                $logicFiles[] = strchr($file->getFilename(), '.', true);
            }
        }

        return $logicFiles;
    }

    private function getClassMethods(string $logicFile): array
    {
        $thisMehods = [];
        $className = $this->logicNamespace . '\\' . $logicFile;
        $logicInfo = new \ReflectionClass($className);
        $methods = $logicInfo->getMethods();
        foreach($methods as $method){
            if(!($method->class === $className)){
                continue;
            }
            $thisMehods[] = lcfirst($this->phpunitExt) . ucfirst($method->name);
        }

        return $thisMehods;
    }

    private function getPhpunitCode(string $className, string $templates): string
    {
        $logicFile = strtr($className, [$this->phpunitExt => '']);
        $methods = $this->getClassMethods($logicFile);
        $classBody = $this->getPhpunitMethodCode($methods) . $this->getTestLogicCode($className);
        $namespace = $this->getNamespace($this->logicNamespace);
        $useNamespaceArr = [
            'Eelly\Test\UnitTestCase',
        ];
        $useNamespace = $this->getUseNamespace($useNamespaceArr);
        $className = $this->getClassName($className, 'UnitTestCase');

        return sprintf($templates, $namespace, $useNamespace, $className, '', $classBody);
    }

    private function getPhpunitMethodCode(array $methods): string
    {
        if(empty($methods)){
            return '';
        }

        $str = '';
        foreach($methods as $method){
            $str .= <<<EOF
    /**
     * @author eellytools<localhost.shell@gmail.com>
     */
    public function {$method}(): void
    {
        \$logic = \$this->getLogic();
        //your test code
    }\n\n
EOF;
        }

        return $str;
    }

    private function getTestLogicCode(string $className): string
    {
        $className = strtr($className, [$this->phpunitExt => '']);
        $str = <<<EOF
    /**
     * @author eellytools<localhost.shell@gmail.com>
     * @return {$className}
     */
    private function getLogic()
    {
        \$logic = \$this->getDI()->getShared({$className}::class);

        return \$logic;
    }
EOF;

        return $str;
    }

}