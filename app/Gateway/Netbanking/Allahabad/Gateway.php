<?php

namespace RZP\Gateway\Netbanking\Allahabad;

//use RZP\Exception;
//use RZP\Constants\Mode;
//use RZP\Models\Payment;
//use RZP\Error\ErrorCode;
//use RZP\Trace\TraceCode;
//use RZP\Gateway\Base\Verify;
//use RZP\Gateway\Netbanking\Base;
//
//use RZP\Gateway\Base\VerifyResult;
//use RZP\Gateway\Base\AuthorizeFailed;
//use RZP\Models\Payment\Verify\Action as VerifyAction;
//use RZP\Gateway\Netbanking\Base;
//use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Payment\Action;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'netbanking_allahabad';

    protected $bank = 'allahabad';

    protected $map = [
             RequestFields::ACCOUNT_NUMBER      => NetbankingEntity::ACCOUNT_NUMBER,
             RequestFields::MERCHANT_CODE       => NetbankingEntity::MERCHANT_CODE,
             RequestFields::AMOUNT              => NetbankingEntity::AMOUNT,
             RequestFields::ACCOUNT_NUMBER      => NetbankingEntity::ACCOUNT_NUMBER,
             NetbankingEntity::RECEIVED         => NetbankingEntity::RECEIVED,
             ResponseFields::PRODUCT_REF_NUMBER => NetbankingEntity::PAYMENT_ID,

    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray([],'get');

        $param_str = http_build_query($content,null,'|');

        $param_str = str_replace('%2F','/',$param_str);

        $param_str = str_replace('%3A',':',$param_str);

        $sig_str = $this->getHashOfString($param_str);

        $request['url'] .= '?bank_signature=' . $sig_str . '&parameter_string=' . $param_str;

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->validateCallbackChecksum($content);

        $content = $this->getCallbackContentArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $expectedAmount = $this->formatAmount($input['payment']['amount']);

        $actualAmount   = $this->formatAmount($content[ResponseFields::AMOUNT]);

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->assertPaymentId($input['payment']['id'],$content[ResponseFields::PRODUCT_REF_NUMBER]);

        $this->checkCallbackStatus($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE);

        $this->saveCallbackResponse($content);
        s($input['terminal']);
        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }


    protected function getAuthRequestData($input)
    {
        $data = [
            RequestFields::ACTION                 => Status::YES,
            RequestFields::BANK_ID                => Constants::BANK_ID,
            RequestFields::MODE_OF_PAYMENT        => Constants::MODE_OF_PAYMENT_AUTH,
            RequestFields::PAYEE_ID               => Constants::PAYEE_ID,
            RequestFields::ITEM_CODE              => $input['payment']['id'],
            RequestFields::PRODUCT_REF_NUMBER     => $input['payment']['id'],
            RequestFields::AMOUNT                 => $this->formatAmount($input['payment']['amount']),
            RequestFields::CURRENCY               => Currency::INR,
            RequestFields::RETURN_URL             => $input['callbackUrl'],
            RequestFields::CG                     => Status::YES,
            RequestFields::LANGUAGE_ID            => Constants::USER_LANG_ID,
            RequestFields::USER_TYPE              => Constants::USER_TYPE,
            RequestFields::APP_TYPE               => Constants::RETAIL,
            RequestFields::MERCHANT_CODE          => Constants::MERCHANT_CODE,
        ];

        return $data;
    }

    protected function validateCallbackChecksum($content)
    {
        $inputHash = $content[ResponseFields::CHECKSUM];

        $expectedHash = $this->getHashOfString($content['parameter_string']);

        if (hash_equals($expectedHash, $inputHash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }


    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::PAID]) === false) or
            ($content[ResponseFields::PAID] !== Status::YES))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse($content)
    {
        $content[NetbankingEntity::RECEIVED] = true;

        $gatewayPayment = $this->getRepository()->findByPaymentIdAndActionOrFail(
            $content[ResponseFields::PRODUCT_REF_NUMBER],
            Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->getResponseArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'decrypted'  => $verify->verifyResponseContent,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );
    }

    protected function getVerifyRequestData($verify)
    {

        $input = $verify->input;

        if ($this->action === Action::VERIFY)
        {
            $gatewayPayment = $verify->payment;
        }

        else
        {
            throw new Exception\LogicException('Verify should be called from verify action');
        }

        $data = [
            RequestFields::ACTION                 => Status::YES,
            RequestFields::BANK_ID                => Constants::BANK_ID,
            RequestFields::MODE_OF_PAYMENT        => Constants::MODE_OF_PAYMENT_VERIFY,
            RequestFields::PAYEE_ID               => Constants::PAYEE_ID,
            RequestFields::ITEM_CODE              => $input['payment']['id'],
            RequestFields::PRODUCT_REF_NUMBER     => $input['payment']['id'],
            RequestFields::AMOUNT                 => $input['payment']['amount'] / 100,
            RequestFields::CURRENCY               => Currency::INR,
            RequestFields::LANGUAGE_ID            => Constants::USER_LANG_ID,
            RequestFields::USER_TYPE              => Constants::USER_TYPE,
            RequestFields::APP_TYPE               => Constants::RETAIL,
            RequestFields::STATFLG                => Constants::STATFLG,
            RequestFields::BANK_TRANSACTION_ID    => '',
        ];

        $request = $this->getStandardRequestArray([],'get');

        $str = http_build_query($data,null,'|');

        $sig_str = $this->getHashOfString($str, '|');

        $request['url'] .= '?bank_signature=' . $sig_str . '&parameter_string=' . $str;

        return $request;
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyResponse($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::PAID]) === true) and
            ($content[ResponseFields::PAID] === STATUS::YES))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyResponse(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, $gatewayPayment): array
    {
        $attributes = [];

        $attributes[Base\Entity::STATUS] = $content[ResponseFields::PAID];

        return $attributes;
    }

    protected function getResponseArray($response)
    {
        $xml = (array) simplexml_load_string($response);

        $string = $xml['VERIFICATION'];

        $array = explode('=', $string);

        $array[0] = rtrim($array[0]);

        $array[1] = trim($array[1]);

        $new_array = array();

        $new_array[$array[0]]=$array[1];

        return $new_array;
    }

    public function formatAmount($amount): string
    {
        return number_format($amount , 2, '.', '');
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $sig_str = hash_hmac('sha512',$str,$secret);

        return $sig_str;
    }

    protected function getCallbackContentArray($content)
    {
        $str = $content['parameter_string'];

        $array = explode('|',$str);

        $new_array=array();

        foreach($array as $val)
        {
            $temp = explode('=',$val);

            $new_array[$temp[0]]=$temp[1];
        }

        return $new_array;
    }

}