<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use phpseclib\Crypt\AES;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    const MODE_CBC = 2;

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

        sd($request);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace>info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $content);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $payment->fill($attrs);

        $this->repo->saveOrFail($payment);

        $this->checkResponseStatus($attrs, $content);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

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

        $verify->verifyResponseBody = $response->body;
    }

    public function verifyPayment($verify)
    {
        // Response XML
        $content = $verify->verifyResponseBody;

        $response = $this->parseResponseXml($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $response);

        $this->getVerifyStatus($verify, $response);
    }

    protected function getVerifyStatus($verify, $response)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify, $response);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;
    }

    protected function checkApiSuccess($verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        if ($input['payment']['status'] === 'failed' or
            $input['payment']['status'] === 'created')
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess($verify, $response)
    {
        $verify->gatewaySuccess = false;

        if ((isset($response[ResponseFields::PAYMENT_STATUS]) === true) and
            ($response[ResponseFields::PAYMENT_STATUS] === Constants::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $data = $this->getDefaultRequestData($input);

        $paymentDate = $this->getPaymentDate($payment);

        $pid = $this->getPid();

        $data[RequestFields::DATE] = $paymentDate;
        $data[RequestFields::PAYEE_ID] = $pid;

        return $data;
    }

    protected function getPaymentRequestData($input)
    {
        $encryptedString = $this->getAuthorizeEncryptedString($input);

        return [
            // RequestFields::PAYEE_ID         => $this->getPid(),
            RequestFields::ENCRYPTED_STRING => $encryptedString,
            RequestFields::RETURN_URL       => $input['callbackUrl']
        ];
    }

    protected function getAuthorizeEncryptedString($input)
    {
        $masterKey = $this->getSecret();

        $defaultData = $this->getDefaultRequestData($input);

        $data = [
            RequestFields::PAYEE_ID         => $this->getPid(),
            RequestFields::MODE_OF_OPERATION => Constants::PAY,
            RequestFields::CURRENCY_CODE     => Constants::INDIAN_RUPEE,
            RequestFields::CONFIRMATION      => Constants::YES,
            RequestFields::RESPONSE          => Constants::RESPONSE
        ];

        // TODO: Add TPV to the request array

        $data = array_merge($defaultData, $data);

        // trace before encryption
        $this->traceGatewayPaymentRequest($data, $input);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        // sd($stringToEncrypt);

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

    protected function getDataFromResponse($encryptedResponse)
    {
        $masterKey = $this->getSecret();

        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $decryptedString = $this->decryptString($encryptedString, $masterKey);

        parse_str($decryptedString, $response);

        $this->checkDecryptionFailure($encryptedString, $response);

        return $response;
    }

    protected function checkDecryptionFailure($encryptedString, $content)
    {
        if (empty($content) ===  true)
        {
            $this->trace->error(TraceCode::PAYMENT_CALLBACK_FAILURE,
                [$encryptedString]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }
    }


    protected function checkResponseStatus($attrs, $content)
    {
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

    public function encryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_CBC);
        $aes->setKey($masterKey);

        // returning Encrypted String
        return base64_encode($aes->encrypt($string));
    }

    public function decryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_CBC);
        $aes->setKey($masterKey);

        $encryptedString = base64_decode($string);

        // returning Decrypted String
        return $aes->decrypt($encryptedString);
    }

    public function getPid()
    {
        $pid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $pid = $this->config['test_merchant_id'];
        }

        return $pid;
    }
}
