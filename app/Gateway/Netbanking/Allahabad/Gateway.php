<?php

namespace RZP\Gateway\Netbanking\Allahabad;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Constants\Mode as RZPMode;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_allahabad';

    protected $bank = 'allahabad';

    protected $map = [
         RequestFields::MERCHANT_CODE         => NetbankingEntity::MERCHANT_CODE,
         RequestFields::AMOUNT                => NetbankingEntity::AMOUNT,
         RequestFields::ACCOUNT_NUMBER        => NetbankingEntity::ACCOUNT_NUMBER,
         NetbankingEntity::RECEIVED           => NetbankingEntity::RECEIVED,
         RequestFields::PRODUCT_REF_NUMBER    => NetbankingEntity::PAYMENT_ID,
         ResponseFields::BANK_TRANSACTION_ID  => NetbankingEntity::BANK_PAYMENT_ID,
         ResponseFields::PAID                 => NetbankingEntity::STATUS,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray([],'post');

        $paramStr = http_build_query($content,null,'|');

        $paramStr = urldecode($paramStr);

        $sigStr = $this->getHashOfString($paramStr);

        $request['url'] .= '?bank_signaturte=' . $sigStr . '&parameter_string=' . $paramStr;

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->validateCallbackChecksum($content);

        $content = $this->getCallbackContentArray($content);

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);

        $actualAmount   = $this->formatAmount($content[ResponseFields::AMOUNT]);

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::PRODUCT_REF_NUMBER]);

        $this->saveCallbackResponse($content);

        $this->checkCallbackStatus($content);

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
            RequestFields::ACTION               => Status::YES,
            RequestFields::BANK_ID              => Constants::BANK_ID,
            RequestFields::MODE_OF_PAYMENT      => Constants::MODE_OF_PAYMENT_AUTH,
            RequestFields::PAYEE_ID             => Constants::PAYEE_ID,
            RequestFields::ITEM_CODE            => $input['payment']['id'],
            RequestFields::PRODUCT_REF_NUMBER   => $input['payment']['id'],
            RequestFields::AMOUNT               => $this->formatAmount($input['payment']['amount']/100),
            RequestFields::CURRENCY             => Currency::INR,
            RequestFields::RETURN_URL           => $input['callbackUrl'],
            RequestFields::CG                   => Status::YES,
            RequestFields::LANGUAGE_ID          => Constants::USER_LANG_ID,
            RequestFields::USER_TYPE            => Constants::USER_TYPE,
            RequestFields::APP_TYPE             => Constants::RETAIL,
            RequestFields::MERCHANT_CODE        => $this->getMerchantId(),
        ];

        return $data;
    }

    protected function validateCallbackChecksum($content)
    {
        $inputHash = $content[ResponseFields::CHECKSUM];

        $expectedHash = $this->getHashOfString($content['parameter_string']);

        $this->compareHashes($inputHash, $expectedHash);
    }

    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::PAID]) === true) and
            ($content[ResponseFields::PAID] === Status::CANCEL))
        {
            //Payment cancelled by user
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER);
        }

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

        $gatewayPayment = $this->getRepository()
                               ->findByPaymentIdAndActionOrFail(
                                   $content[ResponseFields::PRODUCT_REF_NUMBER],
                                   Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->getVerifyResponse($response->body);
    }

    protected function getVerifyRequestData($verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $data = [
            RequestFields::ACTION                 => Status::YES,
            RequestFields::BANK_ID                => Constants::BANK_ID,
            RequestFields::PAYEE_ID               => Constants::PAYEE_ID,
            RequestFields::MODE_OF_PAYMENT        => Constants::MODE_OF_PAYMENT_VERIFY,
            RequestFields::ITEM_CODE              => $input['payment']['id'],
            RequestFields::PRODUCT_REF_NUMBER     => $input['payment']['id'],
            RequestFields::AMOUNT                 => $this->formatAmount($input['payment']['amount']/ 100),
            RequestFields::CURRENCY               => Currency::INR,
            RequestFields::LANGUAGE_ID            => Constants::USER_LANG_ID,
            RequestFields::USER_TYPE              => Constants::USER_TYPE,
            RequestFields::APP_TYPE               => Constants::RETAIL,
            RequestFields::STATFLG                => Constants::STATFLG,
            RequestFields::BANK_TRANSACTION_ID    => $gatewayPayment['bank_payment_id'],
        ];

        $request = $this->getStandardRequestArray([],'get');

        $str = http_build_query($data,null,'|');

        $sigStr = $this->getHashOfString($str, '|');

        $request['url'] .= '?bank_signaturte=' . $sigStr . '&parameter_string=' . $str;

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
        $attributes[Base\Entity::STATUS] = $content[ResponseFields::PAID];

        return $attributes;
    }

    protected function getVerifyResponse($response)
    {
        $xml = (array) simplexml_load_string($response);

        $string = $xml['VERIFICATION'];

        $array = explode('=', $string);

        $array[0] = rtrim($array[0]);

        $array[1] = trim($array[1]);

        $newArray = array();

        $newArray[$array[0]] = $array[1];

        return $newArray;
    }

    public function formatAmount($amount): string
    {
        return number_format($amount , 2, '.', '');
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $sigStr = hash_hmac('sha1', $str, $secret);

        return $sigStr;
    }

    protected function getCallbackContentArray($content)
    {
        $str = $content['parameter_string'];

        $array = explode('|', $str);

        $newArray = array();

        foreach($array as $val)
        {
            $temp = explode('=', $val);

            $newArray[$temp[0]] = $temp[1];
        }

        return $newArray;
    }

    protected function getMerchantId(): string
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === RZPMode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}

