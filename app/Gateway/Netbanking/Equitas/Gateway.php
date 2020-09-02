<?php

namespace RZP\Gateway\Netbanking\Equitas;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
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

        $data = $this->getAuthRequestData($input);

        $data[RequestFields::CHECKSUM] = $this->generateHash($data);

        $this->createGatewayPaymentEntity($data);

        $request = $this->createRequest($data);

        $traceData = $request;

        unset($traceData['content'][RequestFields::ACCOUNT_NUMBER]);

        $this->traceGatewayPaymentRequest($traceData, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $traceData = $content;

        unset($traceData[RequestFields::ACCOUNT_NUMBER]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $traceData,
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

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->checkCallbackStatus($content);

        $checksumInput = $this->getArrayForChecksum($content);

        $this->verifySecureHash($checksumInput);

        $this->saveCallbackResponse($content, $gatewayEntity);

        $this->verifyCallback($gatewayEntity, $input);

        $acquirerData = $this->getAcquirerData($input, $gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback($gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $response = $verify->verifyResponseContent;

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getAuthRequestData($input)
    {
        $accountNo = Constants::NOT_APPLICABLE;

        if ($input['merchant']->isTPVRequired())
        {
            $accountNo = $input['order']['account_number'];
        }

        return [
            RequestFields::MERCHANT_ID                  => $this->getMerchantId(),      // PID
            RequestFields::PAYMENT_ID                   => $input['payment']['id'],     //BRN
            RequestFields::AMOUNT                       => $this->formatAmount($input['payment']['amount']), //AMT
            RequestFields::RETURN_URL                   => $input['callbackUrl'], //
            RequestFields::ACCOUNT_NUMBER               => $accountNo,
            RequestFields::MODE                         => Constants::MODE_OF_TRANSACTION_PAYMENT,
            // TODO : should we send this ?
            RequestFields::DESCRIPTION                  => $input['merchant']->getFilteredDba(),
        ];
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

    protected function getArrayForChecksum($content)
    {
        $data = [
            ResponseFields::MERCHANT_ID     => $content[ResponseFields::MERCHANT_ID],
            ResponseFields::PAYMENT_ID      => $content[ResponseFields::PAYMENT_ID],
            ResponseFields::AMOUNT          => $content[ResponseFields::AMOUNT],
            RequestFields::RETURN_URL       => $this->getReturnUrlForCallback(),
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

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected function getHashOfString($str)
    {
        //return strval(crc32($str)); //changed to SHA-256
        $hashedString = hash(HashAlgo::SHA256, $str, false);

        return $hashedString;
    }

    protected function checkForErrors($content)
    {
        if ((isset($content[ResponseFields::ERROR_MESSAGE]) === true) and
            (isset($content[ResponseFields::ERROR_MESSAGE]) === true) and
            ($content[ResponseFields::ERROR_MESSAGE] !== Constants::UNDEFINED) and
            ($content[ResponseFields::ERROR_CODE] !== Constants::UNDEFINED))
        {
            //TODO : Ask for error code list
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }
    }

    protected function createRequest($content)
    {

        $request = $this->getStandardRequestArray($content);

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

    protected function checkCallbackStatus($content)
    {
        $status = trim($content[ResponseFields::AUTH_STATUS]);

        if(in_array($status, Status::VALID_STATUS_LIST))
        {
            if ($status === Status::NO)
            {
                $this->checkForErrors($content);

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

        //$this->validateVerifyResponse($verify->verifyResponseContent);
    }

    protected function getVerifyRequest(Verify $verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $data = [
            RequestFields::MERCHANT_ID                  => $this->getMerchantId(),      //PID
            RequestFields::PAYMENT_ID                   => $input['payment']['id'],     //BRN
            RequestFields::AMOUNT                       => $this->formatAmount($input['payment']['amount']), //AMT
            RequestFields::ACCOUNT_NUMBER               => Constants::NOT_APPLICABLE,   //ACCNO
            RequestFields::MODE                         => Constants::MODE_OF_TRANSACTION_VERIFY, //MODE
            RequestFields::VERIFY_BANK_PAYMENT_ID       => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID] ?? '', //TID
        ];

        $data[RequestFields::CHECKSUM] = $this->generateHash($data);

        $request = $this->getStandardRequestArray($data, 'get', Action::VERIFY);

        return $request;
    }

    protected function verifyPayment(Verify $verify)
    {
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

        if ((isset($content[ResponseFields::VERIFY_STATUS]) === true) and
            ($content[ResponseFields::VERIFY_STATUS] === Status::YES))
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
        if ((isset($content[ResponseFields::VERIFY_CHECKSUM_STATUS]) === true) and
            ($content[ResponseFields::VERIFY_CHECKSUM_STATUS] === Constants::FALSE))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED);
        }

        if ((isset($content[ResponseFields::VERIFY_ERROR_MESSAGE]) === true) and
            (isset($content[ResponseFields::VERIFY_ERROR_CODE]) === true))
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
