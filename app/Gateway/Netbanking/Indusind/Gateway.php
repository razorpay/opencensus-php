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

        $content = $this->getAuthorizeRequestData($input);

        $entity = $this->getAuthGatewayPaymentAttributes($input);

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway_response' => $input['gateway'],
                'payment_id'       => $input['payment']['id'],
            ]
        );

        $content = $this->getDataFromResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
             $content[RequestFields::MERCHANT_REFERENCE]);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->saveCallbackResponse($content);

        $this->checkCallbackStatus($attrs, $content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getAuthGatewayPaymentAttributes($input)
    {
        return [RequestFields::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100];
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request
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

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);
    }

    public function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->status = $this->getVerifyMatchStatus($verify, $content);

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify, array $response)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify, $response);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
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

        if ((isset($response[ResponseFields::VERIFICATION]) === true) and
            ($response[ResponseFields::VERIFICATION] === Constants::YES))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getPaymentVerifyData(Verify $verify)
    {
        $payment = $verify->payment;

        $input = $verify->input;

        $data = $this->getVerifyRequestData($input);

        return $data;
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $data = [
            RequestFields::MODE       => Constants::PAY,
            RequestFields::PAYEE_ID   => $this->getPid(),
            RequestFields::USER_TYPE  => Constants::RETAIL_USER,
        ];

        $data[RequestFields::ENCRYPTED_STRING] = $this->getAuthorizeEncryptedString($input);

        return $data;
    }

    protected function getVerifyRequestData(array $input)
    {
        $data = [
            RequestFields::MODE         => Constants::VERIFY,
            RequestFields::PAYEE_ID     => $this->getPid(),
            RequestFields::USER_TYPE    => Constants::RETAIL_USER,
        ];

        $data[RequestFields::ENCRYPTED_STRING] = $this->getVerifyEncryptedString($input);

        return $data;
    }

    protected function getVerifyEncryptedString($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $data = [
            RequestFields::ITEM_CODE          => strtoupper($input['payment']['id']),
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::AMOUNT             => ($input['payment']['amount'] / 100),
            RequestFields::CURRENCY_CODE      => Currency::INR,
            RequestFields::CONFIRMATION       => Constants::YES,
            RequestFields::RETURN_URL         => "dummy",
            RequestFields::BANK_REFERENCE_ID  => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
        ];

        $queryString = $this->createQueryString($data);

        $masterKey = $this->getSecret();

        $aes = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        return strtoupper(bin2hex($aes->encryptString($queryString)));
    }

    protected function getAuthorizeEncryptedString($input)
    {
        $data = [
            RequestFields::ITEM_CODE          => strtoupper($input['payment']['id']),
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::AMOUNT             => ($input['payment']['amount'] / 100),
            RequestFields::CURRENCY_CODE      => Currency::INR,
            RequestFields::CONFIRMATION       => Constants::YES,
            RequestFields::RETURN_URL         => $input['callbackUrl'],
        ];

        $queryString = $this->createQueryString($data);

        $masterKey = $this->getSecret();

        $aes = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        return strtoupper(bin2hex($aes->encryptString($queryString)));
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

        $masterKey = $this->getsecret();

        $crypto = new Base\AESCrypto(AES::MODE_ECB, $masterKey);

        $decryptedString = $crypto->decryptString(hex2bin($encryptedString));

        parse_str($decryptedString, $response);

        $this->checkDecryptionFailure($encryptedString, $response);

        return $response;
    }

    protected function checkDecryptionFailure(string $encryptedString, array $content)
    {
        if (empty($content) === true)
        {
            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['encrypted_string' => $encryptedString]
            );

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }
    }


    protected function checkCallbackStatus(array $attrs, array $content)
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

    protected function saveCallbackResponse(array $content)
    {
        $attrs = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REFERENCE_ID]
        ];

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributes($content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributes(array $content)
    {
        return [
            'received'          => true,
            'status'            => $content['VERIFICATION'],
        ];
    }

    protected function parseResponseXml(string $response)
    {
        $response = trim($response);

        $responseArray = (array) simplexml_load_string($response);

        return $responseArray;
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

    public function getSecret()
    {
        $secret = parent::getSecret();

        return pack('H*', $secret);
    }
}

