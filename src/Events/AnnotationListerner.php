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

class AnnotationListerner
{
    public function __construct()
    {
    }
    
    public function init()
    {
        $annotation = new Annotation();
        $annotation->verify();
    }

}
