<?php

namespace RZP\Gateway\Netbanking\Obc;

use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Entity as GatewayEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = Payment\Gateway::NETBANKING_OBC;

    /**
     * @var Crypto
     */
    protected $aesCrypto;

    protected $map = [
        Base\Entity::AMOUNT             => Base\Entity::AMOUNT,
        ResponseFields::PAID            => Base\Entity::STATUS,
        ResponseFields::BANK_PAYMENT_ID => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::DEBIT_ACC_NUM   => Base\Entity::ACCOUNT_NUMBER,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequestArray($input);

        $attributes = $this->getContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($attributes);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->parseGatewayResponse($input['gateway']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'content' => $content,
            ]);

        $this->assertPaymentId($input['payment']['id'], $content[RequestFields::PAY_REF_NUM]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->checkGatewayStatus($content);

        $this->verifyCallback($gatewayPayment, $input);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                                       $input['payment']['id'],
                                       Payment\Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return back.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Status::SUCCESS))
        {
            return true;
        }

        if (empty($input['gateway']['gateway_payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                null,
                $input);
        }

        $attributes = [
            Base\Entity::STATUS          => Status::SUCCESS,
            Base\Entity::BANK_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
        ];

        $gatewayPayment->fill($attributes);
        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    public function encrypt(string $stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->encryptString($stringToEncrypt);
    }

    public function decrypt(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestArray($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $verify->verifyResponse->body,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponseContent = $this->parseVerifyResponse($verify->verifyResponse, $verify->input['payment']);
    }

    protected function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyMatchStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->saveVerifyContent($verify);

        $verify->amountMismatch = $this->setVerifyAmountMismatch($verify);
    }

    protected function assertAmount($expectedAmount, $actualAmount)
    {
        $expectedAmount = $this->formatAmount($expectedAmount);

        $actualAmount = $this->formatAmount($actualAmount);

        parent::assertAmount($expectedAmount, $actualAmount);
    }

    protected function getContentToSave($payment): array
    {
        return [
            Base\Entity::AMOUNT     => $payment[Payment\Entity::AMOUNT],
            Base\Entity::REFERENCE1 => $this->getMerchantId(),
        ];
    }

    protected function verifyCallback(Base\Entity $gatewayPayment, $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        $verify->amountMismatch = $this->setVerifyAmountMismatch($verify);

        if ($verify->amountMismatch === true)
        {
            throw new Exception\LogicException(
                'Amount tampering found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
                null,
                null,
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                null,
                null,
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        if (isset($content[ResponseFields::AMOUNT]) === false)
        {
            return false;
        }

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);

        $actualAmount = $this->formatAmount($content[ResponseFields::AMOUNT]);

        return ($expectedAmount !== $actualAmount);
    }

    protected function parseVerifyResponse($response, $payment)
    {
        if ((strpos($response->body, 'No Records Fetched') !== false) or
            (empty($response->body) === true))
        {
           return [];
        }

        $response = str_replace('|', '&', $response->body);

        parse_str($response, $verifyResponseArray);

        return $verifyResponseArray;
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            return VerifyResult::STATUS_MISMATCH;
        }

        return VerifyResult::STATUS_MATCH;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::TXN_STATUS]) === true) and
            ($content[ResponseFields::TXN_STATUS] === Status::VERIFY_SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getVerifyAttributesToSave(array $content, Base\Entity $gatewayPayment)
    {
        $attributesToSave = $this->getMappedAttributes($content);

        $attributesToSave[Base\Entity::RECEIVED] = true;

        // If auth status was not success, we update the entity with verify status
        if (($gatewayPayment->getStatus() !== Status::SUCCESS) and (isset($content[ResponseFields::TXN_STATUS]) === true))
        {
            $attributesToSave[Base\Entity::STATUS] = $this->getAuthMappedVerifyStatus($content[ResponseFields::TXN_STATUS]);
        }

        return $attributesToSave;
    }

    protected function getAuthMappedVerifyStatus($verifyStatus)
    {
        return ($verifyStatus === Status::VERIFY_SUCCESS) ? Status::SUCCESS : Status::FAILED;
    }

    protected function checkGatewayStatus(array $content)
    {
        if ((empty($content[ResponseFields::PAID]) === true) or
            ($content[ResponseFields::PAID] !== Status::SUCCESS))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function getAuthorizeRequestArray(array $input)
    {
        $content = [
            RequestFields::RETURN_URL   => $this->encrypt($input['callbackUrl']),
            RequestFields::CATEGORY_ID  => Constant::CATEGORY_ID,
            RequestFields::QUERY_STRING => $this->getQueryString($input)
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getVerifyRequestArray(Verify $verify)
    {
        $payment = $verify->input['payment'];

        $content = [
            RequestFields::PAYEE_ID    => $this->getMerchantId(),
            RequestFields::PAY_REF_NUM => $payment['id'],
            RequestFields::ITEM_CODE   => strtoupper($payment['id']),
            RequestFields::AMOUNT      => $this->formatAmount($payment['amount'] / 100),
            RequestFields::RETURN_URL  => Constant::RAZORPAY_END_POINT,
            RequestFields::BID         => $verify->payment['bank_payment_id'] ?? '',
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function formatAmount(float $amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * This method maps the query array into the required query string format
     * For eg. $queryArray = ['key1' => 'value1', 'key2' => 'value2'] becomes key1~value1&key2~value2
     *
     * @param array $input
     * @return string
     */
    protected function getQueryString(array $input)
    {
        $content = [
            RequestFields::TRAN_CRN    => Currency::INR,
            RequestFields::TXN_AMOUNT  => $this->formatAmount($input['payment']['amount'] / 100),
            RequestFields::PAYEE_ID    => $this->getMerchantId(),
            RequestFields::PAY_REF_NUM => $input['payment']['id'],
            RequestFields::ITEM_CODE   => strtoupper($input['payment']['id']),
        ];

        $this->traceGatewayPaymentRequest($content, $input, TraceCode::GATEWAY_AUTH_REQUEST);

        $query = implode(
            '|',
            array_map(
                function($key, $value)
                {
                    return Constant::SHOPPING_MALL . $key . '~' . $value;
                },
                array_keys($content),
                array_values($content)
            ));

        return $this->encrypt($query);
    }

    protected function createCryptoIfNotCreated()
    {
        if ($this->aesCrypto === null)
        {
            $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $this->getSecret());
        }
    }

    protected function updateGatewayPaymentEntity(
        GatewayEntity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        $attr = $this->getMappedAttributes($attributes);

        $attr[Base\Entity::RECEIVED] = 1;

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();
    }

    protected function parseGatewayResponse(array $response)
    {
        $content = [];

        $encryptedString = array_keys($response)[0];

        $decryptedString = $this->decrypt($encryptedString);

        parse_str($decryptedString, $content);

        return $content;
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}
