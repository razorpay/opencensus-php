<?php

namespace Gateway\Wallet\Olamoney\Mock;

use Http\Route;
use Gateway\Base;
use EE\Exception;
use Carbon\Carbon;
use Models\Payment;
use EE\Error\ErrorCode;
use Gateway\Base\Action;
use Gateway\Wallet\Olamoney;

class Server extends Base\Mock\Server
{
    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
