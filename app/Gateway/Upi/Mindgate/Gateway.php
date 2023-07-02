<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BharatQr;
use RZP\Models\Terminal;
use RZP\Gateway\Upi\Base;
use RZP\Models\UpiTransfer;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Gateway\Upi\Base\UpiErrorCodes;
use RZP\Models\Payment\Processor\UpiTrait;
use RZP\Models\Feature\Constants as Feature;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant\Repository as MerchantRepository;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use Base\RecurringTrait;

    use Base\MandateTrait;

    use CommonGatewayTrait;

    const ACQUIRER = 'hdfc';

    protected $gateway = Payment\Gateway::UPI_MINDGATE;

    const MAX_RETRY_COUNT = 5;

    protected $response;

    const BANK = 'hdfc';

    const TIMEOUT       = 15;

    const CONNECT_TIMEOUT = 1;

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpaypg@hdfcbank';

    // Transaction Types
    const P2P = 'P2P';
    const P2M = 'P2M';

    const PAY = 'PAY';

    const NA = 'NA';

    const FIELD_LENGTH = [
        Action::AUTHENTICATE  => 17,
        Action::AUTHORIZE     => 17,
        Action::VALIDATE_VPA  => 14,
        Action::REFUND        => 20,
        Action::VERIFY        => 14,
        Action::VALIDATE_PUSH => 14,
        Action::INTENT_TPV    => 19,
    ];

    protected $map = [
        Entity::VPA                       => Entity::VPA,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::TYPE                      => Entity::TYPE,
        ResponseFields::PAYER_VA          => Entity::VPA,
        ResponseFields::PAYER_NAME        => Entity::NAME,
        ResponseFields::RESPCODE          => Entity::STATUS_CODE,
        // This is a 5 digit number that is the reference ID on the HDFC side
        ResponseFields::UPI_TXN_ID        => Entity::GATEWAY_PAYMENT_ID,
        // NPCI provided RRN for the transaction
        ResponseFields::NPCI_UPI_TXN_ID   => Entity::NPCI_REFERENCE_ID,
        ResponseFields::ACCOUNT_NUMBER    => Entity::ACCOUNT_NUMBER,
        ResponseFields::IFSC_CODE         => Entity::IFSC,
        Entity::MERCHANT_REFERENCE        => Entity::MERCHANT_REFERENCE,
        Entity::GATEWAY_DATA              => Entity::GATEWAY_DATA,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input)
    {
        parent::action($input, Action::AUTHENTICATE);

        if ($this->isFirstRecurringPayment($input) === true)
        {
            return $this->authenticate($input);
        }

        if ($this->isMandateCreateRequest($input) === true)
        {
            $data = $this->getGatewayEntityAttributes($input);

            $gatewayPayment = $this->createGatewayPaymentEntity($data, Action::AUTHORIZE);

            $response = $this->mandateCreate($input);

            $this->updateGatewayPaymentResponse($gatewayPayment, $response['upi'], false);

            return $response;
        }

        if (($this->isBharatQrPayment() === true) or
            ($this->isUpiTransferPayment() === true))
        {
            $attributes = $this->getBharatqrGatewayAttributes($input);

            $paymentData = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

            return [
                'acquirer' => [
                    Payment\Entity::REFERENCE16 => $paymentData->getNpciReferenceId(),
                ],
            ];
        }

        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            $attributes = [
                Entity::TYPE                => Base\Type::PAY,
                Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            ];

            $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

            if ($input['merchant']->isTPVRequired() === true)
            {
                $this->initiateIntentTpv($input);
            }

            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $hasUDFFields = $this->hasUDFFields($input);

        $this->trace->info(TraceCode::ADDITIONAL_UDF_FIELDS_UPI_MINDGATE, [
            'UdfFields' => $hasUDFFields
        ]);

        $request =  $this->getAuthorizeRequestArray($input, $hasUDFFields);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->updateGatewayPaymentEntity($gatewayPayment, $response);

        $this->checkResponseStatus($response[ResponseFields::STATUS]);

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    public function hasUDFFields(array $input)
    {
        $merchant = (new MerchantRepository())->find($input['merchant']['id']);

        return $merchant->isFeatureEnabled(Feature::ENABLE_ADDITIONAL_INFO_UPI);

    }

    public function capture(array $input)
    {
        parent::capture($input);

        if ($this->isMandateExecuteRequest($input) === true)
        {
            /**
             * Checking if the upi entity is present.
             */
            $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

            $upi = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Action::CAPTURE);

            if ($upi === null)
            {
                $data = $this->getGatewayEntityAttributes($input, Action::CAPTURE);

                $this->createGatewayPaymentEntity($data, Action::CAPTURE);
            }

            return $this->mandateExecute($input);
        }

        return;
    }

    public function getIntentUrl(array $input)
    {
        return $this->authorizeIntent($input);
    }

    protected function authorizeIntent(array $input)
    {
        $request = $this->getIntentRequest($input);

        if ($this->shouldSignIntentRequest() === true)
        {
            $secure = $this->getSecureInstance();

            $secure->setRequest($request);

            $data = [
                'intent_url'    => $secure->getIntentUrl(),
                'qr_code_url'   => $secure->getQrcodeUrl(),
            ];
        }
        else
        {
            $data = [
                'intent_url'    => $this->generateIntentString($request),
            ];
        }

        return ['data' => $data];
    }

    protected function getIntentRequest($input)
    {
        if(isset($input['usage_type']) === true)
        {
            $usageType = $input['usage_type'];
        }
        else
        {
            $usageType = null;
        }

        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $input['terminal']->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA,
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => $input['payment']['currency'],
            Base\IntentParams::MCC           => $this->getMerchantCategoryCode($input),
            Base\IntentParams::TXN_REF_ID    => $input['payment']['id'],
        ];

        if(($input['merchant']->isFeatureEnabled(Feature::UPIQR_V1_HDFC)) and $usageType === 'multiple_use')
        {
            $content[Base\IntentParams::TXN_REF_ID] = 'STQ'. $input['payment']['id'];
        }

        if (isset($input['upi']['reference_url']) === true)
        {
            $content[Base\IntentParams::URL] = $input['upi']['reference_url'];
        }

        return $content;
    }

    /**
     * We need to validate that the user's VPA is valid before proceeding with the payment
     * @param array $input
     */
    public function validateVpa(array $input)
    {
        parent::action($input, Action::VALIDATE_VPA);

        $request = $this->getValidateVpaRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::VALIDATE_VPA);

        $this->checkResponseStatus($response[ResponseFields::VPA_STATUS], Status::VPA_AVAILABLE);

        return $this->returnValidateVpaResponse($response);
    }

    private function checkResponseStatus(string $status, $successStatus = Status::SUCCESS)
    {
        $successStatus = (array) $successStatus;

        if (in_array($status, $successStatus, true) === false)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));

            if ($this->action === Action::AUTHENTICATE)
            {
                $ex->markSafeRetryTrue();
            }

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::VPA_VALIDATION_GATEWAY_RESPONSE,
                [
                    'status' => $status
                ]
            );

            throw $ex;
        }
    }

    /**
     * @param string $status
     * @param string $successStatus
     * @param array $response
     * @throws Exception\GatewayErrorException
     */
    private function checkRefundResponseStatus(string $status, string $successStatus = Status::REFUND_SUCCESS, array $response = [])
    {
        if ($status !== $successStatus)
        {
            $responseKey = ($this->action === Action::VERIFY) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE : Payment\Gateway::GATEWAY_RESPONSE;

            $errorCode = ErrorCodes\ErrorCodes::getErrorCode($response);

            $errorMessage = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[ResponseFields::RESPCODE],
                $errorMessage,
                [
                    $responseKey                    => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS   => $this->getGatewayData($response)
                ]);
        }
    }

    protected function getExternalMockUrl(string $type)
    {
        return  env('EXTERNAL_MOCK_GO_GATEWAY_DOMAIN') . $this->getRelativeUrl($type);
    }

    protected function getAxisWrapperUrl(string $type, string $urlDomain )
    {
        return  env('EXTERNAL_MOCK_GO_GATEWAY_DOMAIN') . $this->getRelativeUrl($type);
    }
    /**
     * We only store the VPA because the rest of the fields
     * are filled by the callback
     *
     * @param  array $input
     * @param string $action
     *
     * @return array
     */
    protected function getGatewayEntityAttributes(
        array $input,
        string $action = Action::AUTHORIZE,
        string $type = Base\Type::COLLECT)
    {
        $attrs = [
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::VPA                 => $input['payment']['vpa'],
            Entity::ACTION              => $action,
            Entity::TYPE                => $type,
            Entity::GATEWAY_DATA        => $input['upi']['gateway_data'] ?? null,
        ];

        if ($action === Action::REFUND)
        {
            $attrs[Entity::REFUND_ID] = $input['refund']['id'];
        }

        if ($action === Action::AUTHORIZE)
        {
            $attrs[Entity::EXPIRY_TIME] = $input['upi']['expiry_time'];
        }

        return $attrs;
    }

    /**
     * It checks if decryption failed at mozart due to
     * different terminal secret
     *
     * @param  array $response Response from mozart
     * @return bool
     */
    protected function isDecryptionFailure($response)
    {
        $success = $response['success'] ?? '';

        if ($success === true)
        {
            return false;
        }

        $error = $response['error']['internal_error_code'] ?? '';

        if ($error === ErrorCode::BAD_REQUEST_DECRYPTION_FAILED)
        {
            return true;
        }

        return false;
    }

    /**
     * It will create input for pre-process through mozart
     *
     * @param  array
     * @return array
     */
    protected function getInputForMozartPreProcess($input)
    {
        $data = [
            'payload'       => $input,
            'gateway'       => Payment\Gateway::UPI_MINDGATE,
            'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
        ];

        // If it's a vas merchant, this will execute during preprocess fallback
        if (empty($this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET]) === false)
        {
            $data['terminal'] = $this->terminal;
        }

        return $data;
    }

    /**
     * Function to identify if callback is for static qr, where payment id would be qr code
     * Conditons - QR code to be present and merchant with feature flag - UPIQR_V1_HDFC
     * @param $input
     * @param $response
     * @return bool
     */
    protected function isStaticQrForHDFCMindgate($input, $response)
    {
        if (isset($response['payment_id']) === false)
        {
            return false;
        }

        $paymentId = $response['payment_id'];

        if ((strlen($paymentId) > 14) and
            (starts_with($paymentId, 'STQ') === true) and
            ($this->action !== Action::VALIDATE_PUSH) and
            ($this->action !== Action::VERIFY))
       {
            $paymentId = substr($paymentId, 3, 14);
        }

        try
        {
            $this->app['repo']->qr_code->findOrFail($paymentId);
        }
        catch(\Throwable $e)
        {
            return false;
        }

        $terminal = $this->terminal;

        if (isset($terminal) === false)
        {
            if (isset($input['pgMerchantId']) === false)
            {
                return false;
            }

            $terminal = $this->app['repo']->terminal->findByGatewayMerchantId($input['pgMerchantId'], Payment\Gateway::UPI_MINDGATE);
        }

        $merchant = (new MerchantRepository())->find($terminal['merchant_id']);

        if ($merchant->isFeatureEnabled(Feature::UPIQR_V1_HDFC) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Takes in S2S request input array
     * and returns the parsed response as an array
     *
     * @param  array $input Request Input arrau
     * @param bool   $isBharatQr
     *
     * @return array
     */
    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        // TODO: Find a better way of identifying the callback for Mandate.
        if (isset($input['payload']) === true)
        {
            return $this->preProcessMandateCallback($input, Payment\Gateway::UPI_MINDGATE);
        }

        // We are passing gateway driver as second parameter from GatewayController
        // In that case, isBharatQr will be equals to upi_mindgate

        $encryptedResponse = $input[ResponseFields::CALLBACK_RESPONSE_KEY] ?? null;

        $response = $this->parseGatewayResponse($encryptedResponse, Action::CALLBACK);

        //Static QR does not go via UPI V2 rearch route through mozart. All handling happens in api only
        $isStaticQR = $this->isStaticQrForHDFCMindgate($input, $response);

        $useUpiPreProcess = $this->shouldUseUpiPreProcess(Payment\Gateway::UPI_MINDGATE);

        if (($isBharatQr !== true) and
            ($useUpiPreProcess === true) and
            ($isStaticQR === false))
        {
            $data = $this->getInputForMozartPreProcess($input);

            $response = $this->upiPreProcess($data);

            if ($this->isDecryptionFailure($response) === true)
            {
                $e = new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                    null,
                    null
                );

                $e->markSafeRetryTrue();

                throw $e;
            }

            return $response;
        }

        $response[ResponseFields::CALLBACK_RESPONSE_PGMID] = $input[ResponseFields::CALLBACK_RESPONSE_PGMID];

        $bankDetails = $this->parseBankAccountDetails($response[ResponseFields::BANK_REFERENCE]);

        $payeeVaDetails = $this->parsePayeeVaDetails($response[ResponseFields::REFERENCE_7]);

        $response = array_merge($response, $bankDetails);

        $response = array_merge($response, $payeeVaDetails);

        if ($isBharatQr === true)
        {
            $this->checkCallbackResponseStatus($response);

            $response = $this->getQrData($response);
        }

        return $response;
    }

    public function postProcessServerCallback($input): array
    {
        return ['success' => true];
    }

    public function getTerminalDetailsFromCallbackIfApplicable($input)
    {
        return [
            Terminal\Entity::GATEWAY_MERCHANT_ID => $input[ResponseFields::CALLBACK_RESPONSE_PGMID]
        ];
    }

    protected function getQrData(array $input)
    {
        $amount = $this->getIntegerFormattedAmount($input[ResponseFields::AMOUNT]);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $amount,
            BharatQr\GatewayResponseParams::VPA                   => $input[ResponseFields::PAYER_VA],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::UPI,
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[ResponseFields::CALLBACK_RESPONSE_PGMID],
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => substr($input[ResponseFields::PAYMENT_ID], 3),
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[ResponseFields::UPI_TXN_ID],
        ];

        $payerAccountType = $this->getInternalPayerAccountType($input);

        if (isset($payerAccountType) === true) {
            $qrData[BharatQr\GatewayResponseParams::PAYER_ACCOUNT_TYPE] = $payerAccountType;
        }

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    public function getUpiTransferData(array $input)
    {
        $this->checkForUpiTransferPaymentFailure($input);

        $amount = $this->getIntegerFormattedAmount($input[ResponseFields::AMOUNT]);

        $upiTransferData = [
            UpiTransfer\GatewayResponseParams::AMOUNT                => $amount,
            UpiTransfer\GatewayResponseParams::GATEWAY               => $this->gateway,
            UpiTransfer\GatewayResponseParams::PAYER_VPA             => $input[ResponseFields::PAYER_VA],
            UpiTransfer\GatewayResponseParams::PAYEE_VPA             => $input[ResponseFields::PAYEE_VA],
            UpiTransfer\GatewayResponseParams::PAYER_BANK            => $input[ResponseFields::BANK_NAME],
            UpiTransfer\GatewayResponseParams::PAYER_IFSC            => $input[ResponseFields::IFSC_CODE],
            UpiTransfer\GatewayResponseParams::PAYER_ACCOUNT         => $input[ResponseFields::ACCOUNT_NUMBER],
            UpiTransfer\GatewayResponseParams::TRANSACTION_TIME      => $input[ResponseFields::TXN_AUTH_DATE],
            UpiTransfer\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[ResponseFields::CALLBACK_RESPONSE_PGMID],
            UpiTransfer\GatewayResponseParams::NPCI_REFERENCE_ID     => $input[ResponseFields::NPCI_UPI_TXN_ID],
            UpiTransfer\GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[ResponseFields::UPI_TXN_ID],
            UpiTransfer\GatewayResponseParams::TRANSACTION_REFERENCE => $input[ResponseFields::PAYMENT_ID],
        ];

        $payerAccountType = $this->getInternalPayerAccountType($input);

        if (isset($payerAccountType) === true) {
            $upiTransferData[BharatQr\GatewayResponseParams::PAYER_ACCOUNT_TYPE] = $payerAccountType;
        }

        return [
            'callback_data'     => $input,
            'upi_transfer_data' => $upiTransferData
        ];
    }

    protected function checkForUpiTransferPaymentFailure($input)
    {
        $this->checkCallbackResponseStatus($input);
    }

    /**
     * @param $responseBody
     * @param string $type
     * @return array
     * @see https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
     */
    protected function parseGatewayResponse($responseBody, $type = Action::COLLECT)
    {
        $response = null;
        $sanitized = null;

        try
        {
            $response = $this->decrypt($responseBody);
            $sanitized = $this->sanitizeTextForTracing($response);

            $type = strtoupper($type);

            $fields = constant(__NAMESPACE__ . "\ResponseFields::$type");

            $values = explode('|', $response);

            $result = [];

            foreach ($fields as $index => $key)
            {
                $result[$key] = $values[$index];
            }
        }
        catch (Exception\GatewayErrorException $e)
        {
            // Since Gateway Error Exception is only thrown from decrypt function
            // We do not need to check for exception message as of now
            // We also do not need to trace as the response, but we will still check for callback

            // Note: Callback type is only passed from preProcessServerCallback and not from callback function
            // The idea here is that preProcessServerCallback function can be safely retried
            if ($type === Action::CALLBACK)
            {
                $e->markSafeRetryTrue();
            }

            throw $e;
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
                'body'              => $responseBody,
                'sanitized'         => $sanitized,
                'gateway'           => $this->gateway,
                'type'              => $type,
                'error'             => $e->getMessage()
            ]);

            $responseKey = ($this->action === Action::VERIFY) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE : Payment\Gateway::GATEWAY_RESPONSE;

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                $e->getMessage(),
                [
                    $responseKey  => $responseBody,
                ]);
        }

        $traceResult = $this->maskUpiDataForTracing($result, [
            Entity::VPA                         => ResponseFields::PAYER_VA,
            Entity::CONTACT                     => ResponseFields::PHONE_NUMBER,
            Entity::ACCOUNT_NUMBER              => ResponseFields::ACCOUNT_NUMBER,
            ResponseFields::BANK_REFERENCE      => ResponseFields::BANK_REFERENCE,
        ]);

        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'body'              => $responseBody,
            'sanitized'         => $sanitized,
            'parsed'            => $traceResult,
            'gateway'           => $this->gateway,
            'type'              => $type
        ]);

        return $result;
    }
    /**
     * Handles the S2S callback
     * @param  array $input
     * @return boolean
     */
    public function callback(array $input): array
    {
        parent::callback($input);

        if ($input['payment']['recurring'] === true)
        {
            return $this->processRecurringCallback($input);
        }

        if ($this->isMandateProcessedCallback($input) === true)
        {
            if ($input['gateway']['mandateDtls'][0]['mandateType'] === 'CREATE')
            {
                return $this->recurringMandateCreateCallback($input);
            }

            if ($input['gateway']['mandateDtls'][0]['mandateType'] === 'UPDATE')
            {
                return $this->mandateUpdateCallback($input);
            }

            return $this->mandateCreateCallback($input);
        }

        if ((isset($input['gateway']['data']['version']) === true) and
            ($input['gateway']['data']['version']) === 'v2')
        {
            $acquirerData = $this->upiCallback($input);

            // Some merchants onboarded on mindgate wants this field
            $acquirerData['acquirer'][Payment\Entity::REFERENCE1] = $input['gateway']['data']['upi'][Entity::GATEWAY_PAYMENT_ID];

            return $acquirerData;
        }

        $content = $input['gateway'];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        if ($gatewayPayment->getType() !== Base\Type::PAY)
        {
            assertTrue($content[ResponseFields::UPI_TXN_ID] === $gatewayPayment->getGatewayPaymentId());
        }

        $traceContent = $this->maskUpiDataForTracing($content, [
            Entity::VPA             => ResponseFields::PAYER_VA,
            Entity::CONTACT         => ResponseFields::PHONE_NUMBER,
            Entity::ACCOUNT_NUMBER  => ResponseFields::ACCOUNT_NUMBER,
        ]);

        $this->trace->info(TraceCode::GATEWAY_RESPONSE, [
            'parsed'            => $traceContent,
            'type'              => $gatewayPayment->getType()
        ]);

        assertTrue($input['payment']['id'] === $content[ResponseFields::PAYMENT_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        $this->checkCallbackResponseStatus($content);

        // Gateways must return array in callback
        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
                Payment\Entity::REFERENCE1  => $gatewayPayment->getGatewayPaymentId(),
            ]
        ];
    }

    /**
     * @param $response
     * @param string $successStatus
     * @throws Exception\GatewayErrorException
     */
    private function checkCallbackResponseStatus($response, string $successStatus = Status::SUCCESS)
    {
        if ($response[ResponseFields::STATUS] !== $successStatus)
        {
            $errorCode = ErrorCodes\ErrorCodes::getErrorCode($response, Action::CALLBACK);

            $errorMessage = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[ResponseFields::STATUS],
                $errorMessage);
        }
    }

    protected function parseBankAccountDetails($bankReference)
    {
        $fields = constant(__NAMESPACE__ . '\ResponseFields::BANK_DETAILS');

        $values = explode(ResponseFields::BANK_REFERENCE_SEPARATOR, $bankReference);

        $bankReferenceArray = [];

        $index = 0;

        if (empty($values) === false)
        {
            foreach ($fields as $key)
            {
                if ($values[$index] !== ResponseFields::NO_BANK_DETAIL)
                {
                    $bankReferenceArray[$key] = $values[$index];
                }

                $index++;
            }

        }

        return $bankReferenceArray;
    }

    protected function parsePayeeVaDetails($payeeVaReference)
    {
        $fields = constant(__NAMESPACE__ . '\ResponseFields::PAYEE_VA_DETAILS');

        $values = explode(ResponseFields::BANK_REFERENCE_SEPARATOR, $payeeVaReference);

        $payeeVaReferenceArray = [];

        $index = 0;

        if (empty($values) === false)
        {
            foreach ($fields as $key)
            {
                $payeeVaReferenceArray[$key] = $values[$index];

                $index++;
            }
        }

        return $payeeVaReferenceArray;
    }

    protected function updateGatewayPaymentResponse($payment, array $response, $shouldMap = true)
    {
        $attributes = $response;

        if ($shouldMap === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        // To mark that we have received a callback for this payment/refund
        $attributes[Entity::RECEIVED] = 1;

        $payment->fill($attributes);

        $payment->generatePspData($attributes);

        $this->repo->saveOrFail($payment);
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
     */
    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->terminal->getGatewayMerchantId();
        }

        return $this->config['test_merchant_id'];
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        return $this->config['gateway_encryption_key'];
    }

    /**
     * Encrypts data
     *
     * @param $plaintext
     *
     * @return string
     */
    public function encrypt($plaintext)
    {
        return $this->getCipherInstance()
                    ->encrypt($plaintext);
    }

    /**
     * Returns a Crypto instance
     * @return Crypto class instance
     * @return Crypto
     */
    protected function getCipherInstance()
    {
        $key = $this->getEncryptionKey();

        if (empty($this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET]) === false)
        {
            $key = $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET];
        }

        return new Crypto($key);
    }

    /**
     * Decrypts responses from the Mindgate API
     *
     * @param string $cipherText
     *
     * @return string
     */
    public function decrypt(string $cipherText)
    {
        if($this->checkForValidInput($cipherText) === false)
        {
            throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_INVALID_DATA,
            null,
            'Invalid input for decryption',
            [
                'cipherText' => $cipherText,
            ]);

        }

        $response = $this->getCipherInstance()->decrypt($cipherText);

        // In fact the library returns boolean false when decryption fails.
        // But we are still taking empty string in context too.
        //  1. For certain reasons decryption fails, we might still receive empty string
        // Note: Check for ctype_print is for the case when we on decrypting callback
        // we are receiving binary response(not expected). Thereby , throwing the error
        // fallback to use the secrets from terminal (for VAS merchants)
        if ((empty($response) === true) or (ctype_print($response) === false))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                null,
                null,
                [
                    'cipherText' => $cipherText,
                    'decrypted'  => (ctype_print($response) === false) ? bin2hex($response) : $response,
                ]);
        }

        return $response;
    }

    protected function getAuthorizeRequestArray($input, $hasUDFFields = false)
    {
        $payment = $input['payment'];

        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $payment['id'],
            $payment['vpa'],
            $this->formatAmount($payment['amount']),
            $this->getPaymentRemark($input),
            $input['upi']['expiry_time'],
            $this->getMerchantCategoryCode($input),
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA'
        ];

        if($hasUDFFields)
        {
            $values = $payment['notes'];

            if (empty($values) === false and empty($values['Application Id']) === false)
            {
                $data[7] = $this->sanitizeInput($values['Application Id']);
            }
        }

        $traceData = $this->maskUpiDataForTracing($data, [
            Entity::VPA => 2
        ]);

        if ($input['merchant']->isTPVRequired() === true)
        {
            // MEBR is the request type for TPV
            $data[12] = 'MEBR';
            $data[13] = $input['order']['account_number'];

            $traceData = $this->maskUpiDataForTracing($data, [
                Entity::VPA             => 2,
                Entity::ACCOUNT_NUMBER  => 13
            ]);
        }

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'type'              => 'collect',
                'decrypted_content' => $traceData,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $payment['id'],
            ]);

        return $request;
    }

    public function sanitizeInput($input)
    {
        $pattern = '/[^A-Za-z0-9@_\-=.\/]/';

        $sanitized_input = preg_replace($pattern, '', $input);

        return substr($sanitized_input, 0, 60);
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string.
     *                  Default value is 6012, as per HDFC
     *                  (Check pgtech group)
     */
    protected function getMerchantCategoryCode(array $input)
    {
        return $input['merchant']['category'] ?? '6012';
    }

    /**
     * Returns a refund description, capped to 50 chars
     * @param  array  $input
     * @return string
     */
    protected function getRefundRemark(array $input): string
    {
        $description = $input['merchant']->getFilteredDba();

        // Using ?: works with empty strings as well
        // (because '' == false) === true
        $description = $description ?: 'Razorpay';

        return 'Refund for ' . substr($description, 0, 36);
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data)
    {
        $extraFields = self::FIELD_LENGTH[$this->action] - count($data);

        // We have space for 10 extra fields that we don't use
        $suffixArray = array_fill(0, $extraFields, 'NA');

        $data = array_merge($data, $suffixArray);

        // Drop any `|` in any of the field values
        $data = array_map(function($e)
        {
            return str_replace('|', '', $e);
        }, $data);

        $data = implode('|', $data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'data'              => $data,
                'gateway'           => $this->gateway,
                'action'            => $this->action
            ]);

        $msg = $this->encrypt($data);

        $json = [
            'requestMsg'    => $msg,
            'pgMerchantId'  => $this->getMerchantId(),
        ];

        return json_encode($json);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input, Action::REFUND);

        $refund = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::REFUND);

        $response[Entity::RECEIVED] = 1;

        $this->updateGatewayPaymentEntity($refund, $response);

        $this->checkRefundResponseStatus($response[ResponseFields::STATUS], Status::REFUND_SUCCESS, $response);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
        ];
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                ResponseFields::UPI_TXN_ID          => $response[ResponseFields::UPI_TXN_ID] ?? null,
                ResponseFields::NPCI_UPI_TXN_ID     => $response[ResponseFields::NPCI_UPI_TXN_ID] ?? null,
                ResponseFields::PAYER_VA            => $response[ResponseFields::PAYER_VA] ?? null,
                ResponseFields::TXN_AUTH_DATE       => $response[ResponseFields::TXN_AUTH_DATE] ?? null,
                ResponseFields::RESPCODE            => $response[ResponseFields::RESPCODE] ?? null,
            ];
        }

        return [];
    }

    // Verify payment from barricade
    public function verifyGateway(array $input)
    {
        parent::verify($input);

        if (($this->isFirstRecurringPayment($input) === true) or
            ($this->isSecondRecurringPayment($input) === true))
        {
            return $this->recurringPaymentVerifyGateway($input);
        }

        $verify = new Verify($this->gateway, $input);

        $verify = $this->sendPaymentVerifyRequestGateway($verify);

        return $verify->getDataToTrace();
    }

    protected function sendPaymentVerifyRequestGateway($verify)
    {
        $input = $verify->input;

        // update input using the merchant_reference in the gateway payment
        $input['gateway']['merchant_reference'] = $verify->payment['merchant_reference'];

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $bankDetails = $this->parseBankAccountDetails($content[ResponseFields::BANK_REFERENCE]);

        $content = array_merge($content, $bankDetails);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $verify;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        if (($this->isFirstRecurringPayment($input) === true) or
            ($this->isSecondRecurringPayment($input) === true))
        {
            return $this->recurringPaymentVerify($input);
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        // update input using the merchant_reference in the gateway payment
        $input['gateway']['merchant_reference'] = $verify->payment['merchant_reference'];

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $bankDetails = $this->parseBankAccountDetails($content[ResponseFields::BANK_REFERENCE]);

        $content = array_merge($content, $bankDetails);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function sendRefundVerifyRequest(array $input)
    {
        $request = $this->getRefundVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'raw_content' => $response->body,
                'content'     => $content,
                'gateway'     => 'upi_mindgate',
                'refund_id'   => $input['refund']['id'],
            ]);

        return $content;
    }

    protected function getValidateVpaRequestArray(array $input): array
    {
        $data = [
            $this->getMerchantId(),
            random_alpha_string(10),
            $input['vpa'],
            'T'
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $traceData = $this->maskUpiDataForTracing($data, [
            // Vpa is at third position
            Entity::VPA => 2,
        ]);

        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            [
                'decrypted_content' => $traceData,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'action'            => Action::VALIDATE_VPA,
            ]);

        return $request;
    }

    protected function getRefundRequestArray(array $input): array
    {
        $payment = $input['payment'];

        if (($payment['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE) ||
            ($payment['cps_route'] === Payment\Entity::REARCH_UPI_PAYMENT_SERVICE))
        {
            $fiscalEntity = $this->app['upi.payments']->findByPaymentIdAndGatewayOrFail(
                $payment['id'],
                $payment['gateway'],
                [
                    'customer_reference',
                    'merchant_reference',
                    'gateway_reference'
                ]);

            $bankRrn            = $fiscalEntity['customer_reference'];
            $merchantReference  = $fiscalEntity['merchant_reference'] ?: $payment['id'];
            $gatewayPaymentId   = $fiscalEntity['gateway_reference'];

        } else {
            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'],
                Action::AUTHORIZE
            );

            $bankRrn            = $gatewayPayment->getNpciReferenceId();
            $merchantReference  = $gatewayPayment[Entity::MERCHANT_REFERENCE] ?: $gatewayPayment[Entity::PAYMENT_ID];
            $gatewayPaymentId   = $gatewayPayment->getGatewayPaymentId();
        }

        $refund = $input['refund'];
        // The order is defined in the docs
        // See README.md

        $data = [
            $this->getMerchantId(),
            $this->getRefundId($refund),
            $merchantReference,
            $gatewayPaymentId,
            $bankRrn,
            $this->getRefundRemark($input),
            $this->formatAmount($input['refund']['amount']),
            $input['refund']['currency'],
            // Transaction Type
            // Refunds are P2P!
            self::P2P,
            // Type of Payment (Pay or Collect)
            // Refunds are considered "Pay" transactions
            self::PAY,
        ];

        $merchant = (new MerchantRepository())->find($input['merchant']['id']);

        if($merchant->isFeatureEnabled(Feature::UPIQR_V1_HDFC) === true)
        {
            for ($i = 1 ; $i <=8 ;$i++)
            {
                $data[] = '';
            }
        }

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'refund_id'         => $input['refund']['id'],
                'cps_route'         => $input['payment']['cps_route'],
            ]);

        return $request;
    }

    /**
     * This is done in order to fix duplicate merchant transaction id issue in case refund is retried multiple times.
     *
     * UPI gateways do not process refund which has been failed, they process new refund everytime. And hence,
     * we send the refund id appended with attempts to generate new refund id.
     *
     * @param array $refund
     * @return string
     */
    protected function getRefundId(array $refund)
    {
        return $refund['id'] . ($refund['attempts'] ?: '');
    }


    protected function getPaymentVerifyRequestArray($input)
    {
        $reference = $input['gateway']['merchant_reference'] ?: $input['payment']['id'];

        $data = [
            $this->getMerchantId(),
            $reference,
            '',
            // This is the Reference ID field
            // which is supposed to be empty for now
            // Non-empty values give error
            '',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers']['Content-Type'] = 'text/plain';

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
            ]);

        return $request;
    }

    protected function getRefundVerifyRequestArray($input)
    {
        //
        // Appending (attempt count - 1)  to refund id for verifying previous refund if that was successful.
        // For scrooge refunds, attempts are sent from scrooge which signifies the attempts which have been done on this.
        // As attempts in scrooge starts with 0, For eg. if attempts = 5,
        // that means we will be requesting refund R5 and we need to verify for R4.
        //
        $attempts = $input['refund']['attempts'] - 1;

        //
        // If this is 0th or 1st attempt, verify refund should be called for first refund (exact Refund Id)
        // Appending empty string to refund if we want to verify refund with 14 digit refund id.
        //
        if (((int) $attempts === 0) or ((int) $input['refund']['attempts'] === 0))
        {
            $attempts = '';
        }

        $data = [
            $this->getMerchantId(),
            $input['refund']['id'] . $attempts,
            //As confirmed by hdfc team gateway payment id is not needed
            '',
            // This is the Reference ID field
            // which is supposed to be empty for now
            // Non-empty values give error
            '',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'text/plain'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
                'cps_route'  => $input['payment']['cps_route']
            ]);

        return $request;
    }

    protected function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $input = $verify->input;

        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

            $actualAmount = number_format($content[ResponseFields::AMOUNT], 2, '.', '');

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $this->updateGatewayPaymentEntity($verify->payment, $content);
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new GatewayBase\ScroogeResponse();

        $content = $this->sendRefundVerifyRequest($input);

        $errorCode = ErrorCodes\ErrorCodes::getInternalErrorCode($content[ResponseFields::RESPCODE]);

        $scroogeResponse->setStatusCode($errorCode)
                        ->setGatewayVerifyResponse($content)
                        ->setGatewayKeys($this->getGatewayData($content));

        if ($content[ResponseFields::STATUS] === Status::REFUND_SUCCESS)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        if (($content[ResponseFields::RESPCODE] === '00') and
            ($content[ResponseFields::STATUS] !== Status::REFUND_SUCCESS))
        {
            $this->checkRefundResponseStatus($content[ResponseFields::STATUS], Status::REFUND_SUCCESS, $content);
        }

        // 'MPIN Captured and Pay Request Initiated' in 'status_description' is a pending state, should be verified again
        if ((in_array($content[ResponseFields::STATUS], [Status::REFUND_FAILED, Status::PENDING], true) === true) and
            ($content[ResponseFields::STATUS_DESCRIPTION] === StatusDescription::MPIN_CAPTURED_AND_PAY_REQUEST_INITIATED))
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_INVALID_STATUS_DESCRIPTION)
                                   ->toArray();
        }

        if (($content[ResponseFields::STATUS] === Status::FAILURE) or
            ($content[ResponseFields::STATUS] === Status::REFUND_FAILED))
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED)
                                   ->toArray();
        }

        if ((isset($content[ResponseFields::RESPCODE]) === true) and
            ($content[ResponseFields::RESPCODE] === 'U48'))
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING)
                                   ->toArray();
        }

        $this->checkRefundResponseStatus($content[ResponseFields::STATUS], Status::REFUND_SUCCESS, $content);
    }

    private function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = ($content[ResponseFields::STATUS] === Status::SUCCESS);
    }

    /**
     * Returns Payment Id
     * @param  string $body Request Body
     * @return string Payment Id
     */
    public function getPaymentIdFromServerCallback(array $response): string
    {
        $version = $response['data']['version'] ?? '';

        if ($version === 'v2')
        {
            return $this->upiPaymentIdFromServerCallback($response);
        }

        $details = $this->getRecurringDetailsFromServerCallback($response);

        if (empty($details[Entity::PAYMENT_ID]) === false)
        {
            return $details[Entity::PAYMENT_ID];
        }

        return $this->getActualPaymentIdFromServerCallback($response);
    }

    protected function getActualPaymentIdFromServerCallback(array $response)
    {
        if (isset($response['requestInfo']['pspRefNo']) === true)
        {
            return $response['requestInfo']['pspRefNo'];
        }

        $paymentId = $response[ResponseFields::PAYMENT_ID];

        if(strlen($paymentId)>14 and starts_with($paymentId,'STQ') === true and
            ($this->action) !== Action::VALIDATE_PUSH and $this->action !== Action::VERIFY) {
            return substr($paymentId, 3, 14);
        }

        return $response[ResponseFields::PAYMENT_ID];
    }

    protected function isDuplicateUnexpectedPayment($callbackData)
    {
        $merchantReference = $callbackData[ResponseFields::PAYMENT_ID];

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
    /** Checks if duplicate unexpected payment for the recon through ART
     * @param $input
     * @throws Exception\LogicException
     */
    protected function isDuplicateUnexpectedPaymentV2($input)
    {
        $upiEntity = $this->upiGetRepository()->fetchByNpciReferenceIdAndGateway($input['upi']['npci_reference_id'], $this->gateway);

        if (empty($upiEntity) === false)
        {
            if ($upiEntity->getAmount() === (int) ($input['payment']['amount']))
            {
                throw new Exception\LogicException(
                    'Duplicate Unexpected payment with same amount',
                    null,
                    [
                        'callbackData' => $input
                    ]
                );
            }
        }
    }
    public function isMandateUpdateCallback($input)
    {
        if ((isset($input['mandateDtls']) === true) and ($input['mandateDtls'][0]['mandateType'] === 'UPDATE'))
        {
            return true;
        }

        return false;
    }

    protected function isValidUnexpectedPayment($callbackData)
    {
        /*
            Verifies if the payload specified in the server callback is valid.
        */

        $paymentId = $this->getPaymentIdFromServerCallback($callbackData);

        $input = [
            'gateway' => [
                'merchant_reference' => $paymentId,
            ]
        ];

        $this->action = Action::VERIFY;

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->action = Action::VALIDATE_PUSH;

        $content = $this->parseGatewayResponse($response->body, Action::VERIFY);

        $this->checkResponseStatus($content[ResponseFields::STATUS], [Status::SUCCESS]);
    }

    public function getParsedDataFromUnexpectedCallback($callbackData)
    {
        if ((isset($callbackData['data']['version']) === true)
            and ($callbackData['data']['version']) === 'v2')
        {
            return $this->upiGetParsedDataFromUnexpectedCallback($callbackData);
        }

        $payment = [
            'method'   => 'upi',
            'amount'   => $this->getIntegerFormattedAmount($callbackData[ResponseFields::AMOUNT]),
            'currency' => 'INR',
            'vpa'      => $callbackData[ResponseFields::PAYER_VA],
            'contact'  => '+919999999999',
            'email'    => 'void@razorpay.com',
        ];

        $payerAccountType = $this->getInternalPayerAccountType($callbackData);

        if (isset($payerAccountType) === true) {
            $payment[BharatQr\GatewayResponseParams::PAYER_ACCOUNT_TYPE] = $payerAccountType;
        }

        $terminal = $this->getTerminalDetailsFromCallback($callbackData);

        return [
            'payment'  => $payment,
            'terminal' => $terminal
        ];
    }

    public function getTerminalDetailsFromCallback($callbackData)
    {
        return [
            'gateway_merchant_id' => $callbackData[ResponseFields::CALLBACK_RESPONSE_PGMID],
        ];
    }

    public function validatePush($input)
    {
        parent::action($input, Action::VALIDATE_PUSH);
        // It checks if the version is V2,which is request from art
        if ((empty($input['meta']['version']) === false) and
            ($input['meta']['version'] === 'api_v2'))
        {
             $this->isDuplicateUnexpectedPaymentV2($input);
             $this->upiIsValidUnexpectedPaymentV2($input);
             return;
        }
        // It checks if pre process happened through common gateway trait contracts
        if ((isset($input['data']['version']) === true) and
            ($input['data']['version'] === 'v2'))
        {
            $this->upiIsDuplicateUnexpectedPayment($input);

            $this->isValidUnexpectedPayment($input);

            return ;
        }

        $this->isDuplicateUnexpectedPayment($input);

        $this->isValidUnexpectedPayment($input);
    }

    /**
     * @param $status
     * @return void
     * @throws Exception\GatewayErrorException
     * Checks the status of unexpected payments
     */
    protected function checkUnexpectedPaymentResponseStatus($status)
    {
        if ($status !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            );
        }
    }

    protected function getRedactedData($data)
    {
        unset($data['data']['Key']);

        unset($data['data']['enqinfo']['0']['Key']);

        unset($data['data']['enqinfo']['0']['MOBILENO']);

        unset($data['data']['MobileNo']);

        unset($data['data']['valkey']);

        unset($data['otp']);

        unset($data['data']['_raw']);

        unset($data['_raw']);

        unset($data['data']['account_number']);

        return $data;
    }

    /**
     * Check if its a valid Unexpected Payment
     * @param array $callbackData
     * @throws Exception\LogicException
     * @throws GatewayErrorException
     */
    protected function upiIsValidUnexpectedPaymentV2($callbackData)
    {
        //
        // Verifies if the payload specified in the server callback is valid.
        //
        $input = [
            'payment'       => [
                'id'             => $callbackData['upi']['merchant_reference'],
                'gateway'        => $callbackData['terminal']['gateway'],
                'vpa'            => $callbackData['upi']['vpa'],
                'amount'         => (int) ($callbackData['payment']['amount']),
            ],
            'terminal'      => $this->terminal,
            'gateway'       => [
                'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
            ]
        ];
        $this->action = Action::VERIFY;

        $verify = new Verify($input['payment']['gateway'], $input);

        $this->sendPaymentVerifyRequestv2($verify);

        $paymentAmount = $verify->input['payment']['amount'];

        $content = $verify->verifyResponseContent;

        $actualAmount = $content['data']['payment']['amount_authorized'];

        $this->assertAmount($paymentAmount, $actualAmount);

        $status = $content['success'];

        $this->checkUnexpectedPaymentResponseStatus($status);
    }

    /**
     * @param $verify
     * @return array
     * @throws Exception\GatewayErrorException
     * This is used for verifying the unexpected payments.
     * In normal payments verify we have upi entity but for unexpected payments we don't have, because of which used this.
     */
    public function sendPaymentVerifyRequestv2($verify)
    {
        $result               = $this->upiSendGatewayRequest(
            $verify->input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            'verify'
        );
        $traceRes = $this->getRedactedData($result);

        $this->traceGatewayPaymentResponse($traceRes, $result, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $result;

        return $verify->verifyResponseContent;
    }

    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;
        // It checks if the version is V2,which is request from art
        if ((empty($callbackData['meta']['version']) === false) and
            ($callbackData['meta']['version'] === 'api_v2'))
        {
            return $this->authorizePushV2($input);
        }
        // To handle if callback data was preprocessed through mozart config with v2 contracts
        if ((isset($callbackData['data']['version']) === true) and
            ($callbackData['data']['version'] === 'v2'))
        {
            return $this->upiAuthorizePush($input);
        }

        $gatewayInput = [
            'payment' => [
                'id'     => $paymentId,
                'vpa'    => $callbackData[ResponseFields::PAYER_VA],
                'amount' => $this->getIntegerFormattedAmount($callbackData[ResponseFields::AMOUNT]),
            ],
            'upi'     => [
                'expiry_time' => 1, // dummy value
            ]
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = $this->getGatewayEntityAttributes($gatewayInput, Action::AUTHORIZE, Base\Type::PAY);

        $callbackData[Entity::RECEIVED] = 1;

        // Update merchant reference and payment_id
        $merchantReference = $callbackData[ResponseFields::PAYMENT_ID];

        $callbackData[ResponseFields::PAYMENT_ID] = $paymentId;

        $callbackData[Entity::MERCHANT_REFERENCE] = $merchantReference;

        $attributes = array_merge($attributes, $callbackData);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $this->checkCallbackResponseStatus($callbackData);

        return [
            'acquirer' => [
                Payment\Entity::VPA         => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }
    /**
     * AuthorizePushV2 is triggered for reconciliation happening via ART
     * @param array $input
     * @return array[]
     * @throws Exception\LogicException
     */
    protected function authorizePushV2($input)
    {
        list ($paymentId, $content) = $input;

        // Create attributes for upi entity.
        $attributes = [
            Entity::TYPE                => Base\Type::PAY,
            Entity::RECEIVED            => 1,
        ];

        $attributes = array_merge($attributes, $content['upi']);

        $payment  = $content['payment'];

        $upi      = $content['upi'];

        $gateway = $this->gateway;

        // Create input structure for upi entity.
        $input = [
            'payment'    => [
                'id'       => $paymentId,
                'gateway'  => $gateway,
                'vpa'      => $upi['vpa'],
                'amount'   => $payment['amount'],
            ],
        ];

        // Call to set the input in gateway
        parent::action($input, Action::AUTHORIZE);

        $gatewayPayment = $this->upiCreateGatewayEntity($input, $attributes);

        return [
            'acquirer' => [
                PaymentEntity::VPA           => $gatewayPayment->getVpa(),
                PaymentEntity::REFERENCE16   => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
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
        if ((($gatewayPayment[Entity::STATUS_CODE] === Status::SUCCESS) or
            ($gatewayPayment[Entity::STATUS_CODE] === '00')) and
            ($gatewayPayment[Entity::RECEIVED] === true))
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


    protected function getBharatQrGatewayAttributes($input)
    {
        $attrs = [
            Entity::TYPE                    => Base\Type::PAY,
            Entity::RECEIVED                => true,
            Entity::MERCHANT_REFERENCE      => $input['payment']['receiver_id'],
            Entity::VPA                     => $input[ResponseFields::PAYER_VA],
            ResponseFields::UPI_TXN_ID      => $input[ResponseFields::UPI_TXN_ID],
            ResponseFields::NPCI_UPI_TXN_ID => $input[ResponseFields::NPCI_UPI_TXN_ID],
            ResponseFields::ACCOUNT_NUMBER  => $input[ResponseFields::ACCOUNT_NUMBER],
            ResponseFields::IFSC_CODE       => $input[ResponseFields::IFSC_CODE],
            ResponseFields::RESPCODE        => $input[ResponseFields::RESPCODE],
        ];

        return $attrs;
    }

    protected function returnValidateVpaResponse($response)
    {
        if (isset($response[ResponseFields::PAYER_NAME]) === true)
        {
            return $response[ResponseFields::PAYER_NAME];
        }
    }

    protected function initiateIntentTpv($input)
    {
        $this->action = Action::INTENT_TPV;

        $data = [
            $this->getMerchantId(),
            $input['payment']['id'],
            $this->getMerchantCategoryCode($input),
            self::P2M,
            self::PAY,
            $this->getPaymentRemark($input),
            '',
            '',
            $this->formatAmount($input['payment']['amount']),
            '',
            '',
            '',
            '',
            '',
            'MEBR',
            $input['order']['account_number'],
            'NA',
            'NA',
            'NA',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $traceData = $this->maskUpiDataForTracing($data, [
            Entity::ACCOUNT_NUMBER  => 15,
        ]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'type'              => 'pay',
                'decrypted_content' => $traceData,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body, Action::INTENT_TPV);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'decrypted_content' => $data,
                'encrypted'         => $content,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        $status = $response[ResponseFields::STATUS];

        if ($status !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));

            $ex->markSafeRetryTrue();

            throw $ex;
        }
    }

    protected function checkForValidInput($ciphertext): bool
    {
        // check empty string
        if($ciphertext === '')
        {
            return false;
        }

        // return true if ciphertext is hexadecimal
        return ctype_xdigit($ciphertext);
    }

    /**
     * Get internal payer account type from gateway payer account type
     * @param $input
     * @return string|void
     */
    protected function getInternalPayerAccountType($input)
    {
      if (isset($input[BharatQr\GatewayResponseParams::PAYER_ACCOUNT_TYPE]) === true)
      {
          $payerAccountType = explode("!", (string)$input[BharatQr\GatewayResponseParams::PAYER_ACCOUNT_TYPE]);
          if ((sizeof($payerAccountType)) > 0 and
              (in_array(strtolower($payerAccountType[0]), PayerAccountType::SUPPORTED_PAYER_ACCOUNT_TYPES)) === true) {
              return PayerAccountType::getPayerAccountType(strtolower($payerAccountType[0]));
          }
      }

    }
}
