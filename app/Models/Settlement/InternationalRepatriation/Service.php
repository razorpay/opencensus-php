<?php

namespace RZP\Models\Settlement\InternationalRepatriation;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createInternationalRepatriation(array $input): Entity
    {
        return (new Core())->createInternationalRepatriation($input);
    }
}

