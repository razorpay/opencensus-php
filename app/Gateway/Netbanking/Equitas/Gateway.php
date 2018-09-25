<?php

namespace RZP\Gateway\Netbanking\Equitas;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_equitas';

    protected $bank = 'equitas';

    const DELIMITER = '|';

    const CHECKSUM_ATTRIBUTE = RequestFields::CHECKSUM;

    protected $map = [
        RequestFields::MERCHANT_ID              => NetbankingEntity::MERCHANT_CODE,
        RequestFields::PAYMENT_ID               => NetbankingEntity::PAYMENT_ID,
        RequestFields::AMOUNT                   => NetbankingEntity::AMOUNT,
        ResponseFields::BANK_PAYMENT_ID         => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::AUTH_STATUS             => NetbankingEntity::STATUS,
        NetbankingEntity::RECEIVED              => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $data = $this->getBaseRequestData($input, Constants::MODE_OF_TRANSACTION_PAYMENT);

        $data[RequestFields::DESCRIPTION] = $input['merchant']->getFilteredDba();

        $data[RequestFields::RETURN_URL] = $input['callbackUrl'];

        $data[RequestFields::CHECKSUM] = $this->generateHash($data);

        $this->createGatewayPaymentEntity($data);

        $request = $this->createRequest($data);

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
                'terminal_id'      => $input['payment']['terminal_id'],
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        $this->assertAmount(
            $this->formatAmount($input['payment']['amount']),
            $content[ResponseFields::AMOUNT]
        );

        $content[RequestFields::RETURN_URL] = $this->getReturnUrlForCallback();

        $this->checkForErrors($content);

        $content = $this->getArrayForChecksum($content);

        $this->verifySecureHash($content);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->checkCallbackStatus($content[ResponseFields::AUTH_STATUS]);

        $this->saveCallbackResponse($content, $gatewayEntity);

        $acquirerData = $this->getAcquirerData($input, $gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getBaseRequestData($input, $paymentMode)
    {
        $data = [
            RequestFields::MERCHANT_ID                  => $this->getMerchantId(),
            RequestFields::PAYMENT_ID                   => $input['payment']['id'],
            RequestFields::AMOUNT                       => $this->formatAmount($input['payment']['amount']),
            RequestFields::ACCOUNT_NUMBER               => Constants::NOT_APPLICABLE,
            RequestFields::MODE                         => $paymentMode,
        ];

        return $data;
    }

    protected function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function getReturnUrlForCallback()
    {
        return $this->app['request']->url();
    }

    public function generateHash($content)
    {
        return $this->getHashOfArray($content);
    }

    protected function getArrayForChecksum($content)
    {
        $data = [
            ResponseFields::MERCHANT_ID     => $content[ResponseFields::MERCHANT_ID],
            ResponseFields::PAYMENT_ID      => $content[ResponseFields::PAYMENT_ID],
            ResponseFields::AMOUNT          => $content[ResponseFields::AMOUNT],
            RequestFields::RETURN_URL       => $content[RequestFields::RETURN_URL],
            ResponseFields::ACCOUNT_NUMBER  => $content[ResponseFields::ACCOUNT_NUMBER],
            ResponseFields::MODE            => $content[ResponseFields::MODE],
            ResponseFields::DESCRIPTION     => $content[ResponseFields::DESCRIPTION],
            ResponseFields::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID],
            ResponseFields::AUTH_STATUS     => $content[ResponseFields::AUTH_STATUS],
            ResponseFields::CHECKSUM        => $content[ResponseFields::CHECKSUM],
        ];

        return $data;
    }

    protected function getHashOfArray($content)
    {
        $content[RequestFields::CHECKSUM] = $this->getSecret();

        $hashString = $this->getStringToHash($content, self::DELIMITER);

        return $this->getHashOfString($hashString);
    }

    protected function getHashOfString($str)
    {
        return strval(crc32($str));
    }

    protected function checkForErrors($content)
    {
        if ($content[ResponseFields::ERROR_MESSAGE] !== Constants::UNDEFINED and
            $content[ResponseFields::ERROR_CODE] !== Constants::UNDEFINED)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);
        }
    }

    protected function createRequest($content)
    {
        $request = $this->getStandardRequestArray();

        $content = http_build_query($content);

        $request['url'] .= '?' . $content;

        return $request;
    }

    protected function saveCallbackResponse(array $content, Base\Entity $gatewayEntity)
    {
        $attributes = [
            Base\Entity::RECEIVED           => true,
            Base\Entity::STATUS             => $content[ResponseFields::AUTH_STATUS],
            Base\Entity::BANK_PAYMENT_ID    => $content[ResponseFields::BANK_PAYMENT_ID] ?? null,
        ];

        $gatewayEntity->fill($attributes);

        $this->repo->saveOrFail($gatewayEntity);
    }

    protected function checkCallbackStatus(string $status)
    {
        if(in_array($status, Status::VALID_STATUS_LIST))
        {
            if ($status === Status::NO)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
            }
        }

        else
        {
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS);
        }
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequest($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->validateVerifyResponse($verify->verifyResponseContent);
    }

    protected function getVerifyRequest(Verify $verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $bankRefNumber = $gatewayPayment[Base\Entity::BANK_PAYMENT_ID];

        $data = $this->getBaseRequestData($input, Constants::MODE_OF_TRANSACTION_VERIFY);

        $data[RequestFields::VERIFY_BANK_PAYMENT_ID] = $bankRefNumber;

        $data[RequestFields::CHECKSUM] = $this->generateHash($data);

        $request = $this->getStandardRequestArray($data, 'get', Action::VERIFY);

        return $request;
    }

    protected function verifyPayment(Verify $verify)
    {
        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                                       $input['payment']['id'],
                                       Payment\Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Status::YES))
        {
            return true;
        }

        if (empty($input['gateway']['gateway_payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                null,
                $input);
        }

        $attrs = [
            Base\Entity::STATUS          => Status::YES,
            Base\Entity::BANK_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
        ];

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
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

        if ((isset($content[ResponseFields::VERIFY_STATUS]) === true) and
            ($content[ResponseFields::VERIFY_STATUS] === Status::YES))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyAttributesToSave(array $content, $gatewayPayment)
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::VERIFY_STATUS];
        }

        return $attributes;
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function parseVerifyResponse($body)
    {
        $content = $this->xmlToArray($body);

        list($key, $val) = explode('=', $content[ResponseFields::VERIFICATION]);

        unset($content[ResponseFields::VERIFICATION]);

        $content[$key] = $val;

        return $content;
    }

    protected function validateVerifyResponse($content)
    {
        if ($content[ResponseFields::VERIFY_CHECKSUM_STATUS] === Constants::FALSE)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED);
        }

        if (isset($content[ResponseFields::VERIFY_ERROR_MESSAGE]) === true and
            isset($content[ResponseFields::VERIFY_ERROR_CODE]) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);
        }
    }

    protected function getAuthSuccessStatus()
    {
        return Status::YES;
    }
}
