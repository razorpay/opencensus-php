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

    public function authorize($input)
    {
        $bill = json_decode(base64_decode(urldecode($input['bill'])), true);

        parent::authorize($input);

        $this->validateActionInput($input);

        $content = array(
            'type'              => 'debit',
            'status'            => 'success',
            'merchantBillId'    => $input['paymentId'],
            'transactionId'     => 'ola_txn_id',
            'amount'            => $bill['amount'],
            'comments'          => $bill['comments'],
            'udf'               => $bill['udf'],
            'timestamp'         => '1467613732',
        );
        $content['hash'] = $this->generateHash($content);

        $request = array(
            'url' => $bill['returnUrl'],
            'content' => $content,
            'method' => 'post',
        );

        return $this->makePostResponse($request, 'application/x-www-form-urlencoded');
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $responseContent = array(
            'type'              => 'refund',
            'status'            => 'success',
            'transactionId'     => 'bgho5botne16',
            'merchantBillId'    => 'cd1501cea88e4654898d8b2a266bc467',
            'amount'            => '20.0',
            'timestamp'         => '1439473847354',
            'comments'          => 'test',
            'udf'               =>'test',
        );

        return $this->makeResponse($responseContent);
    }

    protected function makeResponse($json, $content_type = 'application/json; charset=UTF-8')
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', $content_type);
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

}
