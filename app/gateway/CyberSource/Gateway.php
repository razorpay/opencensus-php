<?php

namespace Gateway\Cybersource;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\AxisMigs;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'cybersource';

    public function authorize(array $input)
    {
        parent::authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);
    }

    public function capture(array $input)
    {
    }

    public function refund(array $input)
    {
        $this->input = $input;
        $this->action = Action::REFUND;
    }

    public function verify(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY;
    }

}
