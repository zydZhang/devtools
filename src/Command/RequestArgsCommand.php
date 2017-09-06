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

use Phalcon\Annotations\Adapter\Memory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Eelly\DTO\UidDTO;

class RequestArgsCommand extends BaseCommand
{
    private $annotations = [
        'example' => 'requestExample',
        'reqArgs' => 'reqArgs',
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
        $this->setName('request-args')
        ->setDescription('生成方法的请求参数说明')
        ->setHelp('request-args goods:goods getStoreAddress')
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
     * 设置方法的请求参数说明文档
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
        if(!$reader->has($this->annotations['reqArgs'])){
            throw new RuntimeException('not found annotation:' . $this->annotations['reqArgs']);
        }

        $methodReflection = new \ReflectionMethod($className, $methodName);
        $filePath = $methodReflection->getFileName();
        $methodDoc = $methodReflection->getDocComment();
        $hasUidDTO = $this->hasUidDTO($methodReflection->getParameters());
        $requestArgs = $this->getMethodRequestExample($reader, $hasUidDTO) . sprintf('%-5s*', '');
        $newMethodDoc = preg_replace('/\*\s*@reqArgs/', $requestArgs, $methodDoc);
        $newFileContent = str_replace($methodDoc, $newMethodDoc, file_get_contents($filePath));
        file_put_contents($filePath, $newFileContent);
    }

    /**
     * 获取requestExample字符串
     *
     * @param string $moduleName 模块名
     * @param string $interfaceName 接口名
     * @param string $methodName 方法名称
     * @throws RuntimeException
     * @return string
     * @author wangjiang<wangjiang@eelly.net>
     * 2017年8月24日
     */
    private function getMethodRequestExample(\Phalcon\Annotations\Collection $reader, bool $hasUidDTO = false): string
    {
        if(!$reader->has($this->annotations['example'])){
            throw new RuntimeException('not found annotation:' . $this->annotations['example']);
        }

        /** @var \Phalcon\Annotations\Annotation $exAnnotation */
        $exAnnotation = $reader->get($this->annotations['example']);
        $requestExample = $exAnnotation->getArgument(0);
        if(!is_array($requestExample)){
            throw new RuntimeException('request example not json');
        }

        $arguments = $exAnnotation->getExprArguments();
        // 区分{}和[]json格式的数据
        $arguments = $arguments[0]['expr']['items'];
        $exprArguments = $this->getExprArguments($arguments);
        $requestArgsStr = '*' . PHP_EOL;
        foreach($exprArguments as $field => $fieldType){
            $field = preg_replace_callback('/_(\w*)/', function($match){
                return sprintf('["%s"]', $match[1]);
            }, $field);
            $requestArgsStr .= sprintf('%-5s* @param %s %s', '', $fieldType, '$' . $field) . PHP_EOL;
        }
        $hasUidDTO && $requestArgsStr .= sprintf('%-5s* @param \\Eelly\\DTO\\UidDTO $user 登录用户信息', '') . PHP_EOL;


        return $requestArgsStr;
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

    private function hasUidDTO(array $parameters): bool
    {
        if(empty($parameters)){
            return false;
        }

        foreach($parameters as $parameter){
            if(UidDTO::class === $parameter->getType()->getName()){
                return true;
            }
        }

        return false;
    }
}