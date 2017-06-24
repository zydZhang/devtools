<?php
/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\DevTools\BuildFile;

use Phalcon\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

/**
 * @author hehui<hehui@eelly.net>
 */
class ApiFileTest extends TestCase
{
    private $apiFile;

    public function setUp()
    {
        $di = new FactoryDefault();
        $this->apiFile = new ApiFile($di);
    }

    public function testGetEellySDKPath()
    {
        $sdkPath = $this->apiFile->getEellySDKPath();
        $this->assertFileExists($sdkPath.'/EellyClient.php');
    }
}
