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

namespace Eelly\DevTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Phalcon\Annotations\Adapter\Memory;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;

class ReturnExplainCommand extends BaseCommand
{
    private $annotations = [
        'example' => 'returnExample',
        'explain' => 'explain',
    ];

    private $parameterType = [
        301 => 'int',
        302 => 'double',
        303 => 'string',
        304 => 'null',
        305 => 'bool',
        306 => 'bool',
        308 => 'array'
    ];

    /**
     * 配置命令
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName('return-explain')
            ->setDescription('生成方法的返回数据说明')
            ->setHelp('return-explain goods:goods getStoreAddress')
            ->addArgument('interfaceName', InputArgument::REQUIRED, '模块名:接口名')
            ->addArgument('methodName',InputArgument::REQUIRED, '方法名');
    }

    /**
     * 命令执行的逻辑
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interfaceName = $input->getArgument('interfaceName');
        if(false === strpos($interfaceName, ':')){
            throw new InvalidArgumentException('interfaceName error');
        }
        list($moduleName, $interfaceName) = explode(':', $interfaceName);
        $methodName = $input->getArgument('methodName');
        $this->setMethodNewDocument($moduleName, $interfaceName, $methodName);
        $output->writeln('生成完成');
    }

    /**
     * 获取returnexample字符串
     *
     * @param string $moduleName 模块名
     * @param string $interfaceName 接口名
     * @param string $methodName 方法名称
     * @throws RuntimeException
     * @return string
     * @author wangjiang<wangjiang@eelly.net>
     * 2017年8月24日
     */
    private function getMethodReturnExample(\Phalcon\Annotations\Collection $reader): string
    {
        if(!$reader->has($this->annotations['example'])){
            throw new RuntimeException('not found annotation:' . $this->annotations['example']);
        }

        $returnExample = $reader->get($this->annotations['example'])->getArgument(0);
        if(!is_array($returnExample)){
            throw new RuntimeException('return example not json');
        }

        /** @var \Phalcon\Annotations\Annotation $example */
        $example = $reader->get($this->annotations['example']);
        $arguments = $example->getExprArguments();
        // 区分{}和[]json格式的数据
        $arguments = $arguments[0]['expr']['items'];
        $exprArguments = $this->getExprArguments($arguments);
        $returnFields = [];
        $maxKey = '';
        foreach($exprArguments as $key => $val){
            $key = preg_replace_callback('/_(\w*)/', function($match){
                return sprintf('[%s]', $match[1]);
            }, $key);
            $returnFields[$key] = $val;
            empty($maxKey) && $maxKey = $key;
            strlen($maxKey) < strlen($key) && $maxKey = $key;
        }

        $max = strlen($maxKey) + 1;
        $returnExampleStr = sprintf('%-s*','') . ' ### 返回数据说明' . PHP_EOL;
        $returnExampleStr .= sprintf('%-5s*','') . PHP_EOL;
        $returnExampleStr .= sprintf('%-5s* %-s|%-s|%-s', '', '字段', '类型', '说明') . PHP_EOL;
        $returnExampleStr .= sprintf('%-5s* %s|%s|%s', '', str_repeat('-', $max), str_repeat('-', 7),str_repeat('-', 14)) . PHP_EOL;
        foreach($returnFields as $field => $fieldType){
            $returnExampleStr .= sprintf('%-5s* %-' . $max . 's|%-7s|', '', $field, $fieldType) . PHP_EOL;
        }

        return $returnExampleStr;
    }

    /**
     * 设置方法的返回数据说明文档
     *
     * @param string $moduleName
     * @param string $interfaceName
     * @param string $methodName
     * @throws RuntimeException
     */
    private function setMethodNewDocument(string $moduleName, string $interfaceName, string $methodName): void
    {
        $className = sprintf('Eelly\\SDK\\%s\\Service\\%sInterface', ucfirst($moduleName), ucfirst($interfaceName));
        $di = $this->getDI();
        /** @var Memory $annotations */
        $annotations = $di->getShared(Memory::class);
        $reader = $annotations->getMethod($className, $methodName);
        if(!$reader->has($this->annotations['explain'])){
            throw new RuntimeException('not found annotation:' . $this->annotations['explain']);
        }

        $methodReflection = new \ReflectionMethod($className, $methodName);
        $filePath = $methodReflection->getFileName();
        $methodDoc = $methodReflection->getDocComment();
        $returnExplain = $this->getMethodReturnExample($reader) . sprintf('%-5s*', '');
        $newMethodDoc = preg_replace('/\*\s*@explain/', $returnExplain, $methodDoc);
        $newFileContent = str_replace($methodDoc, $newMethodDoc, file_get_contents($filePath));
        file_put_contents($filePath, $newFileContent);
    }

    /**
     * 递归获取参数类型
     *
     * @param array $arguments
     * @param string $parentName
     * @return array
     */
    private function getExprArguments(array $arguments, string $parentName = ''): array
    {
        if(empty($arguments)){
            return [];
        }
        $exprs = [];
        foreach($arguments as $argument){
            if(!isset($argument['name'])){
                $exprs += $this->getExprArguments($argument['expr']['items'], $parentName);
                continue;
            }

            $name = $parentName . $argument['name'];
            $type = $argument['expr']['type'];
            $exprs[$name] = $this->parameterType[$type] ?? '';
            if(isset($argument['expr']['items'])){
                $args = $argument['expr']['items'];
                $exprs += $this->getExprArguments($args, $name . '_');
            }
        }

        return $exprs;
    }

}