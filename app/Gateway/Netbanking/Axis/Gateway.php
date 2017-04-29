<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    protected $map = [
        RequestFields::AMOUNT             => 'amount',
        RequestFields::MERCHANT_REFERENCE => 'payment_id',
        RequestFields::ITEM_CODE          => 'caps_payment_id'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entityAttributes = $this->getEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttributes);

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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);
    }

    public function verifyPayment(Verify $verify)
    {
        $this->setVerifyStatus($verify);

        $verify->payment = $this->saveVerifyResponseIfNeeded($verify);
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

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

        if (($input['payment']['status'] === Payment\Status::FAILED) or
            ($input['payment']['status'] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $response = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($response[ResponseFields::PAYMENT_STATUS]) === true) and
            ($response[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getPaymentVerifyData(Verify $verify)
    {
        $input = $verify->input;

        $date = date('Y-m-d', $input['payment']['created_at']);

        $data = [
            RequestFields::VERIFY_PAYEE_ID => $this->getMerchantId(),
            RequestFields::VERIFY_ITC      => $this->getMerchantId(),
            RequestFields::VERIFY_PRN      => $input['payment']['id'],
            RequestFields::VERIFY_DATE     => $date,
            RequestFields::VERIFY_AMT      => $input['payment']['amount'] / 100,
        ];

        return $data;
    }

    protected function getPaymentRequestData(array $input)
    {
        $encryptedString = $this->getAuthorizeEncryptedString($input);

        return [
            RequestFields::ENCRYPTED_STRING         => $encryptedString,
            RequestFields::RETURN_URL               => $input['callbackUrl']
        ];
    }

    protected function getAuthorizeEncryptedString(array $input)
    {
        $defaultData = $this->getEntityAttributes($input);

        $data = [
            RequestFields::PAYEE_ID          => $this->getMerchantId(),
            RequestFields::MODE_OF_OPERATION => Constants::PAY,
            RequestFields::CURRENCY_CODE     => Currency::INR,
            RequestFields::CONFIRMATION      => Status::YES,
            RequestFields::RESPONSE          => Constants::RESPONSE
        ];

        if ($input['merchant']->isTPVRequired())
        {
            $data[RequestFields::BANK_ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $data = array_merge($defaultData, $data);

        $this->traceGatewayPaymentRequest($data, $input);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        return $crypto->encryptString($stringToEncrypt);
    }

    protected function getEntityAttributes(array $input)
    {
        return [
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::ITEM_CODE          => $this->getMerchantId(),
            RequestFields::AMOUNT             => $input['payment']['amount'] / 100
        ];
    }

    /*
     * @param Eg. $data = ['PRN' => "6vTX585l2WP6Bq", 'MD' => "P"]
     * @return Eg. string "PRN~6vTX585l2WP6Bq$MD~P"
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

    protected function getDataFromResponse(array $encryptedResponse)
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $decryptedString = $crypto->decryptString($encryptedString);

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
            ($attrs['status'] !== Status::YES))
        {
            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function getCallbackAttributes(array $content)
    {
        return [
            'received'        => true,
            'status'          => $content[ResponseFields::STATUS],
            'bank_payment_id' => $content[ResponseFields::BANK_REFERENCE_ID],
        ];
    }

    protected function saveVerifyResponseIfNeeded(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        if ((isset($content[ResponseFields::PAYMENT_STATUS])) and
            ($content[ResponseFields::PAYMENT_STATUS] === Status::SUCCESS))
        {
            $attributes = $this->getVerifyAttributes($verify, $gatewayPayment);

            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    protected function getVerifyAttributes(Verify $verify, $gatewayPayment)
    {
        $content = $verify->verifyResponseContent;

        $bankPaymentId = $gatewayPayment->getBankPaymentId();

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            // We're saving the response only if status is a success
            $attributes[Base\Entity::STATUS] = Status::YES;
        }

        if (empty($bankPaymentId) === true)
        {
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_REFERENCE_ID];
        }

        return $attributes ?? [];
    }

    protected function parseResponseXml(string $response)
    {
        if (empty($response) === false)
        {
            $responseArray = (array) simplexml_load_string($response);

            // Lets assume we verify only one payment at a time
            // So the response will contain just 1 table at a time
            return (array) $responseArray['Table1'];
        }

        return $response;
    }

    protected function getAuthSuccessStatus()
    {
        return Status::getAuthSuccessStatus();
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

    protected function getLiveSecret()
    {
        assert ($this->mode === Mode::LIVE);

        return $this->config['live_hash_secret'];
    }
}
