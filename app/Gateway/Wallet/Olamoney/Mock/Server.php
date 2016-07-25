<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\Command;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $bill = json_decode(base64_decode(urldecode($input['bill'])), true);

        $this->validateActionInput($input, Command::DEBIT);

        $content = array(
            'type'              => 'debit',
            'status'            => 'success',
            'merchantBillId'    => $bill['uniqueId'],
            'transactionId'     => 'ola_txn_id',
            'amount'            => $bill['amount'],
            'comments'          => $bill['comments'],
            'udf'               => $bill['udf'],
            'timestamp'         => time(),
        );

        $content['hash'] = $this->generateHash($content);

        $this->content($content);

        $request = array(
            'url' => $bill['returnUrl'],
            'content' => $content,
            'method' => 'post',
        );

        return $this->makePostResponse($request);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, Command::REFUND);

        $responseContent = array(
            'type'              => 'refund',
            'status'            => 'success',
            'transactionId'     => 'bgho5botne16',
            'merchantBillId'    => 'cd1501cea88e4654898d8b2a266bc467',
            'amount'            => '20.0',
            'timestamp'         => '1439473847354',
            'comments'          => 'test',
            'udf'               => 'test',
        );

        return $this->makeResponse($responseContent);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            'status'        => 'completed',
            'amount'        => '500.00',
            'type'          => 'debit',
            'uniqueBillId'  => 'bgho5botne16',
        );

        return $this->makeResponse($response);
    }

    protected function makeResponse($json, $content_type = 'application/json; charset=UTF-8')
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', $content_type);
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
