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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Eelly\DevTools\BuildFile\PhpunitFile;

class CreatePhpunitCommand extends BaseCommand
{
    /**
     * 配置命令
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $helpStr = <<<EOT
create-phpunit all 创建全部模块的单元测试
create-phpunit goods 创建goods模块的单元测试
EOT;
        $this->setName('create-phpunit')
        ->setDescription('生成单元测试')
        ->setHelp($helpStr)
        ->addArgument('moduleName', InputArgument::REQUIRED, '模块名');
    }

    /**
     * 命令执行的逻辑
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Phalcon\Config $config */
        $config = $this->dependencyInjector->getConfig();
        $moduleName = $input->getArgument('moduleName');
        $modules = 'all' === $moduleName ? $config->devModules->toArray() : [$moduleName];
        $repeatStr = str_repeat('=', 6);
        foreach ($modules as $module){
            (new PhpunitFile($this->dependencyInjector))->run($module);
            $output->writeln($repeatStr . $module . '模块单元测试生成完成' . $repeatStr);
        }
    }
}