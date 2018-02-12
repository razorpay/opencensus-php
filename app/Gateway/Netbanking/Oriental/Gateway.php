<?php

namespace RZP\Gateway\Netbanking\Oriental;

use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Exception\GatewayErrorException;

use phpseclib\Crypt\AES;

/**
 * This gateway has been developed as per the API contract from oriental bank of commerce
 * @see https://drive.google.com/drive/u/0/folders/1A5ULegmYTyv3yVgAD33wwi6wQZk50Nmt
 *
 * Class Gateway
 * @package RZP\Gateway\Netbanking\Oriental
 */
class Gateway extends Base\Gateway
{
    protected $gateway = Payment\Gateway::NETBANKING_ORIENTAL;

    /**
     * Variable to store the gateway attributes after mapping
     * @var array
     */
    private $gatewayAttribues = [];

    /**
     * @var Crypto
     */
    private $aesCrypto;

    protected $map = [
        // Auth request mapping
        RequestFields::TXN_AMOUNT       => Base\Entity::AMOUNT,
        RequestFields::ITEM_CODE        => Base\Entity::REFERENCE1,

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

        $this->createGatewayPaymentEntity($this->gatewayAttribues);

        // Resetting this object to null to free up the memory occupied by the contents of this object
        $this->gatewayAttribues = [];

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

    protected final function sendPaymentVerifyRequest(Verify $verify)
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

    protected final function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyMatchStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);

        $verify->amountMismatch = $this->setVerifyAmountMismatch($verify);
    }

    private function setVerifyAmountMismatch(Verify $verify)
    {
        $mismatch = false;

        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        try
        {
            $this->assertAmount($input['payment']['amount'] / 100, $content[ResponseFields::AMOUNT]);
        }
        catch (LogicException $e)
        {
            $mismatch = true;
        }

        return $mismatch;
    }

    /**
     * Asserting that payment amount is the same as the amount received in the callback / verify response.
     *
     * @override
     * @param $expectedAmount
     * @param $actualAmount
     */
    protected final function assertAmount($expectedAmount, $actualAmount)
    {
        $expectedAmount = number_format($expectedAmount, 2, '.', '');
        $actualAmount = number_format($actualAmount, 2, '.', '');

        parent::assertAmount($expectedAmount, $actualAmount);
    }

    private function parseVerifyResponse(\Requests_Response $response)
    {
        // TODO: Check this
        return json_decode($response->body, true);
    }

    private function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
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
            throw new GatewayErrorException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
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
            "|",
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

    private function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}
