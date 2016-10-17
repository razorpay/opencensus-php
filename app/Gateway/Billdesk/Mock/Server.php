<?php

namespace RZP\Gateway\Billdesk\Mock;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Billdesk;
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

        $input = $this->getContentFromInput($input);

        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today('Asia/Kolkata')->format('d-m-Y H:i:s');

        $content = array(
            'MerchantID'        => $input['MerchantID'],
            'CustomerID'        => $input['CustomerID'],
            'TxnReferenceNo'    => random_alpha_string(10),
            'BankReferenceNo'   => 'NA',
            'TxnAmount'         => $input['TxnAmount'],
            'BankID'            => $input['BankID'],
            'BankMerchantID'    => $input['BankID'],
            'TxnType'           => 'INR',
            'CurencyName'       => 'INR',
            'ItemCode'          => 'DIRECT',
            'SecurityType'      => 'NA',
            'SecurityID'        => 'NA',
            'SecurityPassword'  => 'NA',
            'TxnDate'           => $date,
            'AuthStatus'        => '0300',
            'SettlementType'    => 'NA',
            'AdditionalInfo1'   => 'NA',
            'AdditionalInfo2'   => 'NA',
            'AdditionalInfo3'   => 'NA',
            'AdditionalInfo4'   => 'NA',
            'AdditionalInfo5'   => 'NA',
            'AdditionalInfo6'   => 'NA',
            'AdditionalInfo7'   => 'NA',
            'ErrorStatus'       => 'NA',
            'ErrorDescription'  => 'NA',
        );

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        // // Uncomment below to mock s2s callback
        // $headers = array(
        //                     'User-Agent'    => 'Razorpay-Webhook/v1',
        //             );
        // $url = $this-route->getUrlWithPublicAuth('gateway_payment_callback_post',
        //                                         ['gateway' => 'billdesk']);

        // Requests::post(
        //     $url,
        //     $headers,
        //     ['msg' => $msg]);

        $content = ['msg' => $msg];

        $this->content($content);

        $request = array(
            'url' => $input['RU'],
            'content' => $content,
            'method' => 'post',
        );

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $input = $this->getContentFromInput($input);

        // @todo: Fix below for validation.
        unset($input['Current Date/ Timestamp']);
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
        parent::refund($input);

        $input = $this->getContentFromInput($input);

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

    protected function getContentFromInput($input)
    {
        $name = $this->action;

        $fields = $this->getGatewayInstance()->getFields($name, 'request');
        $content = explode('|', $input['msg']);
        $input = array_combine($fields, $content);

        $this->input = $input;

        return $input;
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
