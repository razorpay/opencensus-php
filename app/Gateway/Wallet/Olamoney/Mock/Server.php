<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Gateway\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\Command;
use RZP\Gateway\Wallet\Olamoney\RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields;


class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $input['bill'] = json_decode(base64_decode(urldecode($input['bill'])), true);

        $this->validateActionInput($input, Command::DEBIT);

        $bill = $input['bill'];

        $content = array(
            ResponseFields::TYPE              => 'debit',
            ResponseFields::STATUS            => 'success',
            ResponseFields::MERCHANT_BILL_ID  => $bill[RequestFields::UNIQUE_ID],
            ResponseFields::TRANSACTION_ID    => 'ola_txn_id',
            ResponseFields::AMOUNT            => $bill[RequestFields::AMOUNT],
            ResponseFields::COMMENTS          => $bill[RequestFields::COMMENTS],
            ResponseFields::UDF               => $bill[RequestFields::UDF],
            ResponseFields::TIMESTAMP         => time(),
        );

        $content[ResponseFields::HASH] = $this->generateHash($content);

        $this->content($content);

        $request = array(
            'url' => $bill[RequestFields::RETURN_URL],
            'content' => $content,
            'method' => 'post',
        );

        return $this->makePostResponse($request);
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->validateActionInput($input, Command::REFUND);

        $responseContent = array(
            ResponseFields::TYPE              => 'refund',
            ResponseFields::STATUS            => 'success',
            ResponseFields::TRANSACTION_ID    => 'bgho5botne16',
            ResponseFields::MERCHANT_BILL_ID  => 'cd1501cea88e4654898d8b2a266bc467',
            ResponseFields::AMOUNT            => '20.0',
            ResponseFields::TIMESTAMP         => '1439473847354',
            ResponseFields::COMMENTS          => 'test',
            ResponseFields::UDF               => 'test',
        );

        return $this->makeResponse($responseContent);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($this->mockRequest['content']);

        $response = array(
            ResponseFields::STATUS          => 'completed',
            ResponseFields::AMOUNT          => '500.00',
            ResponseFields::TYPE            => 'debit',
            ResponseFields::UNIQUE_BILL_ID  => 'bgho5botne16',
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
