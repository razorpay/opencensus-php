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
use RZP\Gateway\Netbanking\Base as Netbanking;

class Gateway extends Base\Gateway
{
    use GatewayBase\AuthorizeFailed;

    protected $gateway = 'netbanking_airtel';

    protected $bank = 'airtel';

    protected $map = [
        AuthFields::AMOUNT => 'amount'
    ];

    protected $request = true;

    const STATUS_MATCH = GatewayBase\VerifyResult::STATUS_MATCH;

    const STATUS_MISMATCH = GatewayBase\VerifyResult::STATUS_MISMATCH;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->createAuthorizeRequestData($input);

        $entity = $this->createPaymentArray($input);

        $payment = $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->traceGatewayPaymentResponse($content, $input);

        $this->request = false;

        $this->verifySecureHash($content);

        $attributes = $this->getCallackAttributes($content);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], GatewayBase\Action::AUTHORIZE);

        $payment->fill($attributes);

        $payment->saveOrFail();

        $this->assertCallbackAttributes($attributes, $content);
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
    }

    public function verifyPayment($verify)
    {
        $response = $verify->verifyResponseContent;

        $this->request = false;

        $this->verifySecureHash($response);

        $status = $this->getVerifyStatus($verify, $response);

        $verify->match = ($status === self::STATUS_MATCH) ? true : false;

        return $status;
    }

    protected function getVerifyStatus($verify, $response)
    {
        $status = self::STATUS_MATCH;

        $this->setApiSuccess($verify);

        $this->setGatewaySuccess($verify, $response[VerifyFields::TRANSACTION][0]);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = self::STATUS_MISMATCH;
        }

        $this->checkVerifyStatus($response);

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
        $mid = $this->getMerchantId();

        $amount = $input['payment']['amount'] / 100;

        $date = Carbon::createFromTimestamp(
            $input['payment']['created_at'], 'Asia/Kolkata')
            ->format('dmYhis');

        $callbackUrl = $input['callbackUrl'];

        $data = [
            AuthFields::MERCHANT_ID              => $mid,
            AuthFields::TRANSACTION_REFERENCE_NO => $input['payment']['id'],
            AuthFields::AMOUNT                   => $amount,
            AuthFields::DATE                     => $date,
            AuthFields::SERVICE                  => Service::NETBANKING,
            AuthFields::SUCCESS_URL              => $callbackUrl,
            AuthFields::FAILURE_URL              => $callbackUrl,
            AuthFields::CURRENCY                 => Constants::INDIAN_RUPEE,
            AuthFields::CUSTOMER_MOBILE          => $input['payment']['contact'],
            AuthFields::CUSTOMER_EMAIL           => $input['payment']['email'],
        ];

        $this->request = true;

        $data[AuthFields::HASH] = $this->generateHash($data);

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
        return [
            Netbanking\Entity::RECEIVED        => true,
            Netbanking\Entity::STATUS          => $content[AuthFields::STATUS],
            Netbanking\Entity::BANK_PAYMENT_ID => $content[AuthFields::TRANSACTION_ID],
            Netbanking\Entity::MERCHANT_CODE   => $content[AuthFields::CODE],
            Netbanking\Entity::ERROR_MESSAGE   => $content[AuthFields::MSG],
            Netbanking\Entity::DATE            => $content[AuthFields::TRANSACTION_DATE],
        ];
    }

    protected function assertCallbackAttributes($attributes, $content)
    {
        if ($attributes[Netbanking\Entity::STATUS] !== Status::SUCCESS)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function sendRefundRequestAndCheckResponse($request, $input)
    {
        $response = $this->sendGatewayRequest($request);

        $content = $response->body;

        $responseArray = $this->jsonToArray($content);

        $this->request = false;

        $this->verifySecureHash($responseArray);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            (array) $responseArray);

        $attributes = $this->getRefundAttributes($responseArray, $input);

        $this->createGatewayActionEntity($responseArray);

        $this->checkRefundStatus($attributes, $responseArray);
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

        $this->request = true;

        $data[VerifyFields::HASH] = $this->generateHash($data);

        return json_encode($data);
    }

    protected function getVerifyResponseArray($content)
    {
        $response = $this->jsonToArray($content);

        $responseArray = (array) $response[VerifyFields::TRANSACTION][0];

        $this->request = false;

        $this->verifySecureHash($responseArray);

        return $responseArray;
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

        $this->request = true;

        $hash = $this->generateHash($request);

        $request[RefundFields::HASH] = $hash;

        return json_encode($request);
    }

    protected function checkRefundStatus($attributes, $refundArray)
    {
        if ((isset($attributes[Netbanking\Entity::MERCHANT_CODE]) === false) or
            ($attributes[Netbanking\Entity::MERCHANT_CODE] !== Code::SUCCESS))
        {
            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                $refundArray);

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED);
        }
    }

    protected function getRefundAttributes($response, $input)
    {
        try
        {
            $attrs = [
                Netbanking\Entity::RECEIVED        => true,
                Netbanking\Entity::AMOUNT          => $input['payment']['amount'] / 100,
                Netbanking\Entity::BANK_PAYMENT_ID => $response[RefundFields::TRANSACTION_ID],
                Netbanking\Entity::STATUS          => $response[RefundFields::STATUS],
                Netbanking\Entity::REFUND_ID       => $input['refund']['id'],
                Netbanking\Entity::DATE            => $response[RefundFields::TRANSACTION_DATE],
                Netbanking\Entity::ERROR_MESSAGE   => $response[RefundFields::MESSAGE_TEXT],
                Netbanking\Entity::MERCHANT_CODE   => $response[RefundFields::CODE],
            ];
        }

        catch(Exception $e)
        {
            throw new Exception\GatewayErrorException($e->getMessage());
        }

        return $attrs;
    }

    protected function checkVerifyStatus($response)
    {
        if ((isset($response[VerifyFields::CODE]) === false) or
            ($response[VerifyFields::CODE] !== Code::SUCCESS))
        {
            $this->trace->error(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $response);

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
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
    protected function getHashOfArray($content)
    {
        $hashString = $this->getStringToHash($content, '#');

        return $this->getHashOfString($hashString);
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getStringToHash($content, $glue = '')
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
                if ($this->request === true)
                {
                    $data = $this->getVerifyRequestHashArray($content);
                }
                else
                {
                    $data = $this->getVerifyResponseHashArray($content);
                }
                break;

            case Action::REFUND:
                if ($this->request === true)
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
