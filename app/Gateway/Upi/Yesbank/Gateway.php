<?php

namespace RZP\Gateway\Upi\Yesbank;

use Request;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Gateway\Utility;
use RZP\Models\BharatQr;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use Illuminate\Support\Str;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Mindgate;
use RZP\Models\QrCode\Constants;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Currency\Currency;
use RZP\Constants\Entity as CoreEntity;
use RZP\Models\QrCode\Entity as QrEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;
use RZP\Models\Payment\Entity as PaymentEntity;


class Gateway extends Mindgate\Gateway
{
    use RequestTrait;
    use CommonGatewayTrait;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 40;

    const ACQUIRER = 'yesb';

    protected $gateway = 'upi_yesbank';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    const QR_CODE_MAXIMUM_EXPIRY_TIME = 64800;

    protected $map = [
        Entity::VPA                     => Entity::VPA,
        Entity::EXPIRY_TIME             => Entity::EXPIRY_TIME,
        Entity::PROVIDER                => Entity::PROVIDER,
        Entity::BANK                    => Entity::BANK,
        Entity::TYPE                    => Entity::TYPE,
        Entity::RECEIVED                => Entity::RECEIVED,
        Entity::PAYMENT_ID              => Entity::PAYMENT_ID,
        Entity::MERCHANT_REFERENCE      => Entity::MERCHANT_REFERENCE,

        Fields::YBLREFNO                => Entity::GATEWAY_PAYMENT_ID,
        Fields::NPCI_TXN_ID             => Entity::NPCI_TXN_ID,
        Fields::CUST_REF_ID             => Entity::NPCI_REFERENCE_ID,
        Fields::PAYEE_ACC_NAME          => Entity::NAME,
        Fields::PAYEE_ACC_NO            => Entity::ACCOUNT_NUMBER,
        Fields::PAYEE_IFSC              => Entity::IFSC,
        Fields::STATUSCODE              => Entity::STATUS_CODE,
    ];

    protected $verifyStatusToCheck = [
      'BT'
    ];

    /**
     * @param array $input
     * @return array|void
     * @throws Exception\GatewayErrorException
     */
    public function authorize(array $input): array
    {
        parent::action($input, Action::AUTHORIZE);

        if ($this->isBharatQrPayment() === true)
        {
            $input[Fields::CUST_REF_ID] = $input['data']['upi'][Fields::NPCI_REFERENCE_ID] ?? '';

            $input[Entity::TYPE] = Base\Type::PAY;

            $paymentData = $this->createGatewayPaymentEntity($input, Action::AUTHORIZE);

            return [
                'acquirer' => [
                    Payment\Entity::REFERENCE16 => $paymentData->getNpciReferenceId(),
                ],
            ];
        }

        return $this->upiAuthorize($input);
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function callback(array $input) :array
    {
        parent::action($input, Action::CALLBACK);

        return $this->upiCallback($input);
    }

    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if ($routeName === 'payment_callback_bharatqr_internal')
        {
            return $this->getQrData(json_decode($input, true));
        }

        return $this->upiPreProcess(['payload' => $input]);
    }

    public function getPaymentIdFromServerCallback(array $response): string
    {
        return $this->upiPaymentIdFromServerCallback($response);
    }

    /** Check if its duplicate unexpected payment
     * @param array $input
     */
    public function validatePush($input)
    {
        parent::action($input, Action::VALIDATE_PUSH);

        // It checks if the version is V2,which is request from art
        if ((empty($input['meta']['version']) === false) and
            ($input['meta']['version'] === 'api_v2'))
        {
            return $this->isDuplicateUnexpectedPaymentV2($input);
        }

        $this->upiValidatePush($input);
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

    /** AuthroizePush creates gateway entity for the payment
     * @param array $input
     * @return array[]
     */
    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;

        // It checks if the version is V2,which is request from art
        if ((empty($callbackData['meta']['version']) === false) and
            ($callbackData['meta']['version'] === 'api_v2'))
        {
           return $this->authorizePushV2($input);
        }

        return $this->upiAuthorizePush($input);
    }

