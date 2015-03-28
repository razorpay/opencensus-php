<?php

namespace Gateway\Axis;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\BaseGateway;
use Gateway\Axis;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends BaseGateway
{
    public function authorize(array $input)
    {
        parent::authorize($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);
    }
}