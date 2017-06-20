<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

/*
$redisServer = [
    'parameters' => [
        'tcp://172.18.107.120:7000',
        'tcp://172.18.107.120:7001',
        'tcp://172.18.107.120:7002',
        'tcp://172.18.107.120:7003',
        'tcp://172.18.107.120:7004',
        'tcp://172.18.107.120:7005',
    ],
    'options' => ['cluster' => 'redis'],
    'statsKey' => '_PHCR_MEMBER_STATS',
];

return [
    'frontend' => \Phalcon\Cache\Frontend\Igbinary::class,
    'backend' => \Eelly\Cache\Backend\Predis::class,
    'options' => [
        \Phalcon\Cache\Frontend\Igbinary::class => [
            'lifetime' => 300,
        ],
        \Eelly\Cache\Backend\Predis::class => $redisServer,
    ],
];
*/