    protected function authorizePushV2($input)
    {
        list ($paymentId, $content) = $input;

        // Create attributes for upi entity.
        $attributes = [
            Entity::TYPE                => Type::PAY,
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
     * @param array $input
     * @return array|void
     * @throws Exception\LogicException
     */
    public function refund(array $input)
    {
        throw new Exception\LogicException(
            'Live payment refund not available on UPI Yesbank');
    }

    /**
     * @param array $input
     * @return null|void
     * @throws Exception\BaseException
     */
    public function verify(array $input)
    {
        parent::action($input, Action::VERIFY);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        return $this->upiSendPaymentVerifyRequest($verify);
    }

    protected function verifyPayment($verify)
    {
        $this->upiVerifyPayment($verify);
    }

    /**
     * Function to postprocess the response of callback. In case of success, return true.
     * However in case of exception, suppress the error and return failure response.
     * @param  array  $input request array
     * @param \Exception $exception exception object
     * @return array success/failure response
     */
    public function postProcessServerCallback($input, $exception = null): array
    {
        if ($exception === null)
        {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];
    }

    public function payout(array $input)
    {
        parent::action($input, Action::PAYOUT);

        try
        {
            $this->validateRequest($input, $this->action);

            $merchantReference = $this->input[Fields::GATEWAY_INPUT][Fields::REF_ID];

            $gatewayEntity = $this->repo->fetchReceivedByMerchantReference($merchantReference);

            if ($gatewayEntity !== null)
            {
                // a payout with this ref_id already exists
                $this->trace->error(
                    TraceCode::DUPLICATE_PAYOUT_REQUEST,
                    [
                        'reference_id'  => $merchantReference,
                        'input'         => $input,
                    ]);

                return $this->generateResponseForRazorpayFailure(
                    $gatewayEntity,
                    'RZP_DUPLICATE_PAYOUT',
                    $input,
                    Status::PENDING);
            }

            $attributes = $this->getGatewayEntityAttributes($input);

            $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

            $request = $this->getPayoutRequest($input);

            $logRequest = $request;

            if (Str::startsWith($logRequest[Fields::PAYEE_VPA], 'CCPAY') === true)
            {
                $logRequest[Fields::PAYEE_VPA] = mask_except_last4($logRequest[Fields::PAYEE_VPA], 'x');
            }

            $maskedRequest = implode('|', $logRequest);

            $content = implode('|', $request);

            $encryptedContent = $this->encryptRequest($content);

            $requestContent = [
                Fields::PGMERCHANTID => $this->getGatewayMerchantId($input),
                Fields::REQUESTMSG   => $encryptedContent,
            ];

            $request = $this->getStandardRequestArray($requestContent);

            $this->traceGatewayPaymentRequest(
                [
                    'request'           => $request,
                    'decrypted_content' => $maskedRequest
                ],
                $input,
                TraceCode::VPA_PAYOUT_REQUEST
            );

            $response = $this->sendGatewayRequest($request);

            $responseArray = $this->parseGatewayResponse($response->body, Action::PAYOUT);

            $responseArray[Entity::RECEIVED] = 1;

            $responseArray[Fields::STATUSCODE] = Status::getStatusCodeFromMap($responseArray[Fields::STATUSCODE]);

            $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

            if ($responseArray[Fields::RESPCODE] === 'DT'){
                return $this->generateResponseForDuplicatePayout($gatewayPayment,
                    'RZP_DUPLICATE_REFERENCE_RECEIVED',
                    $input,
                    Status::PENDING);
            } else {
                $formattedResponse = $this->generateResponse($responseArray, $gatewayPayment);
            }
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $this->trace->traceException($e);

            $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                          'RZP_PAYOUT_TIMED_OUT',
                                                                           $input,
                                                                          Status::TIMEOUT);
        }
        catch (Exception\GatewayRequestException $ee)
        {
            $this->trace->traceException($ee);

            $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                 'RZP_PAYOUT_REQUEST_FAILURE',
                                                                           $input,
                                                                Status::PENDING);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex);

            $errorCode = $ex->getCode();

            $gatewayPayment = $gatewayPayment ?? null;

            // In case of php run time exceptions $errorCode may be 0 and
            // it will execute the first case of following switch case and not the default case
            if (empty($code) === true)
            {
                $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                    'RZP_PAYOUT_UNKNOWN_ERROR',
                    $input,
                    Status::PENDING);
            }
            else {
                switch ($errorCode) {
                    case ErrorCode::BAD_REQUEST_DUPLICATE_PAYOUT:
                        $formattedResponse = $this->generateResponseForDuplicatePayout($gatewayPayment,
                            'RZP_DUPLICATE_REFERENCE_RECEIVED',
                            $input,
                            Status::PENDING);
                        break;

                    case ErrorCode::BAD_REQUEST_VALIDATION_FAILURE:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                            'RZP_FTA_REQUEST_INVALID', $input);
                        break;

                    case ErrorCode::GATEWAY_ERROR_ENCRYPTION_ERROR:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                            'RZP_REQUEST_ENCRYPTION_FAILURE', $input);
                        break;

