<?php

namespace RZP\Gateway\Netbanking\Obc;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;

use phpseclib\Crypt\AES;


class Gateway extends Base\Gateway
{
    protected $gateway = Payment\Gateway::NETBANKING_OBC;

    /**
     * @var Crypto
     */
    private $aesCrypto;

    protected $map = [
        Base\Entity::AMOUNT             => Base\Entity::AMOUNT,
        // Auth request mapping
        RequestFields::PAYEE_ID         => Base\Entity::REFERENCE1,

        // Auth response mapping
        ResponseFields::PAID            => Base\Entity::STATUS,
        ResponseFields::BANK_PAYMENT_ID => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::DEBIT_ACC_NUM   => Base\Entity::ACCOUNT_NUMBER,

        // Verify response mapping
        ResponseFields::BANK_PAYMENT_ID => Base\Entity::BANK_PAYMENT_ID,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $attributes = $this->getContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($attributes);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->parseGatewayResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
                               $content[RequestFields::PAY_REF_NUM]);

        $this->assertAmount($input['payment']['amount'] / 100, $content[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->checkActionStatus($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * This method encrypts and then encodes the input string
     * @param string $stringToEncrypt
     * @return string
     */
    public function encrypt(string $stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->encryptString($stringToEncrypt);
    }

    /**
     * This method decodes the string and then decrypts it
     * @param string $stringToDecrypt
     * @return string
     */
    public function decrypt(string $stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString($stringToDecrypt);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $data = $this->getVerifyRequestData($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $data,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->sendGatewayRequest($data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $verify->verifyResponse->body,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $verify->verifyResponseContent = $this->parseVerifyResponse($verify->verifyResponse);
    }

    protected function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyMatchStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);

        $verify->amountMismatch = $this->setVerifyAmountMismatch($verify);
    }

    /**
     * Asserting that payment amount is the same as the amount received in the callback / verify response.
     *
     * @override
     * @param $expectedAmount
     * @param $actualAmount
     */
    protected function assertAmount($expectedAmount, $actualAmount)
    {
        $expectedAmount = $this->formatAmount($expectedAmount);
        $actualAmount = $this->formatAmount($actualAmount);

        parent::assertAmount($expectedAmount, $actualAmount);
    }

    protected function getContentToSave($payment): array
    {
        return [
            Base\Entity::AMOUNT => $payment[Payment::AMOUNT],
            Base\Entity::REFERENCE1 => $this->getMerchantId(),
        ];
    }

    private function setVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        try
        {
            $this->assertAmount($input['payment']['amount'] / 100, $content[ResponseFields::AMOUNT]);
        }
        catch (Exception\LogicException $e)
        {
            return false;
        }

        return true;
    }

    private function parseVerifyResponse(\Requests_Response $response)
    {
        $keyValuePair = explode('|', $response->body);

        $verifyResponseArray = [];

        foreach ($keyValuePair as $fields)
        {
            if (empty(trim($fields)) === true)
            {
                continue;
            }

            $content = explode('=', $fields);

            $key = $content[0];

            $value = $content[1];

            $verifyResponseArray[$key] = $value;
        }

        return $verifyResponseArray;
    }

    private function getVerifyMatchStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            return VerifyResult::STATUS_MISMATCH;
        }

        return VerifyResult::STATUS_MATCH;
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ($content[ResponseFields::TXN_STATUS] === Status::VERIFY_SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    private function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    private function getVerifyAttributesToSave(array $content, Base\Entity $gatewayPayment)
    {
        $attributesToSave = $this->getMappedAttributes($content);

        // If auth status was not success, we update the entity with verify status
        if ($gatewayPayment->getStatus() !== Status::SUCCESS)
        {
            $attributesToSave[Base\Entity::STATUS] = $this->getAuthMappedVerifyStatus($content);
        }

        return $attributesToSave;
    }

    private function getAuthMappedVerifyStatus(array $content)
    {
        $verifyStatus = $content[ResponseFields::TXN_STATUS];

        return ($verifyStatus === Status::VERIFY_SUCCESS) ? Status::SUCCESS : Status::FAILED;
    }

    private function checkActionStatus(array $content)
    {
        if ((empty($content[ResponseFields::PAID]) === false) and
            ($content[ResponseFields::PAID] !== Status::SUCCESS))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    private function getAuthorizeRequest(array $input)
    {
        $content = [
            RequestFields::RETURN_URL   => $this->encrypt($input['callbackUrl']),
            RequestFields::CATEGORY_ID  => Constant::CATEGORY_ID,
            RequestFields::QUERY_STRING => $this->getQueryString($input)
        ];

        return $this->getStandardRequestArray($content);
    }

    private function getVerifyRequestData(Verify $verify)
    {
        $data = [
            RequestFields::PAYEE_ID    => $this->getMerchantId(),
            RequestFields::PAY_REF_NUM => $verify->input['payment']['id'],
            RequestFields::ITEM_CODE   => strtoupper($verify->input['payment']['id']),
            RequestFields::AMOUNT      => $this->formatAmount($verify->input['payment']['amount'] / 100),
            RequestFields::RETURN_URL  => $this->getCallbackUrl($verify->input['payment']['id']),
            RequestFields::BID         => $verify->payment['bank_payment_id']
        ];

        return $this->getStandardRequestArray($data);
    }

    private function formatAmount(float $amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Creates the callback url for payment
     * where the gateway can hit back to say payment
     * is finished/authorized.
     *
     * @param string $paymentId
     * @return string Callback url
     */
    private function getCallbackUrl(string $paymentId): string
    {
        $params = $this->getPaymentIdAndHashParams($paymentId);

        $callbackUrl = $this->route->getUrlWithPublicCallbackAuth($params);

        return $callbackUrl;
    }

    private function getPaymentIdAndHashParams(string $paymentId): array
    {
        $publicId = Payment\Entity::getSignedId($paymentId);

        $hash = $this->getHashOf($publicId);

        return ['id' => $publicId, 'hash' => $hash];
    }

    /**
     * Returns a hash of a string.
     *
     * @param string $string
     * @return string Hash of the string
     */
    private function getHashOf(string $string): string
    {
        $secret = $this->app->config->get('app.key');

        return hash_hmac(HashAlgo::SHA1, $string, $secret);
    }

    /**
     * This method maps the query array into the required query string format
     * For eg. $queryArray = ['key1' => 'value1', 'key2' => 'value2'] becomes key1~value1&key2~value2
     *
     * @param array $input
     * @return string
     */
    private function getQueryString(array $input)
    {
        $queryArray = [
            RequestFields::TRAN_CRN    => Currency::INR,
            RequestFields::TXN_AMOUNT  => $input['payment']['amount'] / 100,
            RequestFields::PAYEE_ID    => $this->getMerchantId(),
            RequestFields::PAY_REF_NUM => $input['payment']['id'],
            RequestFields::ITEM_CODE   => strtoupper($input['payment']['id'])
        ];

        // We will be using this to map to our gateway entity
        $this->gatewayAttribues = $queryArray;

        //
        // The code below does the following:
        // 1. Takes in the array $queryArray in the form [key1 => value1, key2 => value2]
        // 2. Implodes array key-value pairs with ~ as delimiter as [key1 ~ value1, key2 ~ value2]
        // 3. Implodes that using | as delimiter as key1~value1|key2~value2
        //

        $queryStringToEncrypt = implode(
            '|',
            array_map(
                function($key, $value)
                {
                    return Constant::SHOPPING_MALL . $key . '~' . $value;
                },
                array_keys($queryArray),
                array_values($queryArray)
            ));

        return $this->encrypt($queryStringToEncrypt);
    }

    private function createCryptoIfNotCreated()
    {
        if ($this->aesCrypto === null)
        {
            $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $this->getSecret());
        }
    }

    private function parseGatewayResponse(array $response)
    {
        $encryptedString = array_keys($response)[0];

        $decryptedString = $this->decrypt($encryptedString);

        parse_str($decryptedString, $decryptedArray);

        return $decryptedArray;
    }

    public function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}
