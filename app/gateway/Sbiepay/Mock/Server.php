<?php

namespace Gateway\Sbiepay\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Sbiepay;
use Gateway\Base;
use Gateway\Base\Action;
use Illuminate\Support\Facades\Response;
use Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = $this->getContentFromInputAuthorize($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);
        $content = array(
            'MerchantOrderNo'     => $input['MerchantOrderNo'],
            'SBIePayReferenceID'  => random_integer(6),
            'Status'              => 'SUCCESS',
            'PostingAmount'       => $input['PostingAmount'],
            'MerchantCurrency'    => $input['MerchantCurrency'],
            'Paymode'             => $input['Paymode'],
            'OtherDetails'        => $input['OtherDetails'],
            'Reason'              => 'Some Reason',
            'BankCode'            => random_integer(6),
            'BankReferenceNumber' => random_integer(6),
            'TrasactionDate'      => Carbon::now()->toDateTimeString(),
            'MerchantCountry'     => $input['MerchantCountry'],
            'CIN'                 => random_integer(4),
            'AdditionalInfo1'     => null,
            'AdditionalInfo2'     => null,
            'AdditionalInfo3'     => null,
            'AdditionalInfo4'     => null,
            'AdditionalInfo5'     => null,
            'AdditionalInfo6'     => null,
            'AdditionalInfo7'     => null,
            'AdditionalInfo8'     => null,
            'AdditionalInfo9'     => null,
        );

        $encData = Sbiepay\EncryptDecrypt::encryptData(['encData' => $content]);

        $request = array(
            'url'     => $input['SuccessURL'],
            'content' => $encData,
            'method'  => 'post',
        );

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        $input = $this->getContentFromInputVerify($input);

        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['MerchantOrderNo'], Action::AUTHORIZE);

        $content = array(
            'MerchantOrderNo'     => $payment['MerchantOrderNo'],
            'SBIePayReferenceID'  => random_integer(6),
            'Status'              => 'SUCCESS',
            'PostingAmount'       => $payment['PostingAmount'],
            'MerchantCurrency'    => $payment['MerchantCurrency'],
            'Paymode'             => $payment['Paymode'],
            'OtherDetails'        => $payment['OtherDetails'],
            'Reason'              => 'Some Reason',
            'BankCode'            => random_integer(6),
            'BankReferenceNumber' => random_integer(6),
            'TrasactionDate'      => Carbon::now()->toDateTimeString(),
            'MerchantCountry'     => $payment['MerchantCountry'],
            'CIN'                 => random_integer(4),
            'AdditionalInfo1'     => null,
            'AdditionalInfo2'     => null,
            'AdditionalInfo3'     => null,
            'AdditionalInfo4'     => null,
            'AdditionalInfo5'     => null,
            'AdditionalInfo6'     => null,
            'AdditionalInfo7'     => null,
            'AdditionalInfo8'     => null,
            'AdditionalInfo9'     => null,
        );
        $encData = Sbiepay\EncryptDecrypt::encryptData(['encData' => $content]);

        return $this->makeResponse($encData);
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
            'RequestType'    => '0410',
            'MerchantID'     => $payment['MerchantID'],
            'TxnReferenceNo' => $payment['TxnReferenceNo'],
            'TxnDate'        => $payment['TxnDate'],
            'CustomerID'     => $payment['CustomerID'],
            'TxnAmount'      => $payment['TxnAmount'],
            'RefAmount'      => $input['RefAmount'],
            'RefDateTime'    => $now,
            'RefStatus'      => '0799',
            'RefundId'       => random_alpha_string(15),
            'ErrorCode'      => 'NA',
            'ErrorReason'    => 'NA',
            'ProcessStatus'  => 'Y',
        );

        $msg = $this->getGatewayInstance()->getMessageStringWithHash($content);

        return $this->makeResponse($msg);
    }

//    protected function makeResponse($msg)
//    {
////        $response = \Response::make($msg);
//        $response = Response::make($msg, 200);
//
//        $response->header('Content-Type', 'application/text');
//
//        return $response;
//        //->header('Content-Type', 'application/text; charset=UTF-8');
//
////        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
////        $response->headers->set('Cache-Control', 'no-cache');
//sd($response);
//        return $response;
//    }


    protected function makeResponse($msg)
    {
        $response = \Response::make($msg);

        $response->headers->set('Content-Type', 'application/text; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getContentFromInputAuthorize($input)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $fields = $this->getGatewayInstance()->getFields($name, 'request');
        $content = explode('|', Sbiepay\EncryptDecrypt::decryptData($input['EncryptTrans']));
        $input = array_combine($fields, $content);

        return $input;
    }

    protected function getContentFromInputVerify($input)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $fields = $this->getGatewayInstance()->getFields($name, 'request');
        $content = explode('|', Sbiepay\EncryptDecrypt::decryptData($input['encryptQuery']));
        $input = array_combine($fields, $content);

        return $input;
    }

}