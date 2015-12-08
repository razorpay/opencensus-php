<?php

namespace Gateway\Wallet\Payzapp\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Payzapp;
use Gateway\Base;
use Gateway\Base\Action;
use Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        sd($input);
    }

    public function verify($input)
    {
    }

    public function refund($input)
    {
        return $this->makeResponse($msg);
    }

    protected function getContentFromInput($input)
    {
        return $input;
    }
}