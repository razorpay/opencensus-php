<?php

namespace RZP\Services\Pspx\Mock;

use RZP\Services\Pspx\Service as PspxService;

/**
 * Class Service
 * @package RZP\Services\Pspx\Mock
 *
 * We will mock different test cases here
 * Depending on multiple scenarios we can override mock
 */
class Service extends PspxService
{
    public function ping()
    {
        return [
            'message' =>  'pong',
        ];
    }
}
