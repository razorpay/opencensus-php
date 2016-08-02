<?php

namespace RZP\Gateway\Ebs\Mock;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Ebs;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Card;
use Requests;
use RZP\Models\Payment\Core;
use RZP\Gateway\Ebs\ResponseConstants as Response;

class Server extends Base\Mock\Server
{
    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        $payment = $this->getRepo()->findByEbsPaymentIdAndActionOrFail(
            $input['PaymentID'], Action::AUTHORIZE);

        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');

        $content = '<output transactionId="'.
            $payment['transaction_id'].
            '" paymentId="'.
            $payment['reference_id'].
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
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');

        $content = array(
            Response::RESPONSE_CODE     => '0',
            Response::RESPONSE_MESSAGE  => 'Transaction Successful',
            Response::DATE_CREATED      => $date,
            Response::EBS_PAYMENT_ID    => random_alpha_string(8),
            Response::MERCHANT_REF_NO   => $input['reference_no'],
            Response::AMOUNT            => $input['amount'],
            Response::MODE              => $input['mode'],
            Response::DESCRIPTION       => $input['description'],
            Response::IS_FLAGGED        => 'NO',
            Response::TRANSACTION_ID    => random_alpha_string(8),
            Response::PAYMENT_METHOD    => '1001',
            Response::REQUEST_ID        => random_alpha_string(8),
        );

        $content[Response::SECURE_HASH] = $this->getGatewayInstance()->getSecureHash($content, null);

        $this->content($content);

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

        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');

        $content = '<output response="SUCCESS" transactionId="'.
            $payment['transaction_id'].
            '" paymentId="'.
            $payment['reference_id'].
            '" amount="'.
            $input['Amount'].
            '" dateTime="'.
            $date.
            '" mode='.
            '"TEST"'.
            ' referenceNo="'.
            $payment["payment_id"].
            '" transactionType="refunded" status="Processing" />';

        $this->content($content);

        return $this->makeResponse($content);
    }
}
