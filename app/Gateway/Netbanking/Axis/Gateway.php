<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    const MODE_CBC = 2;

    protected $map = [
        RequestFields::AMOUNT             => 'amount',
        RequestFields::MERCHANT_REFERENCE => 'payment_id',
        RequestFields::ITEM_CODE          => 'caps_payment_id'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entity = $this->getDefaultRequestData($input);

        $payment = $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $content = $this->getDataFromResponse($input['gateway']);

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

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);
    }

    public function verifyPayment($verify)
    {
        // Response XML
        $content = $verify->verifyResponseContent;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $content);

        $this->getVerifyStatus($verify, $content);

        $this->saveVerifyResponseIfNeeded($verify, $content);
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

        $verify->status = $status;

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
        $payment = $verify->payment;

        $data = [
            RequestFields::VERIFY_PAYEE_ID => $this->getMerchantId(),
            RequestFields::VERIFY_ITC      => strtoupper($payment['payment_id']),
            RequestFields::VERIFY_PRN      => $payment['payment_id'],
            RequestFields::VERIFY_DATE     => $this->getPaymentDate($payment),
            RequestFields::VERIFY_AMT      => $payment['amount'],
        ];

        return $data;
    }

    protected function getPaymentRequestData($input)
    {
        $encryptedString = $this->getAuthorizeEncryptedString($input);

        return [
            RequestFields::AUTHENTICATION_MENU_ID   => Constants::AUTH_MENU_ID,
            RequestFields::AUTHENTICATION_CALL_MODE => Constants::AUTH_CALL_MODE,
            RequestFields::CATEGORY_ID              => Constants::CATEGORY_ID,
            RequestFields::ENCRYPTED_STRING         => $encryptedString,
            RequestFields::RETURN_URL               => $input['callbackUrl']
        ];
    }

    protected function getAuthorizeEncryptedString($input)
    {
        $defaultData = $this->getDefaultRequestData($input);

        $data = [
            RequestFields::PAYEE_ID          => $this->getMerchantId(),
            RequestFields::MODE_OF_OPERATION => Constants::PAY,
            RequestFields::CURRENCY_CODE     => Constants::INDIAN_RUPEE,
            RequestFields::CONFIRMATION      => Constants::YES,
            RequestFields::RESPONSE          => Constants::RESPONSE
        ];

        // TODO: Add TPV to the request array

        $data = array_merge($defaultData, $data);

        $this->traceGatewayPaymentRequest($data, $input);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        return $this->encryptString($stringToEncrypt);
    }

    protected function getDefaultRequestData($input)
    {
        return [
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::ITEM_CODE          => strtoupper($input['payment']['id']),
            RequestFields::AMOUNT             => $input['payment']['amount'] /100
        ];
    }

    /*
     * @param associative array $data
     * @return string in key1~value1$key2~value2 format
     */
    protected function prepareStringToEncrypt(array $data)
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
        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $decryptedString = $this->decryptString(urldecode($encryptedString));

        parse_str($decryptedString, $response);

        $this->checkDecryptionFailure($encryptedString, $response);

        return $response;
    }

    protected function checkDecryptionFailure($encryptedString, $content)
    {
        if (empty($content) ===  true)
        {
            $this->trace->error(TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['encrypted_string' => $encryptedString]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }
    }


    protected function checkResponseStatus($attrs, $content)
    {
        if ((isset($attrs['status']) === false) or
            ($attrs['status'] !== Constants::YES))
        {
            $this->trace->error(
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
            'amount'            => $content[ResponseFields::AMOUNT],
            'bank_payment_id'   => $content[ResponseFields::BANK_REFERENCE_ID],
        ];
    }

    protected function saveVerifyResponseIfNeeded($verify, $content)
    {
        $gatewayPayment = $verify->payment;

        $attributes = $this->getVerifyAttributes($content);

        // Late authorization case
        if ($gatewayPayment[Base\Entity::RECEIVED] === false)
        {
            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    protected function getVerifyAttributes($content)
    {
        return [
            'received'          => true,
            'status'            => $content[ResponseFields::PAYMENT_STATUS],
            'amount'            => $content[ResponseFields::VERIFY_RESPONSE_AMT],
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

    public function encryptString(string $string)
    {
        $aes = $this->createAesCrypter();

        // returning Encrypted String
        return base64_encode($aes->encrypt($string));
    }

    public function decryptString(string $string)
    {
        $aes = $this->createAesCrypter();

        // returning Decrypted String
        return $aes->decrypt(base64_decode($string));
    }

    protected function createAesCrypter()
    {
        $masterKey = $this->getSecret();

        $aes = new AES(self::MODE_CBC);

        $aes->setKey($masterKey);

        $aes->setIV($masterKey);

        return $aes;
    }

    /*
     *  Overriding parent class's method
     */
    protected function getUrlDomain()
    {
        $this->domainType = $this->action;

        return parent::getUrlDomain();
    }

    public function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }
        else
        {
            return $this->getLiveMerchantId();
        }
    }
}
