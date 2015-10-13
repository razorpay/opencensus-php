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
        $encData = Sbiepay\EncryptDecrypt::encryptData(['encStatusData' => $content]);
        ob_start();

        require('VerifyResponseHtml.php');

        $html = ob_get_clean();

        return $this->prepareResponse($html);
    }

    public function refund($input)
    {
        $input = $this->getContentFromInputRefund($input);

        parent::verify($input);

        $this->validateActionInput($input, 'refund');

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['MerchantOrderNo'], Action::AUTHORIZE);

        //$fields = $this->getGatewayInstance()->getFieldsForAction('refund');

        $content = array(
            'RefundRequestId'   => $input['RefundRequestId'],
            'Status'            => 'SUCCESS',
            'Message'           => 'Refund Booked',
            'SBIePayReferenceID' => random_integer(6)
        );
        $encData = Sbiepay\EncryptDecrypt::encryptData(['encRefundData' => $content]);
        ob_start();

        require('RefundResponseHtml.php');

        $html = ob_get_clean();

        return $this->prepareResponse($html);
    }




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

    protected function getContentFromInputRefund($input)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $fields = $this->getGatewayInstance()->getFields($name, 'request');
        $content = explode('|', Sbiepay\EncryptDecrypt::decryptData($input['EncryptRefundDetails']));
        $input = array_combine($fields, $content);

        return $input;
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

}