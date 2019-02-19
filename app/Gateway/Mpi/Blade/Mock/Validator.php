<?php

namespace RZP\Gateway\Mpi\Blade\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authenticateRules = [
        'PaReq'                                => 'required|string',
        'MD'                                   => 'present|string|size:14',
        'TermUrl'                              => 'sometimes|url'
    ];
}
