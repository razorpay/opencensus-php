<?php

namespace RZP\Gateway\Netbanking\Indusind;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\AES;
use RZP\Models\Payment;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_indusind';

    protected $bank = 'indusind';

    protected $map = [
        RequestFields::AMOUNT             => 'amount',
        RequestFields::MERCHANT_REFERENCE => 'payment_id',
        RequestFields::ITEM_CODE          => 'caps_payment_id'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input, Constants::PAY);

        $entity = [RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100];

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
                           ['gateway_response' => $input['gateway'],
                            'payment_id'       => $input['payment']['id']]);

        $content = $this->getDataFromResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
             $content[RequestFields::MERCHANT_REFERENCE]);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkResponseStatus($attrs, $content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);
    }

    public function verifyPayment(Verify $verify)
    {
        // Response XML
        $content = $verify->verifyResponseContent;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $content);

        $this->getVerifyStatus($verify, $content);

        $this->saveVerifyResponseIfNeeded($verify, $content);
    }

    protected function getVerifyStatus(Verify $verify, array $response)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify, $response);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        if ($input['payment']['status'] === 'failed' or
            $input['payment']['status'] === 'created')
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify $verify, array $response)
    {
        $verify->gatewaySuccess = false;

        if ((isset($response[ResponseFields::PAYMENT_STATUS]) === true) and
            ($response[ResponseFields::PAYMENT_STATUS] === Constants::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getPaymentVerifyData(Verify $verify)
    {
        $payment = $verify->payment;

        $input = $verify->input;

        $data = $this->getPaymentRequestData($input, Constants::VERIFY);

        return $data;
    }

    protected function getPaymentRequestData(array $input, $mode)
    {
        $data = $this->createDefaultRequestData($input, $mode);

        $data[RequestFields::ENCRYPTED_STRING] = $this->getEncryptedString($input, $mode);

        return $data;
    }

    protected function getEncryptedString(array $input, $mode)
    {
        $data = $this->getAuthorizeRequestData($input);

        if ($mode === Constants::VERIFY)
        {
            $input[RequestFields::BANK_REFERENCE_ID] = $input['payment']['bank_payment_id'];
        }

        $this->traceGatewayPaymentRequest($data, $input);

        $queryString = $this->createQueryString($data);

        $masterKey = $this->getSecret();

        $aes = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        return base64_encode($aes->encryptString($queryString));
    }

    protected function getAuthorizeRequestData(array $input)
    {
        return [
            RequestFields::ITEM_CODE          => strtoupper($input['payment']['id']),
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::AMOUNT             => ($input['payment']['amount'] / 100),
            RequestFields::RETURN_URL         => $input['callbackUrl'],
            RequestFields::CURRENCY_CODE      => Currency::INR,
            RequestFields::CONFIRMATION       => Constants::YES,
        ];
    }

    protected function createDefaultRequestData(array $input, $mode)
    {
 
        $data = [
            RequestFields::MODE       => $mode,
            RequestFields::PAYEE_ID   => $this->getPid(),
            RequestFields::USER_TYPE  => Constants::RETAIL_USER
        ];

        if ($mode === Constants::VERIFY)
        {
            $data[RequestFields::PAYMENT_TYPE] = Constants::HOT_PAYMENT;
        }

        return $data;
    }

    /*
     * @param Eg. $data = ['PRN' => "6vTX585l2WP6Bq", 'MD' => "P"]
     * @return Eg. string "PRN=6vTX585l2WP6Bq&MD=P"
     */
    protected function createQueryString(array $data)
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . '=' . $value;
        }

        $queryString = implode('&', $queryArray);

        return $queryString;
    }

    protected function getDataFromResponse(array $encryptedResponse)
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $masterKey = $this->getSecret();

        $crypto = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        $encryptedString = str_replace(' ', '+', $encryptedString);

        $decryptedString = $crypto->decryptString(base64_decode($encryptedString));

        parse_str($decryptedString, $response);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $response);

        $this->checkDecryptionFailure($encryptedString, $response);

        return $response;
    }

    protected function checkDecryptionFailure(string $encryptedString, array $content)
    {
        if (empty($content) === true)
        {
            $this->trace->error(TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['encrypted_string' => $encryptedString,
                 'payment_id'       => $content[ResponseFields::MERCHANT_REFERENCE]]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }
    }


    protected function checkResponseStatus(array $attrs, array $content)
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

    public function getPid()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    protected function getCallbackAttributes(array $content)
    {
        return [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REFERENCE_ID]
        ];
    }

    protected function saveVerifyResponseIfNeeded(Verify $verify, array $content)
    {
        $gatewayPayment = $verify->payment;

        $bankPaymentId = $gatewayPayment->getBankPaymentId();

        $attributes = $this->getVerifyAttributes($content);

        if ($bankPaymentId === null)
        {
            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    protected function getVerifyAttributes(array $content)
    {
        return [
            'received'          => true,
            'status'            => $content[ResponseFields::PAYMENT_STATUS],
            'bank_payment_id'   => $content[ResponseFields::BANK_REFERENCE_ID],
        ];
    }

    protected function parseResponseXml(string $response)
    {
        $responseArray = (array) simplexml_load_string($response);

        // Lets assume we verify only one payment at a time
        // So the response will contain just 1 table at a time
        return (array) $responseArray['Table1'];
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

    protected function getLiveSecret()
    {
        assert ($this->mode === Mode::LIVE);

        return $this->config['live_hash_secret'];
    }
}

