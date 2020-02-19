<?php

namespace RZP\Gateway\Netbanking\Csb;

use phpseclib\Crypt\AES;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Constants\Mode as RZPMode;
use RZP\Models\Payment\Gateway as PG;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\LogicException;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const PAYEE_ID = 'Razorpay';

    const CALLBACK_URL = 'https://www.api.razorpay.com';

    const CHECKSUM_ATTRIBUTE = ResponseFields::CHECKSUM;

    protected $gateway = PG::NETBANKING_CSB;

    /**
     * This array is modified while getting the authorize request.
     * It is used to create the gateway netbanking entity.
     * @see getAuthorizeRequest
     * @var array
     */
    private $gatewayAttributes = [];

    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::CHNPGCODE     => Base\Entity::MERCHANT_CODE,
        RequestFields::AMOUNT        => Base\Entity::AMOUNT,

        /**
         * Fields from the authorize response
         */
        ResponseFields::TRAN_REF_NUM => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::STATUS       => Base\Entity::STATUS,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity([RequestFields::AMOUNT => $input['payment']['amount']]);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        $content = $this->getGatewayCallbackData($input['gateway'][ResponseFields::QOUT]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'decrypted_response' => $content,
                'payment_id'         => $input['payment']['id']
            ]
        );

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'content' => $content,
            ]);

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::BANK_REF_NUM]);

        $this->assertAmount($input['payment']['amount'] / 100, $content[ResponseFields::AMOUNT]);

        $hashParams = [
            $content[ResponseFields::PAYEE_ID],
            $content[ResponseFields::CHNPGCODE],
            $content[ResponseFields::BANK_REF_NUM],
            $content[ResponseFields::AMOUNT],
            $content[ResponseFields::TRAN_REF_NUM],
            $content[ResponseFields::STATUS],
            ResponseFields::CHECKSUM => $content[ResponseFields::CHECKSUM]
        ];

        $this->verifySecureHash($hashParams);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->checkCallbackSuccess($content, $gatewayPayment);

        //
        // We verify the callback response before doing anything else with the response,
        // this is so that we ensure the response is for the right payment id and amount
        //
        $this->verifyCallback($gatewayPayment, $input);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function getHashOfArray($content)
    {
        $hashString = $this->getStringToHash($content, '|');

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($content, $glue = '')
    {
        $hashSecret = $this->getSecret();

        $hashKeySaltPair = explode('|', $hashSecret);

        return $hashKeySaltPair[0] . '|' . implode($glue, $content) . '|' . $hashKeySaltPair[1];
    }

    protected function getHashOfString($str): string
    {
        return hash(HashAlgo::SHA512, $str);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        // Since they don't have a valid SSL certificate on UAT site.
        //if ($this->mode === RZPMode::TEST)
        //{
        //    $request['options']['verify'] = false;
        //}

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);
    }

    protected function verifyPayment(Verify $verify)
    {
        //
        // we won't be setting amountMismatch here because verify response doesn't contain amount
        //

        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     *
     * @param Base\Entity $gatewayPayment
     * @param array $input
     * @throws GatewayErrorException
     */
    protected function verifyCallback(Base\Entity $gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If the status in callback and verify does not match
        //
        if ($verify->gatewaySuccess !== true)
        {
            throw new GatewayErrorException(
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

    protected function updateGatewayPaymentEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true): Entity
    {
        $attributes = $this->getMappedAttributes($attributes);

        // Since we get the amount in Rs in the callback, we convert to paise before saving
        $attributes[Base\Entity::AMOUNT] = $attributes[Base\Entity::AMOUNT] * 100;

        $attributes[Base\Entity::RECEIVED] = true;

        return parent::updateGatewayPaymentEntity($gatewayPayment, $attributes, false);
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

    protected function throwExceptionIfCallbackFailure(bool $callbackSuccess, array $content)
    {
        // callbackSuccess is set during verify callback
        if ($callbackSuccess === false)
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                [
                    'callback_response' => $content,
                    'payment_id'        => $this->input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    /**
     * This method gets the required verify request as per API contract.
     * @see https://docs.google.com/document/d/153ypkOhWNIetN3kV153gevKz2EIBO4aGj4XjIguLB0Y/edit#
     *
     * @param Verify $verify
     * @return array
     */
    protected function getVerifyRequestData(Verify $verify): array
    {
        $content = [
            RequestFields::CHNPGSYN     => $this->getMerchantId(),
            RequestFields::CHNPGCODE    => $this->getMerchantId2(),
            RequestFields::PAYEE_ID     => $verify->input['merchant']->getFilteredDba(),
            RequestFields::BANK_REF_NUM => $verify->input['payment']['id'],
            RequestFields::AMOUNT       => number_format($verify->input['payment']['amount'] / 100, '2', '.', ''),
            RequestFields::RETURN_URL   => self::CALLBACK_URL,
            RequestFields::TRAN_REF_NUM => '',
            RequestFields::MODE         => Mode::VERIFY_WO_TID
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'data'       => $content,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]
        );

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $encryptedString = $this->performTwoLevelEncryptForVerify($content);

        $content = [RequestFields::POST_DATA => $encryptedString];

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'encrypted'  => true,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]
        );

        return $request;
    }

    protected function parseVerifyResponse(string $responseString): array
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $responseString
            ]);

        $response = simplexml_load_string($responseString);

        //
        // Converting all elements of $response xml into an array
        //
        $responseArray = json_decode(json_encode($response), true);

        $finalDecryptedString = '';

        if (isset($responseArray[ResponseFields::VERIFICATION]) === true)
        {
            $decryptedStringL1 = $this->decrypt($responseArray[ResponseFields::VERIFICATION], $this->getKeyForAction('Bank Payment Key'));

            $finalDecryptedString = $this->decrypt($decryptedStringL1, $this->getKeyForAction('Biller Verification Key'));
        }
        else if (isset($responseArray[ResponseFields::STATUS_UCFIRST]) === true)
        {
            $decryptedStringL1 = $this->decrypt($responseArray[ResponseFields::STATUS_UCFIRST], $this->getKeyForAction('Bank Payment Key'));

            $finalDecryptedString = $this->decrypt($decryptedStringL1, $this->getKeyForAction('Biller Verification Key'));
        }

        $verificationResponse = explode('|', $finalDecryptedString);

        $verifyResponseContent = [
            ResponseFields::BANK_REF_NUM => $verificationResponse[1],
            ResponseFields::AMOUNT       => $verificationResponse[2],
            ResponseFields::TRAN_REF_NUM => $verificationResponse[3],
            Constants::VERIFY_STATUS     => $verificationResponse[0],
            ResponseFields::CHECKSUM     => $verificationResponse[4],
        ];

        $hashParams = $verifyResponseContent;

        $this->verifySecureHash($hashParams);

        return $verifyResponseContent;
    }

    protected function getVerifyStatus(Verify $verify): string
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

    protected function checkCallbackSuccess(array $content, $gatewayPayment)
    {
        if ((empty($content[ResponseFields::STATUS]) === false) and
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->updateGatewayPaymentEntity($gatewayPayment, $content);

            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                [
                    'callback_response' => $content,
                    'payment_id'        => $this->input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        // content will contain status 100 or 101
        if ($content[Constants::VERIFY_STATUS] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $contentToSave = [];

        if ((empty($gatewayPayment[Base\Entity::STATUS]) === true) or
            ($gatewayPayment[Base\Entity::STATUS] !== Status::SUCCESS))
        {
            $contentToSave[ResponseFields::STATUS] = $content[Constants::VERIFY_STATUS];
        }

        return parent::updateGatewayPaymentEntity($gatewayPayment, $contentToSave);
    }

    /**
     * This method gets the required authorize request as per API contract.
     * @see https://docs.google.com/document/d/153ypkOhWNIetN3kV153gevKz2EIBO4aGj4XjIguLB0Y/edit#
     *
     * @param array $input
     * @return array
     */
    protected function getAuthorizeRequest(array $input): array
    {
        $contentToEncrypt = [
            RequestFields::CHNPGSYN     => $this->getMerchantId(),
            RequestFields::CHNPGCODE    => $this->getMerchantId2(),
            RequestFields::PAYEE_ID     => $input['merchant']->getFilteredDba(),
            RequestFields::BANK_REF_NUM => $input['payment']['id'],
            RequestFields::AMOUNT       => number_format($input['payment']['amount'] / 100, 2, '.', ''),
            RequestFields::RETURN_URL   => $input['callbackUrl'],
            RequestFields::MODE         => Mode::PAY,
        ];

        $this->traceGatewayPaymentRequest(
            $contentToEncrypt,
            $input,
            $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
            ['encrypted' => false]);

        $contentToEncrypt[RequestFields::CHECKSUM] = $this->getHashOfArray($contentToEncrypt);

        if ($input['merchant']->isTPVRequired())
        {
            $accNo = $input['order']['account_number'];
            $accNo = substr_replace($accNo, "-", 4, 0);
            $accNo = substr_replace($accNo, "-", 13, 0);
            $contentToEncrypt[RequestFields::ACCOUNT_NUM] = $accNo;
        }

        $encryptedString = $this->performTwoLevelEncryptForAuth($contentToEncrypt);

        $content = [RequestFields::POST_DATA => $encryptedString];

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
            ['encrypted' => true]
        );

        return $request;
    }

    protected function formatAmount(float $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getMerchantId(): string
    {
        $merchantId = $this->config['test_merchant_id'];

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
        }

        return $merchantId;
    }

    /**
     * Sub merchant.
     * @return string
     */
    protected function getMerchantId2(): string
    {
        $merchantId2 = $this->config['test_merchant_id_2'];

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId2 = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
        }

        return $merchantId2;
    }

    public function getTerminalPassword2()
    {
        $password = $this->config['test_terminal_password2'];

        if ($this->mode === RZPMode::LIVE)
        {
            $password = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2];
        }

        return $password;

    }

    public function getSecureSecret2()
    {
        $password = $this->config['test_gateway_secure_secret2'];

        if ($this->mode === RZPMode::LIVE)
        {
            $password = $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET2];
        }

        return $password;
    }

    protected function encrypt(string $str, $key)
    {
        $aes = new AESCrypto(AES::MODE_CBC, substr($key, 0, 16), substr($key, 0, 16));

        return base64_encode($aes->encryptString($str));
    }

    protected function decrypt(string $str, $key)
    {
        $aes = new AESCrypto(AES::MODE_CBC, substr($key, 0, 16), substr($key, 0, 16));

        return $aes->decryptString(base64_decode($str));
    }

    protected function getKeyForAction($action)
    {
        switch ($action) {
            case 'Biller Payment Key':
                $key = $this->getTerminalPassword();
                break;

            case 'Bank Payment Key':
                $key = $this->getTerminalPassword2();
                break;

            case 'Biller Verification Key':
                $key = $this->getSecureSecret2();
                break;

            default:
                throw new LogicException('should not have reached here. Invalid action '. $action);
        }

        return $key;
    }

    protected function performTwoLevelEncryptForAuth($contentToEncrypt)
    {
        $firstLevelEncryptionInput = [
            $contentToEncrypt[RequestFields::BANK_REF_NUM],
            $contentToEncrypt[RequestFields::AMOUNT],
            $contentToEncrypt[RequestFields::RETURN_URL],
            $contentToEncrypt[RequestFields::MODE],
            $contentToEncrypt[RequestFields::CHECKSUM],
        ];

        if (isset($contentToEncrypt[RequestFields::ACCOUNT_NUM]) === true)
        {
            $firstLevelEncryptionInput[] = $contentToEncrypt[RequestFields::ACCOUNT_NUM];
        }

        $encryptedStringL1 = $this->encrypt(implode('|', $firstLevelEncryptionInput) , $this->getKeyForAction('Biller Payment Key'));

        $secondLevelEncryptionInput = [
          $contentToEncrypt[RequestFields::CHNPGSYN],
          $contentToEncrypt[RequestFields::CHNPGCODE],
          $contentToEncrypt[RequestFields::PAYEE_ID],
          urlencode($encryptedStringL1),
        ];

        return urlencode($this->encrypt(implode('|', $secondLevelEncryptionInput), $this->getKeyForAction('Bank Payment Key')));
    }

    protected function performTwoLevelEncryptForVerify($contentToEncrypt)
    {
        $firstLevelEncryptionInput = [
            $contentToEncrypt[RequestFields::BANK_REF_NUM],
            $contentToEncrypt[RequestFields::AMOUNT],
            $contentToEncrypt[RequestFields::RETURN_URL],
            $contentToEncrypt[RequestFields::TRAN_REF_NUM],
            $contentToEncrypt[RequestFields::MODE],
            $contentToEncrypt[RequestFields::CHECKSUM],
        ];

        $encryptedStringL1 = $this->encrypt(implode('|', $firstLevelEncryptionInput) , $this->getKeyForAction('Biller Verification Key'));

        $secondLevelEncryptionInput = [
            $contentToEncrypt[RequestFields::CHNPGSYN],
            $contentToEncrypt[RequestFields::CHNPGCODE],
            $contentToEncrypt[RequestFields::PAYEE_ID],
            urlencode($encryptedStringL1),
        ];

        return $this->encrypt(implode('|', $secondLevelEncryptionInput), $this->getKeyForAction('Bank Payment Key'));
    }

    protected function getGatewayCallbackData($content)
    {
        $decryptedDataL1 = $this->decrypt($content, $this->getKeyForAction('Bank Payment Key'));

        $pairs = explode('&', $decryptedDataL1);

        $decryptedDataL1Array = [];

        foreach($pairs as $pair)
        {
            $newPair = explode('=', $pair);

            $decryptedDataL1Array[$newPair[0]] = $newPair[1];
        }

        $decryptedDataL2 = $this->decrypt($decryptedDataL1Array[ResponseFields::DATA], $this->getKeyForAction('Biller Payment Key'));

        parse_str($decryptedDataL2, $decryptedDataL2Array);

        unset($decryptedDataL1Array['DATA']);

        return array_merge($decryptedDataL2Array, $decryptedDataL1Array);
    }
}
