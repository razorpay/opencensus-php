<?php

namespace RZP\Gateway\Netbanking\Rbl;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\Action;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_rbl';

    protected $bank = 'rbl';

    protected $map = [
        RequestFields::AMOUNT             => Base\Entity::AMOUNT,
        RequestFields::MERCHANT_REFERENCE => Base\Entity::PAYMENT_ID
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $entityAttributes = $this->getEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttributes);

        $request = $this->getStandardRequestArray($content);

        $request['url'] = $request['url'] . urldecode(http_build_query($content));

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::MERCHANT_REFERENCE]
        );

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::MERCHANT_REFERENCE],
                                    Payment\Action::AUTHORIZE);

        $this->saveCallbackResponse($content, $gatewayPayment);

        $this->checkCallbackStatus($content);

        // If callback status was a success, we verify the payment immediately
        $this->verifyCallback($input, $gatewayPayment);

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

        $gatewayPayment->setStatus(Status::SUCCESS);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     */
    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        // Amount received in verifyCallback is formatted like INR|1,391.80
        $receivedAmount = explode('|', $verify->verifyResponseContent[ResponseFields::AMOUNT]);

        $paymentAmount  = (float) str_replace(',', '', last($receivedAmount));

        $actualAmount   = number_format($paymentAmount, 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify->input, $verify->payment);

        $content = http_build_query($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] =
        [
            'IPTYPE' => FileFormat::NV
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
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

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);

        $expectedAmount = number_format($verify->input['payment']['amount'] / 100, 2, '.', '');

        $receivedAmount = explode('|', $verify->verifyResponseContent[ResponseFields::AMOUNT]);

        $paymentAmount = (float) str_replace(',', '', last($receivedAmount));

        $actualAmount = number_format($paymentAmount, 2, '.', '');

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    protected function getVerifyMatchStatus(Verify $verify): string
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

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        //
        // Verify response will contain ENTRY_STATUS and will have success
        // or failure
        //
        if ($content[ResponseFields::ENTRY_STATUS] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyRequestData(array $input, $gatewayPayment): array
    {
        $data = [
            RequestFields::BANK_ID          => Constants::BANK_ID,
            RequestFields::LANGUAGE_ID      => Constants::LANGUAGE_ID,
            RequestFields::CHANNEL_ID       => Constants::CHANNEL_ID,
            RequestFields::V_LOGIN_FLAG     => Constants::VERIFY_LOGIN_FLAG,
            RequestFields::SERVICE_ID       => Constants::SERVICE_ID,
            RequestFields::STATE_MODE       => Constants::STATE_MODE,
            RequestFields::RESPONSE_FORMAT  => FileFormat::XML,
            RequestFields::REQUEST_FORMAT   => FileFormat::NV,
            RequestFields::MULTIPLE_RECORDS => Constants::NO,
            RequestFields::USER_PRINCIPAL   => $this->getUserPrincipal($input),
            RequestFields::ACCESS_CODE      => $this->getAccessCode($input),
            RequestFields::V_PAYEE_ID       => $this->getMerchantId($input),
            RequestFields::BANK_REFERENCE   => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
            RequestFields::ENTITY_TYPE      => Constants::TYPE_PAYMENT,
            RequestFields::TRANS_CURRENCY   => Currency::INR,
        ];

        return $data;
    }

    protected function getAuthorizeRequestData(array $input): array
    {
        $data =
        [
            RequestFields::FORM_ID          => Constants::AUTHENTICATION,
            RequestFields::TRANSACTION_FLAG => Constants::YES,
            RequestFields::FG_BUTTON        => Constants::LOAD,
            RequestFields::ACTION_LOAD      => Constants::YES,
            RequestFields::BANK_ID          => Constants::BANK_ID,
            RequestFields::LOGIN_FLAG       => Constants::LOGIN_FLAG,
            RequestFields::USER_TYPE        => Constants::USER_TYPE,
            RequestFields::MENU_ID          => Constants::MENU_ID,
            RequestFields::CALL_MODE        => Constants::CALL_MODE,
            RequestFields::CATEGORY_ID      => Constants::CATEGORY,
            RequestFields::RETURN_URL       => $input['callbackUrl'],
        ];

        $dataToEncrypt =
        [
            RequestFields::CURRENCY           => Currency::INR,
            RequestFields::AMOUNT             => $input['payment']['amount'] / 100,
            RequestFields::PAYEE_ID           => $this->getMerchantId($input),
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::MERCHANT_NAME      => Constants::MERCHANT_NAME

        ];

        if ($input['merchant']->isTPVRequired())
        {
            $dataToEncrypt[RequestFields::ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $data[RequestFields::QUERY_STRING] = $this->getHashOfArray($dataToEncrypt);

        return $data;
    }

    /*
     * @param Eg. $data = ['PRN' => "6vTX585l2WP6Bq", 'MD' => "P"]
     * @return Eg. string "PRN~6vTX585l2WP6Bq|MD~P"
     */
    protected function getStringToHash($data, $glue = '|'): string
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . '~' . $value;
        }

        $queryString = implode($glue, $queryArray);

        return $queryString;
    }

    protected function getHashOfString($stringToEncrypt): string
    {
        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->encryptString($stringToEncrypt);
    }

    public function getDecryptedString(string $stringToDecrypt): string
    {
        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->decryptString($stringToDecrypt);
    }

    protected function getEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::AMOUNT             => $input['payment']['amount'] / 100,
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
        ];

        return $entityAttributes;
    }

    protected function saveCallbackResponse(array $content, $gatewayPayment)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REFERENCE],
            Base\Entity::STATUS          => $content[ResponseFields::STATUS],
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, Base\Entity $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::ENTRY_STATUS];
        }

        //
        // Saving BID from Verify response only if BID from authorize hasn't been saved
        //
        if (isset($content[ResponseFields::REFERENCE_ID]) === true)
        {
            if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
            {
                $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::REFERENCE_ID];
            }
            else if ($gatewayPayment[Base\Entity::BANK_PAYMENT_ID] !== $content[ResponseFields::REFERENCE_ID])
            {
                $this->trace->error(
                    TraceCode::GATEWAY_MULTIPLE_BANK_PAYMENT_IDS,
                    [
                        'authorize_bid' => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
                        'verify_bid'    => $content[ResponseFields::REFERENCE_ID]
                    ]
                );
            }
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus(): string
    {
        return Status::SUCCESS;
    }

    protected function parseVerifyResponse(string $body): array
    {
        $xml = (array) simplexml_load_string($body);

        $transactionStatus = (array) $xml[ResponseFields::TRANSACTION_STATUS];

        return (array) $transactionStatus[ResponseFields::STATUS_RECORD];
    }

    protected function getAccessCode(array $input): string
    {
        $accessCode = $input['terminal']['gateway_access_code'];

        if ($this->mode === Mode::TEST)
        {
            $accessCode = $this->config['test_access_code'];
        }

        return $accessCode;
    }

    protected function getUserPrincipal(array $input): string
    {
        $userPricipal = $input['terminal']['gateway_merchant_id2'];

        if ($this->mode === Mode::TEST)
        {
            $userPricipal = $this->config['test_merchant_id2'];
        }

        return $userPricipal;
    }

    protected function getMerchantId(array $input)
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            if ($input['merchant']->isTPVRequired() === true)
            {
                $mid = $this->config['test_merchant_id_tpv'];
            }
            else
            {
                $mid = $this->getTestMerchantId();
            }
        }

        return $mid;
    }
}
