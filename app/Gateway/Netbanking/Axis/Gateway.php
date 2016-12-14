<?php

namespace RZP\Gateway\Netbanking\Axis;

use Carbon\Carbon;
use RZP\Constants\Mode as RZPMode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
// use RZP\Gateway\Netbanking\Icici\AesTrait;

class Gateway extends Base\Gateway
{
    use GatewayBase\AuthorizeFailed;

    use AesTrait;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    const MODE_ECB = 1;

    protected $map = [
        RequestFields::AMOUNT                    => 'amount',
        RequestFields::MERCHANT_UNIQUE_REFERENCE => 'payment_id',
        RequestFields::ITEM_CODE                 => 'caps_payment_id'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entity = $this->getDefaultRequestData($input);

        $payment = $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace>info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $content);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], GatewayBase\Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $payment->fill($attrs);

        $this->repo->saveOrFail($payment);

        if ((isset($attrs['status']) === false) or
            ($attrs['status'] !== Constants::YES))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $verify;
    }

    public function verifyPayment($verify)
    {
        // Response XML
        $content = $verify->verifyResponseBody;

        $response = $this->parseResponseXml($content);

        $status = GatewayBase\VerifyResult::STATUS_MATCH;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $response);

        $verify->gatewaySuccess = false;
        $verify->apiSuccess = true;

        if ((isset($response[ResponseFields::PAYMENT_STATUS]) === true) and
            ($response[ResponseFields::PAYMENT_STATUS] === Constants::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        if ($input['payment']['status'] === 'failed' or
            $input['payment']['status'] === 'created')
        {
            $verify->apiSuccess = false;
        }

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = GatewayBase\VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === GatewayBase\VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $data = $this->getDefaultRequestData($input);

        $paymentDate = $this->getPaymentDate($payment);

        $pid = $this->getPid();

        // These two could be wrong
        $data[RequestFields::DATE] = $paymentDate;
        $data[RequestFields::PAYEE_ID] = $pid;

        return $data;
    }

    protected function getPaymentRequestData($input)
    {
        $pid = $this->getPid();

        $encryptedString = $this->getEncryptedString($input);

        return [
            RequestFields::PAYEE_ID         => $pid,
            RequestFields::ENCRYPTED_STRING => $encryptedString,
            RequestFields::RETURN_URL       => $input['callbackUrl']
        ];
    }

    protected function getEncryptedString($input)
    {
        $masterKey = $this->getMasterKey();

        $defaultData = $this->getDefaultRequestData($input);

        $data = [
            RequestFields::MODE_OF_OPERATION => Constants::PAY,
            RequestFields::CURRENCY_CODE     => Constants::INDIAN_RUPEE,
            RequestFields::CONFIRMATION      => Constants::YES,
            RequestFields::RESPONSE          => Constants::RESPONSE
        ];

        // if tpv is enabled, add tpv account number

        $data = array_merge($defaultData, $data);

        // trace before encryption
        $this->traceGatewayPaymentRequest($data, $input);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        return $this->encryptString($stringToEncrypt, $masterKey);
    }

    protected function getDefaultRequestData($input)
    {
        $paymentId = $input['payment']['id'];

        $amount = number_format($input['payment']['amount'] /100, 2, '.', ' ');

        return [
            RequestFields::MERCHANT_UNIQUE_REFERENCE => $paymentId,
            RequestFields::ITEM_CODE                 => strtoupper($paymentId),
            RequestFields::AMOUNT                    => $amount
        ];
    }

    protected function prepareStringToEncrypt($data)
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . '~' . $value;
        }

        $queryString = implode('$', $queryArray);



        return $queryString;
    }

    protected function createPaymentArray($content)
    {
        $amount = number_format($input['payment']['amount'] /100, 2, '.', ' ');

        return [
            RequestFields::AMOUNT => $amount
        ];
    }

    protected function getDataFromResponse($encryptedResponse)
    {
        $masterKey = $this->getMasterKey();

        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $decryptedString = $this->decryptString($encryptedString, $masterKey);

        parse_str($decryptedString, $response);

        return $response;
    }

    protected function getCallbackAttributes($content)
    {
        return [
            'received'          => true,
            'status'            => $content[ResponseFields::STATUS],
            'bank_payment_id'   => $content[ResponseFields::BANK_REFERENCE_ID],
        ];
    }

    protected function getPaymentDate($payment)
    {
        $timestamp = $payment['original']['created_at'];

        return date('Y-m-d', $timestamp);
    }

    protected function parseResponseXml($response)
    {
        $responseArray = (array) simplexml_load_string($response);

        // Lets assume we verify only one payment at a time
        // So the response will contain just 1 table at a time
        return (array) $responseArray['Table1'];
    }

    public function getMasterKey()
    {
        $masterKey = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === RZPMode::TEST)
        {
            $masterKey = $this->config['test_master_key'];
        }

        return $masterKey;
    }

    public function getPid()
    {
        $pid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === RZPMode::TEST)
        {
            $pid = $this->config['test_pid'];
        }

        return $pid;
    }
}
