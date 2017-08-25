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
use Eelly\Di\InjectionAwareInterface;
use Phalcon\Di;
use Phalcon\DiInterface;

class BaseCommand extends Command implements InjectionAwareInterface
{
    /**
     * Dependency Injector
     *
     * @var \Phalcon\DiInterface
     */
    protected $dependencyInjector;

    /**
     * Sets the dependency injector
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     */
    public function getDI()
    {
        if(!is_object($this->dependencyInjector)){
            $this->dependencyInjector = Di::getDefault();
        }

        return $this->dependencyInjector;
    }
}