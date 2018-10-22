<?php

namespace RZP\Gateway\Ebs\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Ebs;
use RZP\Gateway\Ebs\RequestConstants as Request;
use RZP\Gateway\Ebs\ResponseConstants as Response;
use RZP\Models\Card;

class Server extends Base\Mock\Server
{
    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input[Request::API_REFERENCE_NO], Action::AUTHORIZE);

        $date = Carbon::today(Timezone::IST)->format('d-m-Y H:i:s');

        $content = '<output transactionId="'.
            ($payment['transaction_id'] ?: '{{transactionId}}').
            '" paymentId="'.
            ($payment['gateway_payment_id'] ?: '{{paymentId}}').
            '" amount="'.
            $payment['amount'].
            '" dateTime="'.
            $date.
            '" mode='.
            '"TEST"'.
            ' referenceNo="'.
            $payment['payment_id'].
            '" transactionType='.
            '"Authorized" '.
            'status="Processing" isFlagged="NO" />';

        $this->content($content);

        return $this->makeResponse($content);
    }
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today(Timezone::IST)->format('d-m-Y H:i:s');

        $content = array(
            Response::RESPONSE_CODE         => '0',
            Response::RESPONSE_MESSAGE      => 'Transaction Successful',
            Response::DATE_CREATED          => $date,
            Response::GATEWAY_PAYMENT_ID    => random_alpha_string(8),
            Response::MERCHANT_REF_NO       => $input['reference_no'],
            Response::AMOUNT                => $input['amount'],
            Response::MODE                  => $input['mode'],
            Response::DESCRIPTION           => $input['description'],
            Response::IS_FLAGGED            => 'NO',
            Response::TRANSACTION_ID        => random_alpha_string(8),
            Response::PAYMENT_METHOD        => '1001',
            Response::REQUEST_ID            => random_alpha_string(8),
        );

        $this->content($content);

        $content[Response::SECURE_HASH] = $this->generateHash($content);

        if ($content['IsFlagged'] === 'YES')
        {
            $content['IsFlagged'] = 'NO';
        }

        $request = array(
            'url' => $input['return_url'],
            'content' => $content,
            'method' => 'post',
        );

        return $this->makePostResponse($request);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $payment = $this->getRepo()->findByEbsPaymentIdAndActionOrFail(
            $input['PaymentID'], Action::AUTHORIZE);

        $date = Carbon::today(Timezone::IST)->format('d-m-Y H:i:s');

        $content = '<output response="SUCCESS" transactionId="'.
            '{{transactionId}}'.
            '" paymentId="'.
            '{{paymentId}}'.
            '" amount="'.
            $input['Amount'].
            '" dateTime="'.
            $date.
            '" mode='.
            '"TEST"'.
            ' referenceNo="'.
            $payment["payment_id"].
            '" transactionType="refunded" status="Processing" />';

        $this->content($content, $this->action);

        return $this->makeResponse($content);
    }
}
