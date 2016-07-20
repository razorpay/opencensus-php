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

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');
        $content = array(
            "ResponseCode" => "0",
            "ResponseMessage" => "Transaction Successful",
            "DateCreated" => $date,
            "PaymentID" => random_alpha_string(8),
            "MerchantRefNo" => $input['reference_no'],
            "Amount" => $input['amount'],
            "Mode" => $input['mode'],
            "Description" => $input['description'],
            "IsFlagged" => "NO",
            "TransactionID" => random_alpha_string(8),
            "PaymentMethod" => "1001",
            "RequestID" => random_alpha_string(8),

        );

        $content['SecureHash'] = $this->getGatewayInstance()->getSecureHash($content);

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
        $fields = $this->getGatewayInstance()->getFieldsForAction('refund');
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');

        $arr = array(
            '<output response="SUCCESS" transactionId="',
            $payment["TransactionID"],
            '" paymentId="',
            $payment["ebs_payment_id"],
            '" amount="',
            $input['Amount'],
            '" dateTime="',
            $date,
            '" mode="',
            $payment["mode"],
            '" referenceNo="',
            $payment["payment_id"],
            '" transactionType="refunded" status="Processing"/>',
        );

        $content = implode($arr);

            return $this->makeResponse($content);
    }


    protected function makeRequest($request)
    {
        $method = $request['method'];

        $response = Requests::$method(
            $request['url'],
            $request['headers'],
            $request['content']);

        return $response;
    }
}
