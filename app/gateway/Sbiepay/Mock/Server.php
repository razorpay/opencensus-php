<?php

namespace Gateway\Sbiepay\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Sbiepay;
use Gateway\Base;
use Gateway\Base\Action;
use Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = $this->getContentFromInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(

            'MerchantId'         => $input['terminal']['gateway_merchant_id'],
            'OperatingMode'      => 'DOM',
            'MerchantCountry'    => 'IN',
            'MerchantCurrency'   => 'INR',
            'PostingAmount'      => $input['payment']['amount'] / 100,
            'OtherDetails'       => '',
            'SuccessURL'         => $input['SuccessURL'],
            'FailURL'            => $input['FailURL'],
            'AggregatorId'       => 'SBIEPAY',
            'MerchantOrderNo'    => $input['payment']['id'],
            'MerchantCustomerID' => $input['payment']['email'],
            'Paymode'            => 'NB',
            'Accesmedium'        => 'ONLINE',
            'TransactionSource'  => 'ONLINE'
        );

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        $request = array(
            'url' => $input['SuccessURL'],
            'content' => ['encData' => $msg],
            'method' => 'post',
        );

        return $this->makePostResponse($request);;
    }

    public function verify($input)
    {
        $input = $this->getContentFromInput($input);

        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
                        $input['Customer ID'], Action::AUTHORIZE);

        $fields = $this->getGatewayInstance()->getFieldsForAction('verify');

        $content = array_combine($fields, array_fill(0, count($fields), 'NA'));

        $payment = $payment->toArray();

        foreach ($content as $key => $value)
        {
            if (isset($payment[$key]) === true)
                $content[$key] = $payment[$key];
        }

        $refunds = $this->getRepo()->findRefunds($input['Customer ID']);

        $refundAmount = 0.00;

//        $content['AuthStatus'] = '0200';

        foreach ($refunds as $refund)
        {
            $refundAmount += (double) $refunds['RefAmount'];

            $content['TotalRefundAmount'] = $refundAmount;
            $content['LastRefundDate'] = $refunds['RefDateTime'];
            $content['LastRefundRefNo'] = $refunds['RefundId'];
            $content['RefundStatus'] = $refunds['RefStatus]'];
        }

        $content['QueryStatus'] = 'Y';

        unset($content['Checksum']);
        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        return $this->makeResponse($msg);
    }

    public function refund($input)
    {
        $input = $this->getContentFromInput($input);

        parent::verify($input);

        $this->validateActionInput($input, 'refund');

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
                        $input['CustomerID'], Action::AUTHORIZE);

        $fields = $this->getGatewayInstance()->getFieldsForAction('refund');

        $content = array_combine($fields, array_fill(0, count($fields), 'NA'));

        // Format yyyymmdd24hhmmss (in docs), actually yyyymmdd0hhmmss,
        // hh is in 24 hrs
        $now = Carbon::now('Asia/Kolkata')->format('Ymd0His');

        $content = array(
            'RequestType'   => '0410',
            'MerchantID'    => $payment['MerchantID'],
            'TxnReferenceNo' => $payment['TxnReferenceNo'],
            'TxnDate'       => $payment['TxnDate'],
            'CustomerID'    => $payment['CustomerID'],
            'TxnAmount'     => $payment['TxnAmount'],
            'RefAmount'     => $input['RefAmount'],
            'RefDateTime'   => $now,
            'RefStatus'     => '0799',
            'RefundId'      => random_alpha_string(15),
            'ErrorCode'     => 'NA',
            'ErrorReason'   => 'NA',
            'ProcessStatus' => 'Y',
        );

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        return $this->makeResponse($msg);
    }

    protected function makeResponse($msg)
    {
        $response = \Response::make($msg);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getContentFromInput($input)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $fields = $this->getGatewayInstance()->getFields($name, 'request');

        $content = explode('|', $input['msg']);
        $input = array_combine($fields, $content);

        return $input;
    }
}