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

/**
 * 拦截器管理中心
 * @version 1.0
 */
class InterceptCenter
{
    /**
     * 事件管理
     * @var object 
     */
    public $eventsManager;
    
    public function __construct($event) 
    {
        $this->eventsManager = $event;
    }
    
    /**
     * 注册db事件监听事件
     * 
     * @throws \Exception
     */
    public function registDbListener()
    {
        $this->eventsManager->attach('db', new DbListerner());
    }
    
    /**
     * 注册注解监听事件
     * 
     * @throws \Exception
     */
    public function registAnnotation()
    {
        $this->eventsManager->attach('controler', new AnnotationListerner());
    }
}

