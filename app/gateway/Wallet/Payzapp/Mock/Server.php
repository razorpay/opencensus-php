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
        $input = json_decode($input['json'], true);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'ResCode' => '',
            'ResDesc' => '',
            'MsgHash' => '',
            'DataPickUpCode' => '',
            'WibmoTxnId' => '',
        );

        $request = array(
            'url' => $input['callbackUrl'],
            'content' => ['msg' => $msg],
            'method' => 'post',
        );

        return $this->makePostResponse($request);;

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

    public static function getMockServerUrl()
    {
        $callbackUrl = Route::getUrlWithPublicCallbackAuth($params);
    }
}