                    case ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                            'RZP_REQUEST_DECRYPTION_FAILED',
                            $input,
                            Status::PENDING);
                        break;

                    default:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                            'RZP_PAYOUT_UNKNOWN_ERROR',
                            $input,
                            Status::PENDING);
                }
            }
        }

        return $formattedResponse;
    }

    public function payoutVerify(array $input)
    {
        parent::action($input, Action::PAYOUT_VERIFY);

        try
        {
            $this->validateRequest($input, $this->action);

            $gatewayPayment = $this->repo->fetchByMerchantReference($this->input[Fields::GATEWAY_INPUT][Fields::REF_ID]);

            $request = $this->getPayoutVerifyRequest($input, $gatewayPayment);

            $content = implode('|', $request);

            $encryptedContent = $this->encryptRequest($content);

            $requestContent = [
                Fields::PGMERCHANTID => $this->getGatewayMerchantId($input),
                Fields::REQUESTMSG   => $encryptedContent,
            ];

            $request = $this->getStandardRequestArray($requestContent);

            $this->traceGatewayPaymentRequest(
                [
                    'request'           => $request,
                    'decrypted_content' => $content
                ],
                $input,
                TraceCode::VPA_PAYOUT_VERIFY_REQUEST
            );

            $response = $this->sendGatewayRequest($request);

            $responseArray = $this->parseGatewayResponse($response->body,
                                                         Action::PAYOUT_VERIFY,
                                                         TraceCode::VPA_PAYOUT_VERIFY_GATEWAY_RESPONSE);

            if ($responseArray[Fields::ORDERNO] !== $gatewayPayment[Entity::MERCHANT_REFERENCE])
            {
                $this->trace->error(
                    TraceCode::GATEWAY_FATAL_ERROR,
                    [
                        'input'    => $input,
                        'response' => $responseArray,
                    ]);

                return $this->generateResponseForRazorpayFailure($gatewayPayment, 'RZP_REF_ID_MISMATCH', $input);
            }

            $responseArray[Fields::STATUSCODE] = Status::getStatusCodeFromMap($responseArray[Fields::STATUSCODE]);

            $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

            $responseArray = $this->checkAndUpdateForVerifyStatus($responseArray);

            if ($responseArray[Fields::RESPCODE] === 'DT'){
                return $this->generateResponseForDuplicatePayout($gatewayPayment,
                    'RZP_DUPLICATE_REFERENCE_RECEIVED',
                    $input,
                    Status::PENDING);
            } else {
                $formattedResponse = $this->generateResponse($responseArray, $gatewayPayment);
            }
        }
        catch (Exception\GatewayTimeoutException $e)
        {
            $this->trace->traceException($e);

            $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                           'RZP_PAYOUT_VERIFY_TIMED_OUT',
                                                                           $input,
                                                                           Status::TIMEOUT);
        }
        catch (Exception\GatewayRequestException $ee)
        {
            $this->trace->traceException($ee);

            $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                           'RZP_PAYOUT_VERIFY_REQUEST_FAILURE',
                                                                           $input,
                                                                           Status::TIMEOUT);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex);

            $code = $ex->getCode();

            $gatewayPayment = $gatewayPayment ?? null;

            // In case of php run time exceptions $errorCode may be 0 and
            // it will execute the first case of following switch case and not the default case
            if (empty($code) === true)
            {
                $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                    'RZP_PAYOUT_UNKNOWN_ERROR',
                    $input,
                    Status::PENDING);
            }
            else
            {
                switch ($code)
                {
                    case ErrorCode::BAD_REQUEST_DUPLICATE_PAYOUT:
                        $formattedResponse = $this->generateResponseForDuplicatePayout($gatewayPayment,
                            'RZP_DUPLICATE_REFERENCE_RECEIVED',
                            $input,
                            Status::PENDING);
                        break;

                    case ErrorCode::BAD_REQUEST_VALIDATION_FAILURE:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                                       'RZP_FTA_REQUEST_INVALID',
                                                                                       $input);
                        break;

                    case ErrorCode::GATEWAY_ERROR_ENCRYPTION_ERROR:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                                       'RZP_REQUEST_ENCRYPTION_FAILURE',
                                                                                       $input);
                        break;

                    case ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                                       'RZP_REQUEST_DECRYPTION_FAILED',
                                                                                       $input,
                                                                                       Status::PENDING);
                        break;

                    default:
                        $formattedResponse = $this->generateResponseForRazorpayFailure($gatewayPayment,
                                                                                       'RZP_PAYOUT_UNKNOWN_ERROR',
                                                                                       $input,
                                                                                       Status::PENDING);
                }
            }
        }

        return $formattedResponse;
    }

    public function getParsedDataFromUnexpectedCallback($input)
    {
        return $this->upiGetParsedDataFromUnexpectedCallback($input);
    }

    protected function checkAndUpdateForVerifyStatus(array $input)
    {
        if ((empty($input[Fields::RESPCODE]) === false) and
            (in_array($input[Fields::RESPCODE], $this->verifyStatusToCheck, true) === true) and
            (empty($input[Fields::TIMED_OUT_TXN_STATUS]) === false))
            {
                $derivedRespCode =  $input[Fields::RESPCODE] . '_' . $input[Fields::TIMED_OUT_TXN_STATUS];

                $input[Fields::RESPCODE] = strtoupper($derivedRespCode);
            }

         return $input;
    }

    protected function getPayoutRequest(array $input)
    {
        $content = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId($input),
            Fields::ORDERNO             => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
            Fields::TXN_NOTE            => $this->getNarration($input),
            Fields::AMOUNT              => $this->formatAmount($input[Fields::GATEWAY_INPUT][Fields::AMOUNT]),
            Fields::CURRENCY            => Currency::INR,
            Fields::PAYMENT_TYPE        => Type::P2P,
            Fields::TXN_TYPE            => Type::PAY,
            Fields::MCC                 => $this->getMerchantCategoryCode($input),
            Fields::EXP_TIME            => '',
            Fields::PAYEE_ACC_NO        => '',
            Fields::PAYEE_IFSC          => '',
            Fields::PAYEE_AADHAR        => '',
            Fields::PAYEE_MB_NO         => '',
            Fields::PAYEE_VPA           => $input[Fields::GATEWAY_INPUT][Entity::VPA],
            Fields::SUBMERCHANT_ID      => '',
            Fields::WHITELISTED_ACC     => '',
            Fields::PAYEE_MMID          => '',
            Fields::REF_URL             => 'https://razorpay.com',
            Fields::TRANSFER_TYPE       => Type::UPI,
            Fields::PAYEE_NAME          => 'Razorpay Customer',
            Fields::PAYEE_ADDRESS       => '',
            Fields::PAYEE_EMAIL         => '',
            Fields::PAYER_ACCNO         => $input[Fields::GATEWAY_INPUT][Fields::ACCOUNT_NUMBER] ?? '',
            Fields::PAYER_IFSC          => $input[Fields::GATEWAY_INPUT][Fields::IFSC_CODE] ?? '',
            Fields::PAYER_MB_NO         => '',
            Fields::PAYYE_VPA_TYPE      => Type::VPA,
            Fields::ADD1                => '',
            Fields::ADD2                => '',
            Fields::ADD3                => '',
            Fields::ADD4                => '',
            Fields::ADD5                => '',
            Fields::ADD6                => '',
            Fields::ADD7                => '',
            Fields::ADD8                => '',
            Fields::ADD9                => 'NA',
            Fields::ADD10               => 'NA',
        ];

        return $content;
    }

    protected function getPayoutVerifyRequest($input, $gatewayEntity)
    {
        $request = [
            Fields::PGMERCHANT_ID       => $this->getGatewayMerchantId($input),
            Fields::ORDER_ID            => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
            Fields::YBLREFNO            => $gatewayEntity[Entity::GATEWAY_PAYMENT_ID],
            Fields::CUST_REF_ID         => $gatewayEntity[Entity::NPCI_REFERENCE_ID],
            Fields::REFERENCE_ID        => '',
            Fields::ADD1                => '',
            Fields::ADD2                => '',
            Fields::ADD3                => '',
            Fields::ADD4                => '',
            Fields::ADD5                => '',
            Fields::ADD6                => '',
            Fields::ADD7                => '',
            Fields::ADD8                => '',
            Fields::ADD9                => 'NA',
            Fields::ADD10               => 'NA',
        ];

        return $request;
    }

    /**
     * @param $input
     * @param $action
     */
    protected function validateRequest($input, $action)
    {
        (new Validator)->setStrictFalse()
                       ->validateInput($action, $input);
    }

    /**
     * @param $request
     * @throws Exception\LogicException
     *
     * @return string
     */
    public function encryptRequest($request): string
    {
        try
        {
            return $this->encrypt($request);
        }
        catch (\Exception $e)
        {
            throw new Exception\LogicException(
                'Encryption of payout request failed',
                ErrorCode::GATEWAY_ERROR_ENCRYPTION_ERROR,
                [
                    'data' => $request,
                ]);
        }
    }

    protected function getGatewayEntityAttributes(
        array $input,
        string $action = Action::AUTHORIZE,
        string $type = Type::PAY): array
    {
        return [
            Entity::VPA                 => $this->maskVpaHandle($input[Fields::GATEWAY_INPUT][Entity::VPA]),
            Entity::TYPE                => $type,
            Entity::ACTION              => $action,
            Entity::MERCHANT_REFERENCE  => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
            Entity::AMOUNT              => $input[Fields::GATEWAY_INPUT][Entity::AMOUNT],
            Entity::PAYMENT_ID          => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
        ];
    }

    /**
     * Masks the username part of the Vpa handle Except for last four digits
     * e.g $vpaHandle = 'ccpay.12676372617@okhdfcbank'
     * returns XXXXXXXXXXXXX2617@okhdfcbank
     *
     * @param $vpaHandle
     * @return mixed
     */
    public function maskVpaHandle($vpaHandle)
    {
        if (empty($vpaHandle) === false)
        {
            $endPos = strrpos($vpaHandle, '@');

            $subStr = substr($vpaHandle, 0, $endPos);

            $replacement = mask_except_last4($subStr);

            return substr_replace($vpaHandle, $replacement, 0, $endPos);
        }

        return $vpaHandle;
    }

    protected function getGatewayMerchantId(array $input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $input['terminal']['gateway_merchant_id'];
    }

    /**
     * Returns the MCC code, based on the merchant category
     * @param  array  $input
     * @return string 4 digit integer as string.
     */
    protected function getMerchantCategoryCode(array $input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_mcc'];
        }

        return $input['merchant']['category'] ?: 5411;
    }

    protected function getNarration(array $input)
    {
        if (empty($input[Fields::GATEWAY_INPUT][Fields::NARRATION]) === false)
        {
            return $input[Fields::GATEWAY_INPUT][Fields::NARRATION];
        }

        $merchant = $input['merchant'];

        $merchantBillingLabel = $merchant['billing_label'] ?? 'Razorpay';

        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

        $formattedLabel = ($formattedLabel ? str_limit($formattedLabel, 30) : 'Razorpay');

        $narration = $formattedLabel . ' Fund Transfer';

        return $narration;
    }

    /**
     * This is the key used to encrypt requests
     * @return string public key
     */
    protected function getEncryptionKey()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_key'];
        }

        return $this->config['live_merchant_key'];
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    /**
     * @param $response
     * @return string
     * @throws Exception\GatewayErrorException
     */
    public function decryptResponse($response): string
    {
        try
        {
            $response = $this->decrypt($response);

            return $response;
        }
        catch (\Exception $e)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                null,
                null,
                [
                    'response' => $response,
                ]);
        }
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

        $npciReferenceId = null;

        if ((empty($input['gateway']['meta']['version']) === false) and
            ($input['gateway']['meta']['version'] === 'api_v2'))
        {
            $npciReferenceId = $input['gateway']['upi']['npci_reference_id'];
        }
        else
        {
            $npciReferenceId = $input['gateway']['reference_number'];
        }

        $attributes = [
            Entity::STATUS_CODE        => Status::SUCCESS,
            Entity::NPCI_REFERENCE_ID  => $npciReferenceId,
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    protected function parseGatewayResponse(
        $responseBody,
        $type = Action::PAYOUT,
        $traceCode = TraceCode::VPA_PAYOUT_GATEWAY_RESPONSE)
    {
        $this->trace->info(
            $traceCode,
            [
                'body'              => $responseBody,
                'encrypted'         => true,
                'gateway'           => $this->gateway,
                'type'              => $type
            ]);

        $response = $this->decryptResponse($responseBody);

        $scrubbedResponse = array($response);

        Utility::scrubCardDetails($scrubbedResponse, $this->app);

        $this->trace->info($traceCode, [$scrubbedResponse]);

        $type = strtoupper($type);

        $fields = constant(__NAMESPACE__ . "\Fields::$type");

        $values = explode('|', $response);

        $result = [];

        foreach ($fields as $index => $key)
        {
            $result[$key] = $values[$index];
        }

        $scrubbedResult = array($result);

        Utility::scrubCardDetails($scrubbedResult, $this->app);

        $this->trace->info(
            $traceCode,
            [
                'body'      => $responseBody,
                'decrypted' => $scrubbedResponse,
                'parsed'    => $scrubbedResult,
                'gateway'   => $this->gateway,
                'type'      => $type
            ]);

        return $result;
    }

    protected function generateResponse($responseArray, $gatewayPayment)
    {
        $status = $responseArray[Fields::STATUSCODE];

        $formattedResponse = $this->generateSuccessfulResponse($responseArray, $gatewayPayment);

        if (in_array($status, [Status::SUCCESS, Status::VERIFY_SUCCESS], true) === false)
        {
            $failResponse = $this->generateFailureResponse($responseArray);

            $formattedResponse = array_merge($formattedResponse, $failResponse);
        }

        $this->trace->info(
            TraceCode::GATEWAY_INTERNAL_FORMATTED_RESPONSE,
            $formattedResponse);

        return $formattedResponse;
    }

    protected function generateSuccessfulResponse($responseArray, $gatewayPayment)
    {
        return [
            Fields::SUCCESS                     => true,
            Fields::BANK_REFERENCE_NUMBER       => $gatewayPayment->getGatewayPaymentId(),
            Fields::REQUEST_REFERENCE_NUMBER    => $gatewayPayment->getMerchantReference(),
            Fields::UNIQUE_RESPONSE_NUMBER      => $gatewayPayment->getNpciReferenceId(),
            Fields::STATUS_CODE                 => Status::getStatusCodeFromMap($responseArray[Fields::STATUSCODE]),
            Fields::API_ERROR_CODE              => null,
            Fields::RESPONSE_CODE               => $responseArray[Fields::RESPCODE],
            Fields::ERROR_CODE                  => null,
            Fields::RESPONSE_ERROR_CODE         => null,
            Fields::STATUS_DESC                 => null,
        ];
    }

    protected function generateFailureResponse($responseArray)
    {
        //
        // We have null as default since in verify we may not get some of these fields.
        //

        $responseCode = $responseArray[Fields::RESPCODE] ?? null;
        $errorCode = $responseArray[Fields::ERRORCODE] ?? null;
        $errorCode = $errorCode ?? $responseArray[Fields::ERROR_CODE] ?? null;
        $responseErrorCode = $responseArray[Fields::RESPONSE_ERROR_CODE] ?? null;

        $apiErrorCode = ResponseCodeMap::getApiErrorCode($responseCode,
                                                         $errorCode,
                                                         $responseErrorCode);

        $gatewayErrorCodeDesc = ResponseCodes::getResponseMessage($responseCode,
                                                                  $errorCode,
                                                                  $responseErrorCode,
                                                                  $responseArray[Fields::STATUSDESC]);

        return [
            Fields::SUCCESS                     => false,
            Fields::STATUS_CODE                 => Status::getStatusCodeFromMap($responseArray[Fields::STATUSCODE]),
            Fields::API_ERROR_CODE              => $apiErrorCode,
            Fields::RESPONSE_CODE               => $responseCode,
            Fields::ERROR_CODE                  => $errorCode,
            Fields::RESPONSE_ERROR_CODE         => $responseErrorCode,
            Fields::STATUS_DESC                 => $gatewayErrorCodeDesc,
        ];
    }

    protected function generateResponseForRazorpayFailure(
        $gatewayPayment,
        $errorCode,
        $ftaInput = [],
        $statusCode = Status::FAILURE)
    {
        $apiErrorCode = ResponseCodeMap::getApiErrorCode($errorCode);

        $gatewayErrorCodeDesc = ResponseCodes::getResponseMessage($errorCode);

        $response = [
            Fields::SUCCESS                     => false,
            Fields::STATUS_CODE                 => $statusCode,
            Fields::API_ERROR_CODE              => $apiErrorCode,
            Fields::RESPONSE_CODE               => $errorCode,
            Fields::ERROR_CODE                  => $errorCode,
            Fields::RESPONSE_ERROR_CODE         => $errorCode,
            Fields::STATUS_DESC                 => $gatewayErrorCodeDesc,
        ];

        if (empty($gatewayPayment) === false)
        {
            $gatewayPaymentResponse = [
                Fields::BANK_REFERENCE_NUMBER       => $gatewayPayment->getGatewayPaymentId(),
                Fields::REQUEST_REFERENCE_NUMBER    => $gatewayPayment->getMerchantReference(),
                Fields::UNIQUE_RESPONSE_NUMBER      => $gatewayPayment->getNpciReferenceId(),
            ];

            $response = array_merge($response, $gatewayPaymentResponse);
        }

        if (empty($response[Fields::REQUEST_REFERENCE_NUMBER]) === true)
        {
            $response[Fields::REQUEST_REFERENCE_NUMBER] = $ftaInput[Fields::GATEWAY_INPUT][Fields::REF_ID] ?? null;
        }

        $this->trace->info(
            TraceCode::GATEWAY_INTERNAL_FORMATTED_RESPONSE,
            $response);

        return $response;
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info(
            $traceCode,
            [
                'payment_id' => $input[Fields::GATEWAY_INPUT][Fields::REF_ID],
                'request'    => $request,
                'gateway'    => $this->gateway,
            ]);
    }

    protected function generateResponseForDuplicatePayout(
        $gatewayPayment,
        $errorCode,
        $ftaInput = [],
        $statusCode = Status::PENDING)
    {
        $apiErrorCode = ResponseCodeMap::getApiErrorCode($errorCode);

        $gatewayErrorCodeDesc = ResponseCodes::getResponseMessage($errorCode);

        $response = [
            Fields::SUCCESS                     => false,
            Fields::STATUS_CODE                 => $statusCode,
            Fields::API_ERROR_CODE              => $apiErrorCode,
            Fields::RESPONSE_CODE               => $errorCode,
            Fields::ERROR_CODE                  => $errorCode,
            Fields::RESPONSE_ERROR_CODE         => $errorCode,
            Fields::STATUS_DESC                 => $gatewayErrorCodeDesc,
        ];

        if (empty($gatewayPayment) === false)
        {
            $gatewayPaymentResponse = [
                Fields::BANK_REFERENCE_NUMBER       => $gatewayPayment->getGatewayPaymentId(),
                Fields::REQUEST_REFERENCE_NUMBER    => $gatewayPayment->getMerchantReference(),
                Fields::UNIQUE_RESPONSE_NUMBER      => $gatewayPayment->getNpciReferenceId(),
            ];

            $response = array_merge($response, $gatewayPaymentResponse);
        }

        if (empty($response[Fields::REQUEST_REFERENCE_NUMBER]) === true)
        {
            $response[Fields::REQUEST_REFERENCE_NUMBER] = $ftaInput[Fields::GATEWAY_INPUT][Fields::REF_ID] ?? null;
        }

        $this->trace->info(
            TraceCode::GATEWAY_INTERNAL_FORMATTED_RESPONSE,
            $response);

        return $response;
    }

    public function getTerminalDetailsFromCallbackIfApplicable($input)
    {
        return null;
    }

    protected function checkForPaymentFailure($input)
    {
        if ($input[Fields::STATUS] !== Status::SUCCESS_STATUS)
        {
            $this->trace->error(
                TraceCode::QR_PAYMENT_FAILED_TRANSACTION_CALLBACK,
                [
                    'notification_request' => $input,
                    'gateway'              => $this->gateway
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_BQR_PAYMENT_FAILED,
                null,
                null,
                [
                    'notification_request' => $input,
                    'gateway'              => $this->gateway
                ]);
        }
    }

    public function getQrData(array $input)
    {
        $inputFields = $input['data'];

        $this->checkForPaymentFailure($inputFields);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $inputFields['payment'][Fields::AMOUNT_AUTHORIZED],
            BharatQr\GatewayResponseParams::VPA                   => $inputFields['upi']['vpa'],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::UPI,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => substr($inputFields['upi'][Fields::MERCHANT_REFERENCE], 0, UniqueIdEntity::ID_LENGTH),
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $inputFields['upi'][Fields::NPCI_REFERENCE_ID],
            BharatQr\GatewayResponseParams::PAYEE_VPA             => $inputFields['terminal']['vpa'],
        ];

        if (empty($inputFields['meta']['response']['content']) === false)
        {
            $transactionTime = Carbon::createFromFormat('Y:m:d H:i:s', $inputFields['meta']['response']['content'][Fields::TRANSACTION_AUTH_DATE],
                                                        Timezone::IST);

            if ($transactionTime !== false)
            {
                $qrData[BharatQr\GatewayResponseParams::TRANSACTION_TIME] = $transactionTime->getTimestamp();
            }

            $qrData[BharatQr\GatewayResponseParams::NOTES] = $inputFields['meta']['response']['content'][Fields::PAYER_NOTE] ?? '';
        }

        if (isset($input['data']['meta']) === true)
        {
            unset($input['data']['meta']);
        }
        if (isset($input['data']['_raw']) === true)
        {
            unset($input['data']['_raw']);
        }

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    private function getExpiryTime($input): string
    {
        $closeBy = $input[CoreEntity::QR_CODE][QrEntity::CLOSE_BY] ?? null;
        $expTime = 'NA';

        if (is_null($closeBy) === false)
        {
            $expiryInMinutes = (int) (($closeBy - Carbon::now()->getTimestamp()) / 60);

            $expTime = (string) min($expiryInMinutes, self::QR_CODE_MAXIMUM_EXPIRY_TIME);
        }

        return $expTime;
    }

    private function buildRequest($input): array
    {
        $expTime = $this->getExpiryTime($input);

        $input[CoreEntity::QR_CODE]['id']                .= Constants::QR_CODE_V2_TR_SUFFIX;
        $input[CoreEntity::QR_CODE][Entity::EXPIRY_TIME] = $expTime;

        $request = [
            'payment'  => [
                'gateway' => $this->gateway
            ],
            'terminal' => [
                'gateway_merchant_id' => $input[CoreEntity::TERMINAL][Fields::GATEWAY_MERCHANT_ID]
            ],
            'qr_code'  => $input[CoreEntity::QR_CODE],
        ];

        return $request;
    }

    /**
     * @param $input
     *
     * @return string
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function getQrRefId($input): string
    {
        $request = $this->buildRequest($input);

        $this->trace->info(TraceCode::CREATE_QR_MOZART_REQUEST, [
            'request' => $request,
            'gateway' => $this->gateway
        ]);

        $result = $this->upiSendGatewayRequest($request,
                                               TraceCode::GATEWAY_INTENT_REQUEST,
                                               Action::INTENT_QR
        );

        $data = $result['data'];

        $this->trace->info(TraceCode::CREATE_QR_MOZART_RESPONSE, [
            'statusCode'  => $data[Fields::STATUS_CODE],
            'refId'       => $data['upi'][Fields::GATEWAY_PAYMENT_ID],
            'description' => $data['description'],
            'gateway'     => $this->gateway
        ]);

        if (($data[Fields::STATUS_CODE] === Status::SUCCESS) and
            (!is_null($data['upi'])) and
            ($data['upi'][Fields::GATEWAY_PAYMENT_ID] !== 'NA'))
        {
            return $data['upi'][Fields::GATEWAY_PAYMENT_ID];
        }

        return throw new Exception\RuntimeException('Invalid Response from Mozart');
    }
}
