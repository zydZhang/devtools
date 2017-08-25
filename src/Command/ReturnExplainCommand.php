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
    private $annotationName = 'returnExample';

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
        $returnExample = $this->getMethodReturnExample($moduleName, $interfaceName, $methodName);
        $output->writeln($returnExample);
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
    private function getMethodReturnExample(string $moduleName, string $interfaceName, string $methodName): string
    {
        $className = sprintf('Eelly\\SDK\\%s\\Service\\%sInterface', ucfirst($moduleName), ucfirst($interfaceName));
        $di = $this->getDI();
        /** @var Memory $annotations */
        $annotations = $di->getShared(Memory::class);
        $reader = $annotations->getMethod($className, $methodName);
        if(!$reader->has($this->annotationName)){
            throw new RuntimeException('not found annotation:' . $this->annotationName);
        }

        $returnExample = $reader->get($this->annotationName)->getArgument(0);
        if(!is_array($returnExample)){
            throw new RuntimeException('return example not json');
        }

        count($returnExample) !== count($returnExample, COUNT_RECURSIVE) && $returnExample = current($returnExample);
        $exampleArr = [];
        $returnFields = array_keys($returnExample);
        array_walk($returnExample, function($val, $key) use (&$exampleArr){
            $exampleArr[$key] = strlen($key);
        });

        $max = max($exampleArr) + 1;
        $returnExampleStr = '### 返回数据说明' . PHP_EOL;
        $returnExampleStr .= sprintf('%-5s*','') . PHP_EOL;
        $returnExampleStr .= sprintf('%-5s* %-' . ($max + 2) .'s|%-9s|%-16s', '', '字段', '类型', '说明') . PHP_EOL;
        $returnExampleStr .= sprintf('%-5s* %s|%s|%s', '', str_repeat('-', $max), str_repeat('-', 7),str_repeat('-', 14)) . PHP_EOL;
        foreach($returnFields as $field){
            $returnExampleStr .= sprintf('%-5s* %-' . $max . 's|%-7s|%-14s', '', $field, '', '') . PHP_EOL;
        }

        return $returnExampleStr;
    }
}