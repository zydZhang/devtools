<?php
/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\DevTools\Traits;

use Eelly\SDK\EellyClient;
use ReflectionClass;

/**
 * @author hehui<hehui@eelly.net>
 */
trait SDKDirTrait
{
    /**
     * 获取 eelly sdk path.
     */
    public function getEellySDKPath()
    {
        $reflectionClass = new ReflectionClass(EellyClient::class);

        return dirname($reflectionClass->getFileName());
    }
}
