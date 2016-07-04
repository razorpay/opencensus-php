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
        $bill = json_decode(base64_decode($input['bill']), true);

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

        return $this->makePostResponse($request);
    }

    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

}
