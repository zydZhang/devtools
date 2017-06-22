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

/*
// 一主多从

return [
    'master' => [
        'host' => '172.18.107.96',
        'username' => 'devmall',
        'password' => 'devmall',
        'dbname' => 'malltest',
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            \PDO::ATTR_CASE => \PDO::CASE_LOWER,
        ],
    ],
    'slave' => [
        'server_0' => [
            'host' => '172.18.107.96',
            'username' => 'devmall',
            'password' => 'devmall',
            'dbname' => 'malltest',
            'options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                \PDO::ATTR_CASE => \PDO::CASE_LOWER,
            ],
        ],
        'server_1' => [
            'host' => '172.18.107.97',
            'username' => 'devmall',
            'password' => 'devmall',
            'dbname' => 'malltest',
            'options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                \PDO::ATTR_CASE => \PDO::CASE_LOWER,
            ],
        ],
    ],
];
*/
