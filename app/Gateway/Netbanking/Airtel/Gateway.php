<?php

namespace RZP\Gateway\Netbanking\Airtel;

use Carbon\Carbon;
use RZP\Gateway\Base\Action;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;

class Gateway extends Base\Gateway
{
    use GatewayBase\AuthorizeFailed;

    protected $gateway = 'netbanking_airtel';

    protected $bank = 'airtel';

    protected $map = [
        AuthFields::AMOUNT => 'amount'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->createAuthorizeRequestData($input);

        $entity = $this->createPaymentArray($input);

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->traceGatewayPaymentResponse($content, $input);

        $this->verifySecureHash($content);

        $attributes = $this->getCallackAttributes($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], GatewayBase\Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        $this->checkActionStatus($content);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $this->sendRefundRequestAndCheckResponse($request, $input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new GatewayBase\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $responseArray);

        $this->verifySecureHash($responseArray);
    }

    public function verifyPayment($verify)
    {
        $response = $verify->verifyResponseContent;

        $this->checkActionStatus($response);

        $status = $this->getVerifyMatchStatus($verify, $response);

        $verify->match = ($status === GatewayBase\VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    protected function getVerifyMatchStatus($verify, $response)
    {
        $status = GatewayBase\VerifyResult::STATUS_MATCH;

        $this->setApiSuccess($verify);

        $this->setGatewaySuccess($verify, $response[VerifyFields::TRANSACTION][0]);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = GatewayBase\VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function setApiSuccess($verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function setGatewaySuccess($verify, $response)
    {
        $verify->gatewaySuccess = false;

        if ((isset($response[VerifyFields::STATUS]) === true) and
            ($response[VerifyFields::STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function createAuthorizeRequestData($input)
    {
        $date = Carbon::createFromTimestamp(
            $input['payment']['created_at'], 'Asia/Kolkata')
            ->format('dmYhis');

        $data = [
            AuthFields::MERCHANT_ID              => $this->getMerchantId(),
            AuthFields::TRANSACTION_REFERENCE_NO => $input['payment']['id'],
            AuthFields::AMOUNT                   => $input['payment']['amount'] / 100,
            AuthFields::DATE                     => $date,
            AuthFields::SERVICE                  => Service::NETBANKING,
            AuthFields::SUCCESS_URL              => $input['callbackUrl'],
            AuthFields::FAILURE_URL              => $input['callbackUrl'],
            AuthFields::CURRENCY                 => Constants::INDIAN_RUPEE,
            AuthFields::CUSTOMER_MOBILE          => $input['payment']['contact'],
            AuthFields::CUSTOMER_EMAIL           => $input['payment']['email'],
        ];

        $data[AuthFields::HASH] = $this->getHashOfArray($data, true);

        return $data;
    }

    protected function createPaymentArray($input)
    {
        $amount = $input['payment']['amount'] / 100;

        return [
            AuthFields::AMOUNT => $amount
        ];
    }

    protected function getCallackAttributes($content)
    {
        try
        {
            $attributes = [
                Base\Entity::RECEIVED        => true,
                Base\Entity::STATUS          => $content[AuthFields::STATUS],
                Base\Entity::BANK_PAYMENT_ID => $content[AuthFields::TRANSACTION_ID],
                Base\Entity::MERCHANT_CODE   => $content[AuthFields::CODE],
                Base\Entity::ERROR_MESSAGE   => $content[AuthFields::MSG],
                Base\Entity::DATE            => $content[AuthFields::TRANSACTION_DATE],
            ];
        }

        catch(Exception $e)
        {
            throw new Exception\GatewayErrorException($e->getMessage());
        }

        return $attributes;
    }

    protected function sendRefundRequestAndCheckResponse($request, $input)
    {
        $response = $this->sendGatewayRequest($request);

        $content = $response->body;

        $responseArray = $this->jsonToArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            (array) $responseArray);

        $this->verifySecureHash($responseArray);

        $attributes = $this->getRefundAttributes($responseArray, $input);

        $this->createGatewayActionEntity($responseArray);

        $this->checkActionStatus($responseArray);
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;

        $date = Carbon::createFromTimestamp(
            $input['payment']['created_at'], 'Asia/Kolkata')
            ->format('dmYhis');

        $merchantId = $this->getMerchantId();

        $paymentId = $input['payment']['id'];

        $amount = $input['payment']['amount'] / 100;

        $data = [
            VerifyFields::SESSION_ID               => uniqid(),
            VerifyFields::TRANSACTION_REFERENCE_NO => $paymentId,
            VerifyFields::TRANSACTION_DATE         => $date,
            VerifyFields::MERCHANT_ID              => $merchantId,
            VerifyFields::HASH                     => '',
            VerifyFields::AMOUNT                   => "$amount"
        ];

        $data[VerifyFields::HASH] = $this->getHashOfArray($data, true);

        return json_encode($data);
    }

    protected function getRefundRequestData($input)
    {
        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], GatewayBase\Action::AUTHORIZE);

        $tranId = $payment['bank_payment_id'];

        $date = Carbon::createFromTimestamp(
            $input['payment']['created_at'], 'Asia/Kolkata')
            ->format('dmYhis');

        $merchantId = $this->getMerchantId();

        $amount = $input['refund']['amount'] / 100;

        $request = [
            RefundFields::SESSION_ID        => uniqid(),
            RefundFields::TRANSACTION_ID    => $tranId,
            RefundFields::TRANSACTION_DATE  => $date,
            RefundFields::REQUEST           => Constants::REVERSAL,
            RefundFields::MERCHANT_ID       => $merchantId,
            RefundFields::AMOUNT            => "$amount"
        ];

        $hash = $this->getHashOfArray($request, true);

        $request[RefundFields::HASH] = $hash;

        return json_encode($request);
    }

    protected function getRefundAttributes($response, $input)
    {
        try
        {
            $attributes = [
                Base\Entity::RECEIVED        => true,
                Base\Entity::AMOUNT          => $input['payment']['amount'] / 100,
                Base\Entity::BANK_PAYMENT_ID => $response[RefundFields::TRANSACTION_ID],
                Base\Entity::STATUS          => $response[RefundFields::STATUS],
                Base\Entity::REFUND_ID       => $input['refund']['id'],
                Base\Entity::DATE            => $response[RefundFields::TRANSACTION_DATE],
                Base\Entity::ERROR_MESSAGE   => $response[RefundFields::MESSAGE_TEXT],
                Base\Entity::MERCHANT_CODE   => $response[RefundFields::CODE],
            ];
        }

        catch(Exception $e)
        {
            throw new Exception\GatewayErrorException($e->getMessage());
        }

        return $attributes;
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getHashValueFromContent(array $content)
    {
        switch ($this->action)
        {
            case Action::CALLBACK:
                return $content[AuthFields::HASH];

            case Action::VERIFY:
                return $content[VerifyFields::HASH];

            case Action::REFUND:
                return $content[RefundFields::HASH];
        }
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getHashOfArray($content, $request = false)
    {
        $hashString = $this->getStringToHash($content, '#', $request);

        return $this->getHashOfString($hashString);
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getStringToHash($content, $glue = '', $request = false)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $data = $this->getAuthorizeRequestHashArray($content);
                break;

            case Action::CALLBACK:
                $data = $this->getCallbackResponseHashArray($content);
                break;

            case Action::VERIFY:
                if ($request === true)
                {
                    $data = $this->getVerifyRequestHashArray($content);
                }
                else
                {
                    $data = $this->getVerifyResponseHashArray($content);
                }
                break;

            case Action::REFUND:
                if ($request === true)
                {
                    $data = $this->getRefundRequestHashArray($content);
                }
                else
                {
                    $data = $this->getRefundResponseHashArray($content);
                }
                break;
        }

        $salt = $this->getSalt();

        array_push($data, $salt);

        return implode($glue, $data);
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    public function getHashOfString($string)
    {
        return hash(HashAlgo::SHA512, $string);
    }

    protected function getAuthorizeRequestHashArray($content)
    {
        return [
            AuthFields::MERCHANT_ID              => $content[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_REFERENCE_NO => $content[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::AMOUNT                   => $content[AuthFields::AMOUNT],
            AuthFields::DATE                     => $content[AuthFields::DATE],
            AuthFields::SERVICE                  => $content[AuthFields::SERVICE],
        ];
    }

    protected function getCallbackResponseHashArray($content)
    {
        return [
            AuthFields::MERCHANT_ID              => $content[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_ID           => $content[AuthFields::TRANSACTION_ID],
            AuthFields::TRANSACTION_REFERENCE_NO => $content[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::TRANSACTION_AMOUNT       => $content[AuthFields::TRANSACTION_AMOUNT],
            AuthFields::TRANSACTION_DATE         => $content[AuthFields::TRANSACTION_DATE],
        ];
    }

    protected function getVerifyRequestHashArray($data)
    {
        return [
            $data[VerifyFields::MERCHANT_ID],
            $data[VerifyFields::TRANSACTION_REFERENCE_NO],
            $data[VerifyFields::AMOUNT],
            $data[VerifyFields::TRANSACTION_DATE],
        ];
    }

    protected function getVerifyResponseHashArray($content)
    {
        $verifyJson = json_encode($content[VerifyFields::TRANSACTION]);

        return [
            $content[VerifyFields::MERCHANT_ID],
            $verifyJson,
            $content[VerifyFields::ERROR_CODE]
        ];
    }

    protected function getRefundRequestHashArray($data)
    {
        return [
            $data[RefundFields::MERCHANT_ID],
            $data[RefundFields::TRANSACTION_ID],
            $data[RefundFields::AMOUNT],
            $data[RefundFields::TRANSACTION_DATE],
        ];
    }

    protected function getRefundResponseHashArray($data)
    {
        return [
            $data[RefundFields::MERCHANT_ID],
            $data[RefundFields::ERROR_CODE],
            $data[RefundFields::AMOUNT],
            $data[RefundFields::TRANSACTION_ID],
            $data[RefundFields::TRANSACTION_DATE],
            $data[RefundFields::STATUS]
        ];
    }

    protected function checkActionStatus($content)
    {
        switch($this->action)
        {
            case Action::CALLBACK:
                if ((isset($content[AuthFields::STATUS]) === false) or
                    ($content[AuthFields::STATUS] !== Status::SUCCESS))
                {
                    $this->throwException($content, AuthFields::CODE);
                }
                break;

            case Action::REFUND:
                if ((isset($content[RefundFields::CODE]) === false) or
                    ($content[RefundFields::CODE] !== Code::SUCCESS))
                {
                    $this->throwException($content, RefundFields::ERROR_CODE);
                }
                break;

            case Action::VERIFY:
                if ((isset($content[VerifyFields::CODE]) === false) or
                    ($content[VerifyFields::CODE] !== Code::SUCCESS))
                {
                    $this->throwException($content, VerifyFields::ERROR_CODE);
                }
                break;
        }
    }

    protected function throwException($content, $code)
    {
        $errorDescription = ErrorCodes::getErrorCodeDescription(
                $content[$code]);

        $errorCode = ErrorCodes::getErrorCodeMap(
            $content[$code]);

        $this->trace->info(
            TraceCode::PAYMENT_CALLBACK_FAILURE,
            ['content' => $content]);

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException($errorCode, $code, $errorDescription);
    }

    public function getMerchantId()
    {
        $mid = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    public function getSalt()
    {
        $salt = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === Mode::TEST)
        {
            $salt = $this->getTestSecret();
        }

        return $salt;
    }
}
