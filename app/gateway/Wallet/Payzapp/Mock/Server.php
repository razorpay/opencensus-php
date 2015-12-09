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

        $wibmoTxnId = $this->getWibmoTxnId();

        $content = array(
            'resCode'                   =>  '000',
            'resDesc'                   =>  'SUCCESS',
            'actionCode'                =>  '0',
            'additionalUserInputData'   =>  '[object Object]',
            'msgHash'                   =>  'cOMsjV/9Vw3dBN5ECr54tEl7ITCwWj4O+pWbqrz8ZuM=',
            'dataPickUpCode'            =>  '201512091604066125oW33aC8:2jG2cS5gT9',
            'wibmoTxnId'                =>  $wibmoTxnId,
            'merTxnId'                  =>  $input['transactionInfo']['merTxnId'],
        );

        $request = array(
            'url' => $input['wIapDefaults']['wIapReturnUrl'],
            'content' => $content,
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

    protected static function getWibmoTxnId()
    {
        return random_alphanum_string(21);
    }

    protected static function getDataPickupCode($wibmoTxnId)
    {
        return $wibmoTxnId . ':' . random_alphanum_string(10);
    }
}