<?php

namespace RZP\Gateway\Netbanking\Indusind;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

use phpseclib\Crypt\AES;

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

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $attrs = $this->getAuthGatewayPaymentAttributes($input);

        $this->createGatewayPaymentEntity($attrs);

        $content = $this->getRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway_response' => $input['gateway'],
                'payment_id'       => $input['payment']['id'],
            ]
        );

        $content = $this->getDataFromCallbackResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
             $content[RequestFields::MERCHANT_REFERENCE]);

        $this->saveCallbackResponse($content);

        $this->checkCallbackStatus($content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getAuthGatewayPaymentAttributes(array $input): array
    {
        return [
            RequestFields::AMOUNT => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT])
        ];
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

        $verify->status = $this->getVerifyStatus($verify, $content);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyStatus(Verify $verify, array $response) :string
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

    protected function getPaymentVerifyData(Verify $verify): array
    {
        $payment = $verify->payment;

        $input = $verify->input;

        $data = $this->getRequestData($input, $payment);

        return $data;
    }

    protected function getRequestData(array $input, array $payment = [])
    {
        $data = [
            RequestFields::MODE       => Constants::PAY,
            RequestFields::PAYEE_ID   => $this->getPid(),
            RequestFields::USER_TYPE  => User::RETAIL,
        ];

        if ($this->action === Action::AUTHORIZE)
        {
            $data[RequestFields::ENCRYPTED_STRING] = $this->getAuthorizeEncryptedString($input);
        }
        else
        {
            $data[RequestFields::ENCRYPTED_STRING] =  $this->getVerifyEncryptedString($input, $payment);
        }

    }

    protected function getVerifyEncryptedString(array $input, array $payment): string
    {
        $data = [
            RequestFields::ITEM_CODE          => $input['payment']['id'],
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::AMOUNT             => $this->formatAmount($input['payment']['amount']),
            RequestFields::CURRENCY_CODE      => Currency::INR,
            RequestFields::CONFIRMATION       => Constants::YES,
            RequestFields::RETURN_URL         => 'na',
            RequestFields::BANK_REFERENCE_ID  => $payment[Base\Entity::BANK_PAYMENT_ID],
        ];

        $queryString = http_build_query($data);

        return $this->encryptString($queryString);
    }

    protected function getAuthorizeEncryptedString(array $input): string
    {
        $data = [
            RequestFields::ITEM_CODE          => strtoupper($input['payment']['id']),
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::AMOUNT             => $this->formatAmount($input['payment']['amount']),
            RequestFields::CURRENCY_CODE      => Currency::INR,
            RequestFields::CONFIRMATION       => Constants::YES,
            RequestFields::RETURN_URL         => $input['callbackUrl'],
        ];

        $queryString = http_build_query($data);

        return $this->encryptString($queryString);
    }

    protected function getDataFromCallbackResponse(array $encryptedResponse): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_STRING];

        $decryptedString = $this->decryptString($encryptedString);

        $response = [];

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
                [
                    'encrypted_string' => $encryptedString
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }
    }


    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::PAID]) === false) or
            ($content[ResponseFields::PAID] !== Constants::YES))
        {
            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    public function getPid(): string
    {
        $pId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $pId = $this->getTestMerchantId();
        }

        return $pId;
    }

    protected function saveCallbackResponse(array $content)
    {
        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $content[RequestFields::MERCHANT_REFERENCE], Action::AUTHORIZE);

        $attrs = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::PAID],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REFERENCE_ID]
        ];

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);
    }

    protected function saveVerifyContent(Verify $verify): Base\Entity
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributes($content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributes(array $content): array
    {
        return [
            Base\Entity::RECEIVED => true,
            Base\Entity::STATUS   => $content[ResponseFields::VERIFICATION],
        ];
    }

    protected function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    protected function getMerchantId(): string
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

    protected function getLiveSecret(): string
    {
        assert ($this->mode === Mode::LIVE);

        return $this->config['live_hash_secret'];
    }

    public function getSecret(): string
    {
        $secret = parent::getSecret();

        return pack('H*', $secret);
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    public function encryptString(string $queryString): string
    {
        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->encryptString($queryString);
    }

    public function decryptString(string $encryptedString): string
    {
        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->decryptString($encryptedString);
    }
}

