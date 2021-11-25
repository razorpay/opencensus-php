<?php

namespace RZP\Gateway\Upi\Sbi;

use App;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Exception\BaseException;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Encryption\PGPEncryption;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Exception\AssertionException;
use RZP\Exception\GatewayErrorException;
use RZP\Constants\Entity as ConstantsEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use Base\MozartTrait;

    const ACQUIRER = Payment\Processor\Upi::SBIN;

    protected $gateway = Payment\Gateway::UPI_SBI;

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    /**
     * Used to map request / response fields to entity
     * fields before creating or updating the upi entity.
     * @var array
     */
    protected $map = [
        // Mapping entity variables to entity variables
        Base\Entity::GATEWAY_MERCHANT_ID       => Base\Entity::GATEWAY_MERCHANT_ID,
        Base\Entity::VPA                       => Base\Entity::VPA,
        Base\Entity::ACTION                    => Base\Entity::ACTION,
        Base\Entity::EXPIRY_TIME               => Base\Entity::EXPIRY_TIME,

        // Mapping response fields to entity variables
        ResponseFields::PAYER_VPA              => Base\Entity::VPA,
        ResponseFields::CUSTOMER_REFERENCE_NO  => Base\Entity::NPCI_REFERENCE_ID,
        ResponseFields::UPI_TRANS_REFERENCE_NO => Base\Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::STATUS                 => Base\Entity::STATUS_CODE,
        Base\Entity::TYPE                      => Base\Entity::TYPE,
        ResponseFields::NPCI_TRANSACTION_ID    => Base\Entity::NPCI_TXN_ID,
        ResponseFields::ADDITIONAL_INFO        => Base\Entity::GATEWAY_DATA,
        Base\Entity::MERCHANT_REFERENCE        => Base\Entity::MERCHANT_REFERENCE,
    ];

    protected $allowedAdditionalInfo = [
        ResponseFields:: ADDITIONAL_INFO2  => 'string|max:40',
    ];

    protected $sortRequestContent = false;

    public function authorize(array $input)
    {
        parent::authorize($input);

        // Adding remarks for intent and collect request, so the dependency is on
        // payment description
        if (isset($input['upi']) === true)
        {
            $input['upi']['remark'] = $this->getPaymentRemark($input);
        }

        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $response = $this->authorizeRequest($input);

        $this->updateGatewayPaymentEntity($gatewayPayment, $response['data']['gateway_response']);

        $vpa = $input[ConstantsEntity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2] ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                Payment\Entity::VPA => $vpa
            ]
        ];
    }

    protected function authorizeIntent(array $input, bool $persist = true)
    {
        $attributes = [
            Entity::TYPE                => Base\Type::PAY,
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
        ];

        $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $input['merchant']['category'] = $this->getMerchantCategory($input);

        $response = $this->authorizeRequest($input);

        $data = [
            'intent_url' => $response['next']['redirect']['url'],
        ];

        return ['data' => $data];
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string, default value is 5411
     */
    protected function getMerchantCategory(array $input) : string
    {
        return $input['merchant']['category'] ?? '5411';
    }


    /**
     * Handles S2S callback flow
     *
     * @param array $input
     * @return array
     * @throws BaseException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $this->callbackRequest($input);

        $content = $input['gateway']['data']['gateway_response'];

        $this->assertPaymentIdAndAmount($input, $content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                                                                      Action::AUTHORIZE);

        //Skipping this in case of intent as npci ref id is null for this in initial call
        if ($this->assertUpiTransactionId($gatewayPayment, $content) === false and $gatewayPayment->getType() !== Base\Type::PAY)
        {
            throw new AssertionException(
                'Upi Transaction reference number does not match saved npci reference id in DB',
                [
                    Base\Entity::NPCI_REFERENCE_ID         => $gatewayPayment->getNpciReferenceId(),
                    ResponseFields::UPI_TRANS_REFERENCE_NO => $content[ResponseFields::UPI_TRANS_REFERENCE_NO],
                    Base\Entity::PAYMENT_ID                => $input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                ]);
        }

        $entityData = $this->processGatewayData($content);

        $this->updateGatewayPaymentEntity($gatewayPayment, $entityData);

        $this->checkResponseStatus($content[ResponseFields::STATUS]);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    public function verify(array $input)
    {
        unset($input['gateway_config']);

        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function validateVpa(array $input)
    {
        parent::action($input, Action::VALIDATE_VPA);

        $request = [
            'terminal' => $this->terminal,
            'payment' => [
                'id' => random_alpha_string(10),
                'vpa' => $input['vpa'],
            ],
        ];

        $this->trace->info('GATEWAY_VALIDATE_VPA_REQUEST',
            [
                'encrypted'  => false,
                'vpa'        => $request['payment']['vpa'],
                'gateway'    => $this->gateway,
            ]);

        $response = $this->sendMozartRequest($request);

        if ($response['success'] !== true)
        {
            throw new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'GATEWAY_ERROR_REQUEST_ERROR',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                [],
                null,
                $this->action);
        }

        $response = $response['data']['gateway_response'];

        $this->trace->info(TraceCode::GATEWAY_VALIDATE_VPA_RESPONSE,
            [
                'encrypted'  => false,
                'response'   => $response,
                'gateway'    => $this->gateway,
            ]);

        $this->checkResponseStatus($response[ResponseFields::STATUS]);

        return $this->returnValidateVpaResponse($response);
    }

    private function assertPaymentIdAndAmount(array $input, array $response)
    {
        $expectedAmount = $this->formatAmount($input);

        $actualAmount = $response[ResponseFields::AMOUNT];

        $actualAmount = number_format($actualAmount, 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $expectedPaymentId = $input[ConstantsEntity::PAYMENT][Payment\Entity::ID];

        $actualPaymentId = $response[ResponseFields::PSP_REFERENCE_NO];

        $this->assertPaymentId($expectedPaymentId, $actualPaymentId);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $response = $this->verifyRequest($verify);

        $verify->verifyResponseBody = $response;

        unset($response['data']['gateway_response']['addInfo']['statusDesc']);

        $verify->verifyResponseContent = $response['data']['gateway_response'] ?? [];
    }

    protected function verifyPayment(Verify $verify)
    {
        $this->setVerifyAmountMismatch($verify);

        $content = $verify->verifyResponseContent;

        $entityData = $this->processGatewayData($content);

        $this->updateGatewayPaymentEntity($verify->payment, $entityData);

        $this->setVerifyStatus($verify);
    }

    /**
     * @param Verify $verify
     * @return array
     */
    private function getPaymentVerifyRequest(Verify $verify): array
    {
        $input = $verify->input;

        $requestInfo = [
            RequestFields::PG_MERCHANT_ID   => $this->getMerchantId(),
            RequestFields::PSP_REFERENCE_NO => $input[ConstantsEntity::PAYMENT][Payment\Entity::ID],
        ];

        $request = [
            RequestFields::REQUEST_INFO          => $requestInfo,
            RequestFields::CUSTOMER_REFERENCE_NO => $verify->payment->getGatewayPaymentId(),
        ];

        return $this->getStandardRequestArray($request);
    }

    private function setVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input);

        $content = $verify->verifyResponseContent;

        if (empty($content[ResponseFields::AMOUNT]) === false)
        {
            $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }
    }

    private function setVerifyStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = $content[ResponseFields::STATUS];

        $definiteErrorCodes = ['X', 'R'];

        if (in_array($status, $definiteErrorCodes) === true)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                Payment\Verify\Action::FINISH);
        }

        $verify->gatewaySuccess = (Status::isStatusSuccess($status, $this->action) === true);
    }

    /**
     * @param string $status
     * @throws GatewayErrorException
     */
    private function checkResponseStatus(string $status)
    {
        if (Status::isStatusSuccess($status, $this->action) === false)
        {
            $errorCode = Status::getErrorCode($status);

            $errorMessage = Status::getMessage($status);

            throw new GatewayErrorException($errorCode, $status, $errorMessage);
        }
    }

    private function getRequestTraceCode(): string
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST;
                break;

            case Action::VALIDATE_VPA:
                $traceCode = TraceCode::GATEWAY_VALIDATE_VPA_REQUEST;
                break;

            case Action::VERIFY:
                $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY;
                break;

            default:
                $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST;
                break;
        }

        return $traceCode;
    }

    public function getEncryptedPayload(array $content): string
    {
        $json = json_encode($content);

        $hash = $this->getHashOfString($json);

        $pgp = $this->getPgpInstance();

        $encryptedHash = $pgp->encrypt($hash);

        $contentWithHash = $encryptedHash . '|' . $json;

        return base64_encode($pgp->encryptSign($contentWithHash));
    }

    public function getDecryptedPayload(string $encryptedResponse): array
    {
        $pgp = $this->getPgpInstance();

        $decryptedString = $pgp->decryptVerify(base64_decode($encryptedResponse));

        $encHashResponsePair = explode('|', $decryptedString);

        $hash = $pgp->decrypt($encHashResponsePair[0]);

        $this->verifyHash($encHashResponsePair[1], $hash);

        return $this->jsonToArray($encHashResponsePair[1]);
    }

    /**
     * @return Crypto
     */
    public function getPgpInstance(): PGPEncryption
    {
        $pgpConfig = [
        'public_key'  => trim(str_replace('\n', "\n", $this->config['public_key'])),
        'private_key' => trim(str_replace('\n', "\n", $this->config['private_key'])),
        'passphrase'  => $this->config['passphrase']
        ];

        $pgp = new PGPEncryption($pgpConfig);

        return $pgp;
    }

    /**
     * @param string $body
     * @param string $traceCode
     * @return array
     */
    private function parseGatewayResponse(string $body, $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE): array
    {
        $this->trace->info($traceCode,
            [
                'encrypted'  => true,
                'response'   => $body,
                'gateway'    => $this->gateway,
            ]);

        $encryptedResponse = $this->jsonToArray($body)[ResponseFields::RESPONSE];

        $response = $this->getDecryptedPayload($encryptedResponse);

        $traceResponse = $this->maskUpiDataForTracing($response, [
            Base\Entity::VPA    => ResponseFields::PAYEE_TYPE . '.' . ResponseFields::VIRTUAL_ADDRESS,
            Base\Entity::NAME   => ResponseFields::PAYEE_TYPE . '.' . ResponseFields::NAME,
        ]);

        $this->trace->info($traceCode,
            [
                'encrypted'  => false,
                'response'   => $traceResponse,
                'gateway'    => $this->gateway,
            ]);

        return $response;
    }

    private function assertUpiTransactionId(Base\Entity $upiEntity, array $content): bool
    {
        $upiTransactionRefNo = (string) $content[ResponseFields::CUSTOMER_REFERENCE_NO];

        $npciReferenceId = (string) $upiEntity->getNpciReferenceId();

        return ($upiTransactionRefNo === $npciReferenceId);
    }

    /**
     * This method encrypts the request content before converting the request into standard form for Sbi's API's
     *
     * @override
     * @param array $content
     * @param string $method
     * @param null $type
     * @return array
     */
    protected function getStandardRequestArray($content = [], $method = 'post', $type = null): array
    {
        $traceCode = $this->getRequestTraceCode();

        $this->trace->info(
            $traceCode,
            [
                'encrypted'  => false,
                'gateway'    => $this->gateway,
                'content'    => $content
            ]);

        $requestMsg = $this->getEncryptedPayload($content);

        $json = [
            RequestFields::REQUEST_MESSAGE => $requestMsg,
            RequestFields::PG_MERCHANT_ID  => $this->getMerchantId(),
        ];

        $content = json_encode($json);

        $request = parent::getStandardRequestArray($content, $method, $type);

        $token = $this->fetchOauthToken();

        $request['url'] = $request['url'] . '?access_token=' . $token;

        $request['headers']['Content-Type'] = 'application/json';

        $traceReq = $request;

        unset($traceReq['url']);

        $this->trace->info(
            $traceCode,
            [
                'encrypted'  => true,
                'gateway'    => $this->gateway,
                'request'    => $traceReq,
            ]);

        return $request;
    }

    /**
     * Gets the gateway entity attributes
     *
     * @param array $input
     * @return array
     */
    private function getGatewayEntityAttributes(array $input, string $type = Base\Type::COLLECT): array
    {
        $attributes = [
            Base\Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Base\Entity::VPA                 => $input[ConstantsEntity::PAYMENT][Payment\Entity::VPA],
            Base\Entity::ACTION              => $this->action,
            Base\Entity::EXPIRY_TIME         => $input[ConstantsEntity::UPI][Base\Entity::EXPIRY_TIME],
            Base\Entity::TYPE                => $type,
        ];

        return $attributes;
    }

    private function formatAmount(array $input): string
    {
        $amount = $input[ConstantsEntity::PAYMENT][Payment\Entity::AMOUNT] / 100;

        return number_format($amount, '2', '.', '');
    }

    /**
     * @param $input
     * @return array
     */
    public function preProcessServerCallback($input): array
    {
        $response = $this->preProcessServerCallbackRequest($input);

        $response['pgMerchantId'] = json_decode($input['msg'], true)['pgMerchantId'];

        $callback = $response['data']['gateway_response'];

        $callback = [ResponseFields::API_RESPONSE => $callback];

        $traceCallback = $this->maskUpiDataForTracing($callback, [
            Base\Entity::VPA    => ResponseFields::PAYER_VPA,
        ]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'decrypted_data' => $traceCallback,
                'payment_id'     => $callback[ResponseFields::API_RESPONSE][ResponseFields::PSP_REFERENCE_NO]
            ]);

        return $response;
    }

    public function postProcessServerCallback($input): array
    {
        return [
            'pspRefNo' => $input['gateway']['data']['gateway_response']['pspRefNo'],
            'status'   => 'SUCCESS',
            'message'  => 'Request Processed Successfully'
        ];
    }

    /**
     * @param array $response
     * @return mixed
     */
    public function getPaymentIdFromServerCallback(array $response): string
    {
        return $response['data']['paymentId'];
    }

    /**
     * Mode isn't set during the async callback flow, and we would need merchantId
     * based on whether the mode is live or test. However, we are setting the same
     * look up key in the vault file, but the key will be mapped to the live or test
     * merchant_id / hash_secret based on the environment. Since, this is handled by the
     * vault file logic, we are pulling out the merchant_id / hash_secret in the getters below
     */

    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getLiveMerchantId()
    {
        return $this->terminal['gateway_merchant_id'];
    }

    /**
     * This function authorize the payment forcefully when verify api is not supported
     * or not giving correct response.
     *
     * @param $input
     * @return bool
     */
    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'],
                                                                      Action::AUTHORIZE);

        // If it's already authorized on gateway side, there's nothing to do here. We just return back.
        if ($gatewayPayment[Base\Entity::STATUS_CODE] === Status::SUCCESS)
        {
            return true;
        }

        $attributes = [
            Base\Entity::STATUS_CODE        => Status::SUCCESS,
            Base\Entity::NPCI_REFERENCE_ID  => $input['gateway']['reference_number'],
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    protected function returnValidateVpaResponse($response)
    {
        if (isset($response[ResponseFields::PAYEE_TYPE][ResponseFields::NAME]) === true)
        {
            return $response[ResponseFields::PAYEE_TYPE][ResponseFields::NAME];
        }
    }

    protected function fetchOauthToken()
    {
        $content = [
            'grant_type'    => 'password',
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'username'      => 'oauth2-api-merweb-' . $this->config['username'],
            'password'      => $this->config['password'],
        ];

        $request = $traceRequest = parent::getStandardRequestArray($content, 'get', 'oauth_token');

        unset($traceRequest['content']);

        $this->trace->info(TraceCode::FETCH_TOKEN_REQUEST, $traceRequest);

        $response = $this->retryHandler(
            [$this, 'sendGatewayRequest'],
            [$request],
            [$this, 'shouldRetry'],
            [$this, 'getMaxRetryCount']);

        $responseArray = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::FETCH_TOKEN_RESPONSE,
            [
                'action'        => 'fetch_outh_token',
                'token_type'    => $responseArray['token_type'] ?? ' ',
                'expires_in'    => $responseArray['expires_in'] ?? ' ',
            ]);

        return $responseArray['access_token'];
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA256, $string);
    }

    protected function getStringToHash($content, $glue = '')
    {
        return $content;
    }

    public function verifyHash($content, $actual)
    {
        $generated = $this->generateHash($content);

        $this->compareHashes($actual, $generated);
    }

    protected function processGatewayData($content)
    {
        /** We are unsetting additional info here as for some cases, in verify we get empty array in additional info
         * field in verify response. We do not want to update the additional info in gateway entity as we might end
         * up losing data. We need additional info to be there as it is needed in refund file.
         * TODO: Fix this for other fields too.
         */
        if (empty($content[ResponseFields::ADDITIONAL_INFO]) === true)
        {
            unset($content[ResponseFields::ADDITIONAL_INFO]);
        }
        else
        {
            $info = $content[ResponseFields::ADDITIONAL_INFO];

            $content[ResponseFields::ADDITIONAL_INFO] = array_only($info, array_keys($this->allowedAdditionalInfo));
        }

        return $content;
    }

    public function getParsedDataFromUnexpectedCallback($callbackData)
    {
        $payment = [
            'method'   => 'upi',
            'amount'   => (int) ($callbackData['data']['amount'] * 100),
            'currency' => 'INR',
            'vpa'      => $callbackData['data']['gateway_response']['payerVPA'],
            'contact'  => '+919999999999',
            'email'    => 'void@razorpay.com',
        ];

        $terminal = $this->getTerminalDetailsFromCallback($callbackData);

        return [
            'payment'  => $payment,
            'terminal' => $terminal
        ];
    }

    public function getTerminalDetailsFromCallback($callbackData)
    {
        return [
            'gateway_merchant_id' => $callbackData['pgMerchantId'],
        ];
    }

    public function validatePush($input)
    {
        parent::action($input, Action::VALIDATE_PUSH);

        $this->isDuplicateUnexpectedPayment($input);

        $this->isValidUnexpectedPayment($input);
    }

    protected function isDuplicateUnexpectedPayment($callbackData)
    {
        $merchantReference = $callbackData['data']['gateway_response']['pspRefNo'];

        $gatewayPayment = $this->repo->fetchByMerchantReference($merchantReference);

        if ($gatewayPayment !== null)
        {
            throw new Exception\LogicException(
                'Duplicate Gateway payment found',
                null,
                [
                    'callbackData' => $callbackData
                ]
            );
        }
    }

    protected function isValidUnexpectedPayment($callbackData)
    {
        //
        // Verifies if the payload specified in the server callback is valid.
        //
        $input = [
            'payment' => [
                'id'      => $callbackData['data']['gateway_response']['pspRefNo'],
                'gateway' => 'upi_sbi',
                'vpa'     => $callbackData['data']['gateway_response']['vpa'],
                'amount'  => (int) ($callbackData['data']['amount'] * 100),
            ],
            'terminal' => $this->terminal,
        ];

        $this->action = Action::VERIFY;

        $verify = new Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        $paymentAmount = $this->formatAmount($verify->input);

        $content = $verify->verifyResponseContent;

        $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($paymentAmount, $actualAmount);

        $status = $content[ResponseFields::STATUS];

        $this->checkResponseStatus($status);
    }

    public function authorizePushOld($input)
    {
        list($paymentId , $callbackData) = $input;

        $gatewayInput = [
            'payment' => [
                'id'     => $paymentId,
                'vpa'    => $callbackData['data']['gateway_response']['payerVPA'],
                'amount' => $this->getIntegerFormattedAmount($callbackData['data']['amount']),
            ],
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = [
            Entity::TYPE                    => Base\Type::PAY,
            Entity::MERCHANT_REFERENCE      => $callbackData['data']['paymentId'],
            Entity::RECEIVED                => 1,
            Entity::VPA                     => $callbackData['data']['gateway_response']['payerVPA'],
            Entity::GATEWAY_MERCHANT_ID     => $callbackData['pgMerchantId'],
        ];

        $attributes = array_merge($callbackData['data']['gateway_response'], $attributes);

        $entityData = $this->processGatewayData($attributes);

        $gatewayPayment = $this->createGatewayPaymentEntity($entityData);

        $result = $callbackData['data']['gateway_response']['status'];

        $this->checkResponseStatus($result);

        return [
            'acquirer' => [
                Payment\Entity::VPA         => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    public function authorizePush($input)
    {
        // Authorize push now have two implementations, one which calls mozart for
        // pre processing of callback. In this case, the callback data parsed by mozart.
        // Second approach where input is parsed according to new structure
        list($paymentId , $callbackData) = $input;

        // Older structure will have the gateway response
        if (empty($callbackData['data']['gateway_response'] ?? null) === false)
        {
            return $this->authorizePushOld($input);
        }

        $callbackData['payment']['id'] = $paymentId;

        parent::action($callbackData, Action::AUTHORIZE);

        $gatewayPayment = $this->createGatewayPaymentEntity($callbackData['upi'], null, false);

        return [
            'acquirer' => [
                Payment\Entity::VPA         => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }
}
