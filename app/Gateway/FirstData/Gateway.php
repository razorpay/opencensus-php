<?php

namespace RZP\Gateway\FirstData;

use App;
use Queue;

use Carbon\Carbon;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Constants\Entity as E;
use RZP\Reconciliator\Base\InfoCode;
use SimpleXMLElement;
use RZP\Constants\Timezone;

use RZP\Diag\EventCode;
use RZP\Error;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Mpi;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\ScroogeResponse;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;
    use Base\CardCacheTrait;

    const CACHE_PREFIX = '{first_data}:';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';
    const CERTIFICATE_FORMAT_P12     = 'p12';

    const CARD_CACHE_TTL             = 20;
    const CACHE_KEY                  = 'first_data_%s_card_details';
    const PROCESSING                 = 'PROCESSING';
    const SERVICES                   = 'SERVICES';

    const CHECKSUM_ATTRIBUTE         = ConnectResponseFields::RESPONSE_HASH;

    const MINIMUM_CARD_NAME_LENGTH   = 3;
    const CARD_NAME_PADDING          = 'X';

    const PRE_AUTH_TRANSACTION_TYPE  = 'PREAUTH';
    const SALE_TRANSACTION_TYPE      = 'SALE';

    const PARES_DATA_CACHE_KEY       = self::CACHE_PREFIX . 'pares_';

    const STATUS                     = 'status';

    protected $gateway = Constants\Entity::FIRST_DATA;

    protected $secureCacheDriver;

    /**
     * @var boolean
     */
    protected $s2sFlowFlag = false;

    protected $verifyInternalErrorCodes = [
        ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
    ];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->secureCacheDriver = $this->getDriver();
    }

    const TRACE_CODE_MAPPING = [
        Action::PURCHASE  => TraceCode::GATEWAY_PURCHASE_RESPONSE,
        Action::CAPTURE   => TraceCode::GATEWAY_CAPTURE_RESPONSE,
        Action::REFUND    => TraceCode::GATEWAY_REFUND_RESPONSE,
        Action::REVERSE   => TraceCode::GATEWAY_REVERSE_RESPONSE,
    ];

    const OLD_STORE_IDS = [
        // EMI terminals
        '3374679283',
        '3374679291',
        '3374679309',
        '3374679333',
        // Shared FirstData terminal, disabled now
        '3396093976',
    ];

    /**
     * A Parsed object for approval_code string
     *
     * @var ApprovalCode
     */
    protected $approvalCode;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $this->setS2sFlowFlag($input);

        if ($this->isSecondRecurringPayment($input) === true)
        {
            return $this->secondRecurringOrMoto($input);
        }

        if ($this->isMotoTransactionRequest($input) === true)
        {
            return $this->secondRecurringOrMoto($input);
        }

        // this is a check to decide which flow to go from, once new s2s flow will be merged and tested
        // we will remove this check.
        if ($this->isS2sFlowSupported($input) === true)
        {
            $authenticationGateway = $this->decideAuthenticationGateway($input);

            switch ($authenticationGateway)
            {
                case Payment\Gateway::MPI_BLADE:
                case Payment\Gateway::MPI_ENSTAGE:
                    $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

                    $authorizeFields = $this->getFirstDataFieldsForBladeAuthentication($input);
                    $firstDataEntity = $this->createGatewayPaymentEntity($authorizeFields, $input);

                    if ($authResponse !== null)
                    {
                        $this->persistCardDetailsTemporarily($input);

                        return $authResponse;
                    }

                    // We send request for not enrolled cards same as second recurring request.
                    $authorizeRequest = $this->prepareNotEnrolledAuthorizeRequest($input);

                    return $this->authorizeNotEnrolled($input, $authorizeRequest);

                default:
                    parent::action($input, Action::AUTHENTICATE);

                    $response = $this->enroll($input);

                    parent::authorize($input);

                    return $this->decideStepAfterEnroll($response, $input);

                    break;
            }
        }

        $requestContent = $this->getPreAuthRequestContentArray($input);

        $authorizeFields = $this->getAuthorizeFields($requestContent);

        $authorizeEntity = $this->createGatewayPaymentEntity($authorizeFields, $input);

        $request = $this->getStandardRequestArray($requestContent);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        return $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);
    }

    protected function decideAuthenticationGateway($input)
    {
        if (empty($input['authenticate']['gateway']) === false)
        {
            $authenticationGateway = $input['authenticate']['gateway'];
        }
        else
        {
            $authenticationGateway = Payment\Gateway::FIRST_DATA;
        }

        return $authenticationGateway;
    }

    protected function secondRecurringOrMoto(array $input)
    {
        parent::action($input, Action::PURCHASE);

        $requestContent = $this->getPurchaseRequestArrayWithCard($input);

        $traceContent = $requestContent;
        unset($traceContent[ApiRequestFields::V1_TRANSACTION][ApiRequestFields::V1_CREDIT_CARD_DATA]);

        $gatewayPayment = [
            'amount' => $input['payment'][Payment\Entity::AMOUNT],
        ];

        $gatewayEntity = $this->createGatewayPaymentEntity($gatewayPayment, $input);

        $this->trace->info(TraceCode::GATEWAY_PURCHASE_REQUEST, $traceContent);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_PURCHASE_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'response'   => $response
            ]
        );

        $this->setApproval($response[ApiResponseFields::APPROVAL_CODE]);

        $purchaseFields = $this->getPurchaseFields($response, $input['payment']);

        $purchaseEntity = $this->updateGatewayPaymentEntity($gatewayEntity, $purchaseFields, false);

        $this->checkApprovalCode($purchaseEntity);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->traceGatewayCallback($input['gateway']);

        if (empty($input['gateway']) === true)
        {
            // If the callback body is empty, then it's likely because the customer has accidentally
            // sent us a GET request from his browser during redirection. In this case we can treat
            // the payment as failed (effectively a timeout), and let verify handle it like a boss.
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_MISSING_DATA);
        }

        if ($this->isS2sFlow($input['gateway']) === true)
        {
            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], Action::AUTHORIZE);

            switch ($input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY])
            {
                case Payment\Gateway::MPI_BLADE:
                case Payment\Gateway::MPI_ENSTAGE:
                    $authResponse = $this->callAuthenticationGateway($input,
                                                        $input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY]);

                    $authorizeRequest = $this->prepareAuthorizeRequestFromBladeResp($input, $authResponse);

                    $this->authorizeEnrolled($input, $gatewayPayment, $authorizeRequest);

                    break;
                default:
                    $authorizeRequest = $this->getAuthorizeRequest($input, $gatewayPayment);

                    $this->authorizeEnrolled($input, $gatewayPayment, $authorizeRequest);

                    break;
            }
        }
        else
        {
            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], Action::AUTHORIZE);

            $this->verifySecureHash($input['gateway']);

            $this->assertPaymentId($input['payment']['id'], $input['gateway'][ConnectResponseFields::ORDER_ID]);

            $expectedAmount = number_format($input['payment']['amount'] / 100,
                2, '.', '');

            $actualAmount   = number_format($input['gateway'][ConnectResponseFields::CHARGE_TOTAL],
                2, '.', '');

            $this->assertAmount($expectedAmount, $actualAmount);

            $this->mockApprovalCodeIfNeeded($input['gateway']);

            $this->setApproval($input['gateway'][ConnectResponseFields::APPROVAL_CODE]);

            $attributes = $this->getCallbackFields($input['gateway']);

            $this->runCallbackVerify($input, $gatewayPayment);

            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);

            $this->checkApprovalCode($gatewayPayment);
        }

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function prepareAuthorizeRequestFromBladeResp($input, $authResponse)
    {
        $txnType = $this->getTransactionType($input);

        $this->setCardNumberAndCvv($input);

        $gatewayInput = $input['gateway'];

        $cardMonth = str_pad($input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH],
            2, '0', STR_PAD_LEFT);

        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $amount = $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT] / 100;
        $amount = number_format($amount, 2, '.', '');

        $requestArray = [
            ApiRequestFields::V1_TRANSACTION => [
                ApiRequestFields::V1_CREDIT_CARD_TX_TYPE => [
                    ApiRequestFields::V1_STORE_ID => $this->getStoreId(),
                    ApiRequestFields::V1_TYPE     => $txnType,
                ],
                ApiRequestFields::V1_CREDIT_CARD_DATA => [
                    ApiRequestFields::V1_CARD_NUMBER    => $input['card']['number'],
                    ApiRequestFields::V1_EXPIRY_MONTH   => $cardMonth,
                    ApiRequestFields::V1_EXPIRY_YEAR    => substr($input['card']['expiry_year'],-2),
                    ApiRequestFields::V1_CARD_CODE_VALUE => $input[Constants\Entity::CARD][Card\Entity::CVV]
                ],
                ApiRequestFields::V1_CREDIT_CARD_3D_SECURE => [
                    ApiRequestFields::V1_VERIFICATION_RESPONSE => $authResponse['enrolled'],
                    ApiRequestFields::V1_PAYER_AUTHENTICATION_RESPONSE => $authResponse['status'],
                    ApiRequestFields::V1_AUTHENTICATION_VALUE => $authResponse['cavv'],
                    ApiRequestFields::V1_XID => $authResponse['xid'],
                ],
                ApiRequestFields::V1_PAYMENT => [
                    ApiRequestFields::V1_CHARGE_TOTAL => $amount,
                    ApiRequestFields::V1_CURRENCY => $currencyCode,
                ],
                ApiRequestFields::V1_TRANSACTION_DETAILS => [
                    ApiRequestFields::V1_ORDER_ID => $input['payment']['id'],
                ],
            ]
        ];

        return $requestArray;
    }

    public function otpGenerate(array $input)
    {
        if ((isset($input['otp_resend']) === true) and
            ($input['otp_resend'] === true))
        {
            return $this->otpResend($input);
        }

        return $this->authorize($input);
    }

    public function otpResend(array $input)
    {
        parent::action($input, Base\Action::OTP_RESEND);

        $mpiEntity = $this->app['repo']
                          ->mpi
                          ->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        if ($mpiEntity->getGateway() !== Payment\Gateway::MPI_ENSTAGE)
        {
            //
            // This error is consistent with error thrown in otpResend trait
            throw new Exception\LogicException(
                'Gateway does not support OTP resend',
                null,
                ['payment_id' => $input['payment']['id']]);
        }

        $authenticationGateway = $mpiEntity->getGateway();

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        return $authResponse;
    }

    public function callbackOtpSubmit(array $input)
    {
        return $this->callback($input);
    }

    protected function runCallbackVerify(array $input, Entity $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->verifyPayment($verify);

        if (($verify->gatewaySuccess === false) and
            ($this->approval === true))
        {
            $verifyStatus = $verify->payment->getStatus();

            // Callback verify is failing, but possibly only
            // because verify status has not been updated.
            //
            // This should still be considered a failure,
            // but not a case of data tampering.
            if (in_array($verifyStatus, Status::WAITING_STATES, true) === true)
            {
                throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
            }

            throw new Exception\LogicException(
                'Data tampering found.',
                null,
                [
                    'payment_id'      => $input['payment']['id'],
                    'callback_result' => $this->approval,
                    'verify_result'   => $verify->gatewaySuccess,
                ]);
        }
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $requestContent = $this->getCaptureRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_CAPTURE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_CAPTURE_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'response' => $response
            ]
        );

        $this->setApproval($response[ApiResponseFields::APPROVAL_CODE]);

        $captureFields = $this->getCaptureFields($response, $input['payment']);

        $captureEntity = $this->createGatewayPaymentEntity($captureFields, $input);

        $this->checkApprovalCode($captureEntity);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $requestContent = $this->getRefundRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'refund_id' => $input['refund']['id'],
                'request'   => $requestContent,
            ]);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'refund_id' => $input['refund']['id'],
                'response'  => $response,
            ]);

        $this->setApproval($response[ApiResponseFields::APPROVAL_CODE]);

        $refundFields = $this->getRefundFields($response, $input['refund']);

        $refundEntity = $this->createGatewayPaymentEntity($refundFields, $input);

        $this->checkApprovalCode($refundEntity, $response, $refundFields);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($refundFields)
        ];
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                Entity::TDATE                   => $refundFields[Entity::TDATE] ?? null,
                Entity::AUTH_CODE               => $refundFields[Entity::AUTH_CODE] ?? null,
                Entity::APPROVAL_CODE           => $refundFields[Entity::APPROVAL_CODE] ?? null,
                Entity::TRANSACTION_RESULT      => $refundFields[Entity::TRANSACTION_RESULT] ?? null,
                Entity::GATEWAY_TRANSACTION_ID  => $refundFields[Entity::GATEWAY_TRANSACTION_ID] ?? null,
            ];
        }

        return [];
    }

    public function reverse(array $input)
    {
        parent::reverse($input);

        $requestContent = $this->getReverseRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_REVERSE_REQUEST,
            [
                'refund_id' => $input['refund']['id'],
                'request'   => $requestContent,
            ]);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REVERSE_RESPONSE,
            [
                'refund_id' => $input['refund']['id'],
                'response'  => $response,
            ]);

        $this->setApproval($response[ApiResponseFields::APPROVAL_CODE]);

        $reverseFields = $this->getReverseFields($response, $input['refund']);

        $reverseEntity = $this->createGatewayPaymentEntity($reverseFields, $input);

        $this->checkApprovalCode($reverseEntity, $response, $reverseFields);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($reverseFields)
        ];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $refundedEntities = $this->repo->findSuccessfulRefundByRefundId($refundId);

        if ($refundedEntities->count() === 0)
        {
            return false;
        }

        $refundEntity = $refundedEntities->first();

        $refundEntityPaymentId = $refundEntity->getPaymentId();
        $refundEntityRefundAmount = $refundEntity->getAmount();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input'                 => $input,
                'refund_payment_id'     => $refundEntityPaymentId,
                'gateway_refund_amount' => $refundEntityRefundAmount
            ]);

        if (($refundEntityPaymentId !== $paymentId) or
            ($refundEntityRefundAmount !== $refundAmount))
        {
            return false;
        }

        return true;
    }

    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        $scroogeResponse = new ScroogeResponse();

        if ((isset($input['refund']['reverse']) === true) and ($input['refund']['reverse'] === true))
        {
            parent::action($input, Action::VERIFY_REVERSE);

            //
            // Temporary hack. FirstData verifyReverse needs to be
            // refactored to use a different gateway API, since it
            // is currently timing out regularly.
            // Returning false here, so reversal on gateway will be called always.
            // Gateway keeps check if reversal or refunded already happened.
            //
            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
                [
                    'message'    => 'Temporarily blocking verifyRefund',
                    'payment_id' => $input['refund']['payment_id'],
                    'refund_id'  => $input['refund']['id'],
                ]);

            return $scroogeResponse->setSuccess(false)
                                    ->setStatusCode(ErrorCode::GATEWAY_PAYMENT_REVERSAL_VERIFICATION_DISABLED)
                                    ->toArray();
        }

        $this->validateVerifyRefundIsPossible($input);

        $verify = new Base\Verify($this->gateway, $input);

        $this->sendVerifyRequest($verify);

        return $this->verifyRefundResponse($verify);
    }

    protected function verifyRefundResponse(Base\Verify $verify)
    {
        $refundTransactionValue = $this->getRefundTransactionValue($verify);

        $scroogeResponse = new ScroogeResponse();

        if ($refundTransactionValue === null)
        {
            return $scroogeResponse->setSuccess(false)
                                    ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                    ->setGatewayVerifyResponse($verify->verifyResponseContent)
                                    ->toArray();
        }

        $xmlResponse  = $refundTransactionValue->children('ipgapi', true)
                                               ->IPGApiOrderResponse
                                               ->children('ipgapi', true);

        $refundResponse = json_decode(json_encode($xmlResponse), true);

        $this->setApproval($refundResponse[ApiResponseFields::APPROVAL_CODE]);

        $refundFields = $this->getRefundFields($refundResponse, $verify->input['refund']);

        $refundEntity = $this->updateOrCreateRefundEntity($refundFields, $verify->input);

        $refundGatewayStatus = (string) $refundTransactionValue->TransactionState;

        assertTrue(($refundGatewayStatus !== null), 'Status cannot be null');

        $refunded = in_array($refundGatewayStatus, Status::SUCCESSFUL_REFUND_STATES, true);

        $this->checkApprovalCode($refundEntity, $refundResponse, $refundFields);

        return $scroogeResponse->setSuccess($refunded)
                                ->setGatewayVerifyResponse($refundResponse)
                                ->setGatewayKeys($refundFields)
                                ->toArray();
    }

    protected function getRefundTransactionValue(Base\Verify $verify)
    {
        $verifyRefundResponse = $verify->verifyResponseContent;

        if ($verifyRefundResponse === null)
        {
            // FirstData is returning an an invalid response, i.e. success flag
            // set to false, implying that the id does not exist on their end
            return null;
        }

        $refundTransactionValue = null;

        if ($this->action === Action::VERIFY_REVERSE)
        {
            $refundTransactionValue  = $verifyRefundResponse->children('a1', true)
                                                            ->TransactionValues;
        }
        else
        {
            $xmlResponse  = $verifyRefundResponse->children('a1', true)
                                                 ->TransactionValues;

            foreach ($verifyRefundResponse->children('a1', true)->TransactionValues as $transactionValue)
            {
                $refundId = (string) $transactionValue->children('v1', true)
                                                      ->TransactionDetails
                                                      ->MerchantTransactionId;

                if ($refundId === $verify->input['refund']['id'])
                {
                    $refundTransactionValue = $transactionValue;
                }
            }
        }

        return $refundTransactionValue;
    }

    protected function updateOrCreateRefundEntity(array $refundFields, array $input): Entity
    {
        $gatewayRefundEntity = $this->repo->findByRefundId($refundFields['refund_id']);

        if ($gatewayRefundEntity === null)
        {
            $gatewayRefundEntity = $this->getNewGatewayPaymentEntity();

            $gatewayRefundEntity->setPaymentId($input['payment'][Payment\Entity::ID]);

            $gatewayRefundEntity->setAction(Action::REFUND);
        }

        $gatewayRefundEntity->fill($refundFields);

        $this->repo->saveOrFail($gatewayRefundEntity);

        return $gatewayRefundEntity;
    }

    protected function validateVerifyRefundIsPossible(array $input)
    {
        // Refunds can be verified if a reference id was sent in the refund request
        // (or the preauth request for verify reverse)

        // Reference Id was added to refund request in 77fa69f, and deployed in
        // https://app.wercker.com/Razorpay/api/runs/prod-api/5948f98afe92eb00017640f4
        // Tue Jun 20 16:10:00 IST 2017
        if (($this->action === Action::VERIFY_REFUND) and
            ($input['refund']['created_at'] > 1497955200))
        {
            return;
        }

        // Reference Id was added to preauth request in 35c92d4, and deployed in
        // https://app.wercker.com/Razorpay/api/runs/prod-api/59536df68752360001422e03
        // Wed Jun 28 14:25:00 IST 2017
        if (($this->action === Action::VERIFY_REVERSE) and
            ($input['refund']['created_at'] > 1498640100))
        {
            return;
        }

        //
        // For refunds older than this, verification is not possible.
        // Throwing exception here and catching in Processor/refund. Can't return true/false here as
        // if false is returned, that means refund success is false and call gateway refund which is incorrect.
        // Same way if true is returned, refund will be marked processed considering
        // refund is successful at gateway side. Catched exception will return false with error code to scrooge.
        //
        throw new Exception\LogicException(
            'Verification is not possible for older refunds.',
            ErrorCode::GATEWAY_VERIFY_OLDER_REFUNDS_DISABLED,
            [
                'payment_id' => $input['refund']['payment_id'],
                'refund_id'  => $input['refund']['id'],
            ]);
    }

    // First Data is not returning approval code in some cases.
    // In these cases, we mock the code and handle it appropriately.
    protected function mockApprovalCodeIfNeeded(array & $gatewayCallback)
    {
        if (empty($gatewayCallback[ConnectResponseFields::APPROVAL_CODE]) === false)
        {
            return;
        }

        //Approval code wasn't returned, but we can generate one from fail fields
        if (isset($gatewayCallback[ConnectResponseFields::FAIL_RC]) === true)
        {
            $failCode = $gatewayCallback[ConnectResponseFields::FAIL_RC];

            $failReason = $gatewayCallback[ConnectResponseFields::FAIL_REASON];

            $mockedApprovalCode = implode(':', ['N', $failCode, $failReason]);
        }
        // Approval code wasn't returned, and neither were fail_rc and fail_reason
        // Assume failure, and mock the failed approval code.
        else
        {
            $mockedApprovalCode = implode(':', ['N', Codes::MOCK_FAIL_APPROVAL_CODE]);
        }

        $gatewayCallback[ConnectResponseFields::APPROVAL_CODE] = $mockedApprovalCode;
    }

    /**
     * Must be called before processing Gateway response
     * where we expect the approval code. It set approvalCode
     * property and from that approval(boolean) property
     *
     * @param string $approvalCode
     */
    protected function setApproval(string $approvalCode)
    {
        $this->approvalCode = new ApprovalCode($approvalCode);

        $this->approval = $this->approvalCode->isSuccess();
    }

    protected function getSoapResponse(array $requestContent)
    {
        $xmlResponse = $this->postSoapRequest($requestContent, ApiRequestFields::ORDER_REQUEST);

        $traceCode = $this->getTraceCode();

        $this->trace->info($traceCode, [$xmlResponse->asXml()]);

        $response = $this->parseOrderResponse($xmlResponse);

        return $response;
    }

    protected function checkApprovalCode(Entity $gatewayEntity, array $response = [], array $refundFields = [])
    {
        if ($this->approval === false)
        {
            $errorCode = $this->approvalCode->getErrorCode();

            $gatewayErrorDesc = ErrorCodes::getErrorDesc($errorCode);

            $mappedErrorCode = ErrorCodes::getMappedCode($errorCode);

            // Cryptic error messages that First Data keeps sending us
            $this->checkSpecialCases($errorCode, $gatewayEntity, $gatewayErrorDesc);

            $responseKey = ($this->action === Action::VERIFY_REFUND) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE : Payment\Gateway::GATEWAY_RESPONSE;

            throw new Exception\GatewayErrorException(
                $mappedErrorCode,
                $errorCode,
                $gatewayErrorDesc,
                [
                    $responseKey                    => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS   => $this->getGatewayData($refundFields)
                ]);
        }
    }

    protected function checkSpecialCases(string $approvalCode, Entity $gatewayEntity, string $gatewayErrorDesc)
    {
        if (ErrorCodes::isSpecialCase($approvalCode) === true)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_FIRST_DATA_UNEXPECTED,
                [
                    'approval_code' => $gatewayEntity->getApprovalCode(),
                    'error_msg'     => $gatewayErrorDesc,
                ]
            );
        }
    }

    protected function getAuthorizeFields(array $authRequest)
    {
        $attributes = [
            Entity::AMOUNT             => $authRequest[ConnectRequestFields::CHARGE_TOTAL] * 100,
            Entity::CURRENCY           => $authRequest[ConnectRequestFields::CURRENCY],
            Entity::GATEWAY_PAYMENT_ID => $authRequest[ConnectRequestFields::ORDER_ID],
        ];

        return $attributes;
    }
    protected function getFirstDataFieldsForBladeAuthentication(array $input)
    {
        $attributes = [
            Entity::AMOUNT             => $input['payment']['amount'],
        ];

        return $attributes;
    }

    protected function getCallbackFields(array $callbackBody)
    {
        $attributes = [
            Entity::RECEIVED                => true,
            Entity::APPROVAL_CODE           => $this->approvalCode->getFormattedCode(),
        ];

        $this->setFieldIfPresent($attributes, Entity::TRANSACTION_RESULT,
                    ConnectResponseFields::STATUS, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TRANSACTION_ID,
                    ConnectResponseFields::IPG_TRANSACTION_ID, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::ENDPOINT_TRANSACTION_ID,
                        ConnectResponseFields::ENDPOINT_TRANSACTION_ID, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TERMINAL_ID,
                        ConnectResponseFields::TERMINAL_ID, $callbackBody);

        if ($attributes[Entity::TRANSACTION_RESULT] === Status::APPROVED)
        {
            $attributes[Entity::STATUS]    = Status::AUTHORIZED;

            $attributes[Entity::AUTH_CODE] = $this->approvalCode->getAuthCode();

            $attributes[Entity::TDATE]     = $callbackBody[ConnectResponseFields::TDATE];
        }

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
    }

    protected function getPurchaseFields(array $response, array $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setFieldIfPresent($attributes, Entity::AUTH_CODE,
            ApiResponseFields::PROCESSOR_APPROVAL_CODE, $response);

        return $attributes;
    }

    protected function getCaptureFields(array $response, array $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setFieldIfPresent($attributes, Entity::AUTH_CODE,
            ApiResponseFields::PROCESSOR_APPROVAL_CODE, $response);

        return $attributes;
    }

    protected function getRefundFields(array $response, array $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setFieldIfPresent($attributes, Entity::AUTH_CODE,
            ApiResponseFields::PROCESSOR_APPROVAL_CODE, $response);

        $this->setRefundId($attributes, $input);

        return $attributes;
    }

    protected function getReverseFields(array $response, array $input)
    {
        $attributes = $this->getCommonResponseFields($response, $input);

        $this->setRefundId($attributes, $input);

        return $attributes;
    }

    protected function getCommonResponseFields(array $response, array $input)
    {
        $currencyCode = Currency::ISO_NUMERIC_CODES[$input['currency']];

        $attributes = [
            Entity::RECEIVED      => true,
            Entity::APPROVAL_CODE => $this->approvalCode->getFormattedCode(),
            Entity::AMOUNT        => $input['amount'],
            Entity::CURRENCY      => $currencyCode,
            Entity::STATUS        => Status::CAPTURED,
        ];

        $this->setFieldIfPresent($attributes, Entity::TDATE,
                    ApiResponseFields::TDATE, $response);

        $this->setFieldIfPresent($attributes, Entity::TRANSACTION_RESULT,
                    ApiResponseFields::TRANSACTION_RESULT, $response);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_PAYMENT_ID,
                    ApiResponseFields::ORDER_ID, $response);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TRANSACTION_ID,
                    ApiResponseFields::IPG_TRANSACTION_ID, $response);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TERMINAL_ID,
                    ApiResponseFields::TERMINAL_ID, $response);

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
    }

    protected function setFieldIfPresent(array & $attributes, string $field,
                                            string $responseField, array $response)
    {
        if (isset($response[$responseField]) === true)
        {
            $attributes[$field] = $response[$responseField];
        }
        else
        {
            $attributes[$field] = null;

            if ($this->approval === false)
            {
                // Random fields are often missing in FirstData responses
                // in cases of auth being declined. Raise warning, but chill.
                $traceLevel = 'warning';
                $message    = $responseField . ' is missing from response.';
            }
            else
            {
                // If a random field is missing in a successful response,
                // then contact FirstData immediately and clear things up.
                $traceLevel = 'error';
                $message    = $responseField . ' is missing from a successful preauth response.';
            }

            $this->trace->$traceLevel(
                TraceCode::GATEWAY_PAYMENT_MISSING_FIELD,
                [
                    'payment_id' => $this->input['payment']['id'],
                    'message'    => $message,
                    'gateway'    => $this->gateway,
                ]
            );
        }
    }

    protected function setRefundId(array & $attributes, array $input)
    {
        $attributes[Entity::REFUND_ID] = $input['id'];
    }

    protected function setErrorMessageIfNeeded(array & $attributes)
    {
        if ($this->approval === false)
        {
            $errorCode = $this->approvalCode->getErrorCode();

            $attributes[Entity::ERROR_MESSAGE] = ErrorCodes::getErrorDesc($errorCode);
            $attributes[Entity::STATUS]        = Status::FAILED;

            if ($errorCode === ErrorCodes::getTimeoutCode())
            {
                $attributes[Entity::RECEIVED] = false;
            }
        }
    }

    protected function sendVerifyRequest(Base\Verify $verify)
    {
        $input = $verify->input;

        $requestContent = $this->getVerifyRequestContentArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $requestContent);

        $response = $this->postSoapRequest($requestContent, ApiRequestFields::ACTION_REQUEST);

        $ipgApiActionResponse = $this->parseVerifyResponse($input, $response);

        $verify->setVerifyResponseContent($ipgApiActionResponse);
    }

    protected function sendPaymentVerifyRequest(Base\Verify $verify)
    {
        $this->sendVerifyRequest($verify);
    }

    protected function verifyPayment(Base\Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $verifyResponse = $verify->verifyResponseContent;

        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verifyAuthResponse = null;

        if ($verifyResponse === null)
        {
            // Verify request failed, as FirstData API returned successfully flag set to false
            // This is probably because the payment request timed out, or some other unknown
            // reason. Either way, this is equivalent to gateway success being false.
            $verify->gatewaySuccess = false;

            $verifyContent = [
                Entity::TDATE               => null,
                Entity::GATEWAY_PAYMENT_ID  => null,
                Entity::STATUS              => Status::FAILED,
                Entity::AUTH_CODE           => null
            ];
        }
        else
        {
            $this->s2sFlowFlag = $input['merchant']->isFeatureEnabled(Feature\Constants::FIRST_DATA_S2S_FLOW);

            foreach ($verifyResponse->children('a1', true) as $transactionValue)
            {
                if ($this->isS2sFlowSupported($input) === true)
                {
                    // in the new flow all the xml elements will contain submission component as API, so
                    // checking on the basis of transaction type
                    $trType   = 'TransactionType';
                }
                else
                {
                    $trType   = 'SubmissionComponent';
                }

                $authType = (string) $transactionValue->children('a1', true)->$trType;

                if ($this->isRelevantType($authType) !== true)
                {
                    continue;
                }

                $type = (string) $transactionValue->children('v1', true)->CreditCardTxType->Type;

                // Verify response contains separate states for all transactions, possibly multiple for
                // refund/capture.
                // We're only interested in one transaction state, so loop to that one, and check status.
                if ($this->isRelevantVerifyType($type) === true)
                {
                    // This shouldn't be happening, but sometimes FirstData is returning two separate
                    // preauth transactions in a single verify response. In these cases, the second
                    // preauth is usually declined due to the order existing already in an unexpected
                    // state. So we avoid the second transaction, and break after finding the first.
                    $verifyAuthResponse = $transactionValue;

                    break;
                }
            }

            if ($verifyAuthResponse === null)
            {
                throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
            }

            // A example of the verify response structure can be found
            // in the verifyResponseWrapper method of SoapWrapper class.
            //
            // As tdate, order_ID and state are structed under different
            // namespaces, their parsing logic is also distinct.
            $verifyContent = [
                Entity::TDATE               => (string) $verifyAuthResponse->children('v1', true)
                                                                           ->TransactionDetails->TDate,
                Entity::GATEWAY_PAYMENT_ID  => (string) $verifyAuthResponse->children('v1', true)
                                                                           ->TransactionDetails->OrderId,
                Entity::STATUS              => (string) $verifyAuthResponse->children('a1', true)
                                                                           ->TransactionState,
                Entity::AUTH_CODE           => (string) $verifyAuthResponse->children('ipgapi', true)
                                                                           ->IPGApiOrderResponse
                                                                           ->ProcessorApprovalCode,

                Entity::APPROVAL_CODE       => (string) $verifyAuthResponse->children('ipgapi', true)
                                                                           ->IPGApiOrderResponse
                                                                           ->ApprovalCode,
            ];

            if ($this->shouldUpdatePaymentInternalErrorCode($input['payment']) === true)
            {
                $approvalCode = (string) $verifyAuthResponse->children('ipgapi', true)->IPGApiOrderResponse
                                                                                      ->ApprovalCode;

                $this->setApproval($approvalCode);

                // there could be cases when payment is successful at gateway end and not successful at our end
                // also there could be case where it has failed at both the ends, in both the cases we would want to update the
                // payment internal error code with actual error code returned by the gateway. Only in cases where its a happy
                // flow, meaning payment successful at both the ends, We will not update the internal errorcode.

                if ($this->approvalCode->isSuccess() !== true)
                {
                    $errorCode = $this->approvalCode->getErrorCode();

                    $internalErrorCode = ErrorCodes::getMappedCode($errorCode);

                    $exception = new Exception\GatewayErrorException($internalErrorCode);

                    $error = $exception->getError();

                    $verify->error = $error->getAttributes();

                    $verifyContent[Entity::APPROVAL_CODE] = $this->approvalCode->getFormattedCode();
                }
            }

            $verify->gatewaySuccess = (in_array($verifyContent[Entity::STATUS],
                                                Status::SUCCESSFUL_AUTH_STATES,
                                                true) === true);
        }

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input['payment']);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->payment = $this->saveVerifyContent($gatewayPayment, $verifyContent);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        // Verify Response is actually a SOAP Object, and AuthorizeFailed
        // expects it to be an array. This avoids an error being thrown
        // during failed->auth process.
        $verify->setVerifyResponseContent([]);
    }

    protected function shouldUpdatePaymentInternalErrorCode($payment)
    {
        return in_array($payment['internal_error_code'], $this->verifyInternalErrorCodes, true);
    }

    protected function isRelevantVerifyType(string $type)
    {
        // Verify response components contain a CreditCardTxType field,
        // that tells us if the corresponding component is significant.
        //
        // For an ordinary payment, we look for the preauth component.
        // For purchase transaction, we look for the sale component.
        // For second recurring payments, we look for the periodic component.
        //
        // More than one of these cannot appear in the same verify response.
        // So we simply loop through components and look for any one of them.
        $significantTypes = [
            TxnType::AUTH,
            TxnType::SALE,
            TxnType::PERIODIC,
        ];

        return (in_array($type, $significantTypes, true) === true);
    }

    protected function isRelevantType(string $type)
    {
        // Verify response components contain a component field,
        // that tells us if the corresponding component is significant.
        //
        // For an ordinary payment, we look for the preauth component.
        // For purchase transaction, we look for the sale component.
        // For existing connect flow, we look for CONNECT component.
        //
        // More than one of these cannot appear in the same verify response.
        // So we simply loop through components and look for any one of them.
        $significantTypes = [
            self::PRE_AUTH_TRANSACTION_TYPE,
            self::SALE_TRANSACTION_TYPE,
            Component::CONNECT,
        ];

        return (in_array($type, $significantTypes, true) === true);
    }

    protected function getVerifyApiStatus(Entity $gatewayPayment, array $payment)
    {
        if (($payment['status'] === 'failed') or
            ($payment['status'] === 'created'))
        {
            $apiStatus = false;

            if ($gatewayPayment['status'] === Status::AUTHORIZED)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'payment_id'                => $payment['id'],
                        'api_payment_status'        => $payment['status'],
                        'gateway_payment_status'    => $gatewayPayment['status'],
                    ]);
            }
        }
        else
        {
            $apiStatus = true;

            if (($gatewayPayment['status'] !== Status::AUTHORIZED) and
                ($gatewayPayment['status'] !== Status::CAPTURED))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'payment_id'                => $payment['id'],
                        'api_payment_status'        => $payment['status'],
                        'gateway_payment_status'    => $gatewayPayment['status'],
                    ]);
            }
        }

        return $apiStatus;
    }

    protected function saveVerifyContent(Entity $gatewayPayment, array $verifyContent)
    {
        $gatewayPayment->fill($verifyContent);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function postSoapRequest(array $content, string $requestType)
    {
        $xmlRequest = $this->arrayToXml($content);

        $content = SoapWrapper::defaultWrapper($xmlRequest, $requestType);

        $options = $this->getRequestOptions();

        $request = $this->getStandardRequestArray($content, 'post', $options);

        $this->traceSoapRequest($request);

        try
        {
            $response = $this->sendGatewayRequest($request);
        }
        catch (Exception\GatewayErrorException $e)
        {
            $this->traceAndHandleRequestErrorIfApplicable($e);

            if ($this->action === Action::AUTHENTICATE)
            {
                $e->markSafeRetryTrue();
            }

            throw $e;
        }

        $this->trace->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'body'    => $response->body,
                'headers' => $response->headers,
                'code'    => $response->status_code,
            ]
        );

        if (empty($response->body) === true)
        {
            $ex = new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);

            if ($this->action === Action::AUTHENTICATE)
            {
                $ex->markSafeRetryTrue();
            }

            throw $ex;
        }

        $xml = simplexml_load_string(trim($response->body));

        return $xml;
    }

    protected function decideStepAfterEnroll(SimpleXMLElement $xml, array $input)
    {
        $response = $this->parseXmlAndReturnArray(trim($xml->asXML()));

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_ENROLL_RESPONSE);

        if (isset($response[ApiResponseFields::SOAP_ENV_BODY]
            [ApiResponseFields::IPGAPI_ORDER_RESPONSE]) === true)
        {
            $responseBody = $response[ApiResponseFields::SOAP_ENV_BODY][ApiResponseFields::IPGAPI_ORDER_RESPONSE];

            if ($responseBody[ApiResponseFields::IPGAPI_APPROVAL_CODE] === Status::WAITING_3DS_IN_ENROLL)
            {
                $this->setApproval($responseBody[ApiResponseFields::IPGAPI_APPROVAL_CODE]);

                $enrollAttributes = $this->getEnrollAttributes($responseBody, $input);

                $gatewayPayment = $this->createGatewayPaymentEntity($enrollAttributes, $input);

                $authenticateRequest = $this->getAcsRequest($responseBody, $input);

                return $authenticateRequest;
            }
        }

        // This can be the case if the card is directly authorized ie: not enrolled card. In this case we just
        // save the response. In cases where firstdata will send an approval code that is not Y,
        // we will throw an exception and mark payment failed. The below method does that.
        $this->processAuthorizeResponse($response);
    }

    protected function getEnrollAttributes(array $responseArray, array $input)
    {
        $attributes = [
            Entity::APPROVAL_CODE           => $this->approvalCode->getFormattedCode(),
            Entity::GATEWAY_TRANSACTION_ID  => $responseArray[ApiResponseFields::IPGAPI_IPG_TRANSACTION_ID],
            Entity::TDATE                   => $responseArray[ApiResponseFields::IPGAPI_TDATE],
            Entity::GATEWAY_PAYMENT_ID      => $responseArray[ApiResponseFields::IPGAPI_ORDER_ID],
            Entity::RECEIVED                => true,
            Entity::AMOUNT                  => $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT],
            Entity::CURRENCY                => Currency::getIsoCode(
                                                $input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY]),
        ];

        return $attributes;
    }

    protected function getAcsRequest(array $response, array $input)
    {
        $paramArray = $response[ApiResponseFields::IPGAPI_SECURE_3D_RESPONSE]
                      [ApiResponseFields::V1_VERIFICATION_REDIRECT_RESPONSE]
                      [ApiResponseFields::V1_SECURE_3D_VERIFICATION_RESPONSE];

        $authorizeRequest = $this->getFieldsForFormSubmitToBankAcs($input, $paramArray);

        return $authorizeRequest;
    }

    protected function getFieldsForFormSubmitToBankAcs(array $input, array $parameterArray)
    {
        $content = [
            'TermUrl' => $input['callbackUrl'],
            'MD'      => $parameterArray[ApiResponseFields::V1_MD],
            'PaReq'   => $parameterArray[ApiResponseFields::V1_PA_REQ],
        ];

        $request = [
            'url'     => $parameterArray[ApiResponseFields::V1_ACS_URL],
            'method'  => 'post',
            'content' => $content
        ];

        return $request;
    }

    /**
     * Parses response to Capture and Refund requests and converts xml response to an associative array
     * @param  $xml Response received
     * @return $body Associative array containing response
     */
    protected function parseOrderResponse(SimpleXMLElement $xml)
    {
        $this->trace->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'raw_xml_response' => $xml->asXml()
            ]
        );

        $soapEnvBody = $xml->children('SOAP-ENV', true)->Body;

        if ($soapEnvBody->Fault->count() > 0)
        {
            $ipgApiOrderResponse = $soapEnvBody->Fault->children()
                                                ->detail->children('ipgapi', true)
                                                ->IPGApiOrderResponse;
        }
        else
        {
            $ipgApiOrderResponse = $soapEnvBody->children('ipgapi', true);
        }

        $xmlBody = $ipgApiOrderResponse->children('ipgapi', true);

        $body = json_decode(json_encode($xmlBody), true);

        return $body;
    }

    /**
     * Parses response to Verify request and converts xml response to an associative array
     * @param  $xml Response received
     * @return $ipgApiActionResponse
     */
    protected function parseVerifyResponse(array $input, SimpleXMLElement $xml)
    {
        $ipgApiActionResponse = $xml->children('SOAP-ENV', true)->Body->children('ipgapi', true);

        $successful = (string) $ipgApiActionResponse->IPGApiActionResponse->successfully;

        if ($successful === 'false')
        {
            $this->trace->warning(
                TraceCode::PAYMENT_VERIFY_FAILED,
                [
                    'payment_id' => $input['payment']['id'],
                    'message'    => 'Verification failed.',
                    'gateway'    => $this->gateway,
                ]
            );

            return null;
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $ipgApiActionResponse->asXML()
            ]
        );

        return $ipgApiActionResponse;
    }

    protected function getTraceCode()
    {
        return self::TRACE_CODE_MAPPING[$this->action];
    }

    protected function getRelativeUrl($component)
    {
        // For the new s2s flow, a API URL will be picked and not the CONNECT  URL for Firstdata
        // To support both the flows, we are using s2sFlowFlag, whose value will depend on card network,
        // whether merchant has s2s feature enabled and whether it is a recurring payment.
        if (($this->s2sFlowFlag === true) and
            (($this->action === Action::AUTHENTICATE) or ($this->action === Action::AUTHORIZE)))
        {
            $component = Component::API;
        }
        else
        {
            $component = Component::ACTION_MAPPING[$this->action];
        }

        $ns = $this->getGatewayNamespace();

        return constant($ns . '\Url::' . $component);
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $options = [])
    {
        $request = parent::getStandardRequestArray($content, $method);
        $request['options'] = $options;

        return $request;
    }

    protected function createGatewayPaymentEntity(array $content, array $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->fill($content);

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setCapsPaymentId(strtoupper($input['payment'][Payment\Entity::ID]));

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    // This is a SHA hash of the following fields :
    // storename + txndatetime + chargetotal + currency + sharedsecret.
    protected function getRequestHash(string $txnDateTime, float $chargeTotal, string $currencyCode)
    {
        $storeId = $this->getStoreId();

        $sharedSecret = $this->getSecret();

        $stringToHash = $storeId . $txnDateTime . $chargeTotal . $currencyCode . $sharedSecret;

        $hash = hash(HashAlgo::SHA1, bin2hex($stringToHash));

        return $hash;
    }

    protected function getStringToHash($content, $glue = '')
    {
        $approvalCode   = $content[ConnectResponseFields::APPROVAL_CODE] ?? null;

        $txnDateTime    = $content[ConnectResponseFields::TXN_DATE_TIME];

        $chargeTotal    = $content[ConnectResponseFields::CHARGE_TOTAL];

        $currencyCode   = $content[ConnectResponseFields::CURRENCY];

        $storeId = $this->getStoreId();

        $sharedSecret = $this->getSecret();

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currencyCode . $txnDateTime . $storeId;

        return $stringToHash;
    }

    protected function getHashOfString($str)
    {
        return hash(HashAlgo::SHA1, bin2hex($str));
    }

    protected function getPreAuthRequestContentArray(array $input)
    {
        $createdAt = $input['payment'][Payment\Entity::CREATED_AT];

        $dateTime = Carbon::createFromTimestamp($createdAt, Timezone::IST);

        $txnDateTime = $dateTime->format(Codes::DATE_TIME_FORMAT);

        $chargeTotal = $input['payment'][Payment\Entity::AMOUNT] / 100;

        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $networkCode = $input['card'][Card\Entity::NETWORK_CODE];

        $requestHash = $this->getRequestHash($txnDateTime, $chargeTotal, $currencyCode);

        $txnType = $this->getTransactionType($input);

        $content = [
            ConnectRequestFields::TIME_ZONE                 => Timezone::IST,
            ConnectRequestFields::TXN_DATE_TIME             => $txnDateTime,
            ConnectRequestFields::HASH_ALGORITHM            => strtoupper(HashAlgo::SHA1),
            ConnectRequestFields::HASH                      => $requestHash,
            ConnectRequestFields::STORE_NAME                => $this->getStoreId(),
            ConnectRequestFields::MODE                      => PaymentMode::PAYONLY,
            ConnectRequestFields::CHARGE_TOTAL              => $chargeTotal,
            ConnectRequestFields::CURRENCY                  => $currencyCode,
            ConnectRequestFields::ORDER_ID                  => $input['payment'][Payment\Entity::ID],
            ConnectRequestFields::INVOICE_NUMBER            => $input['payment'][Payment\Entity::ID],
            ConnectRequestFields::MERCHANT_TXN_ID           => $input['payment'][Payment\Entity::ID],
            // Card Entity type field is not reliable, and not mandatory
            // ConnectRequestFields::CARD_FUNCTION             => $input['card'][Card\Entity::TYPE],
            ConnectRequestFields::COMMENTS                  => '',
            ConnectRequestFields::DYNAMIC_MERCHANT_NAME     => $this->getDynamicMerchantName($input['merchant']),
            ConnectRequestFields::LANGUAGE                  => Codes::ENGLISH_UK_LANG_CODE_CONNECT,
            ConnectRequestFields::CARD_NUMBER               => $input['card'][Card\Entity::NUMBER],
            ConnectRequestFields::NAME                      => $this->getFormattedCardName($input['card']),
            ConnectRequestFields::EXP_MONTH                 => $input['card'][Card\Entity::EXPIRY_MONTH],
            ConnectRequestFields::EXP_YEAR                  => $input['card'][Card\Entity::EXPIRY_YEAR],
            ConnectRequestFields::CVV                       => $input['card'][Card\Entity::CVV],
            ConnectRequestFields::RESPONSE_SUCCESS_URL      => $input['callbackUrl'],
            ConnectRequestFields::RESPONSE_FAIL_URL         => $input['callbackUrl'],
            ConnectRequestFields::TXN_TYPE                  => $txnType,
            ConnectRequestFields::PAYMENT_METHOD            => PaymentMethod::METHOD_MAP[$networkCode],
        ];

        if ($this->isFirstRecurringPayment($input) === true)
        {
            $content[ConnectRequestFields::TOKEN] = $input['token']->getId();
        }

        return $content;
    }

    protected function isFirstRecurringPayment(array $input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['terminal']->is3DSRecurring() === true))
        {
            return true;
        }

        return false;
    }

    protected function getRequestOptions()
    {
        $options['auth'] = $this->getCredentials();

        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlSslOpts']);

        $options['hooks'] = $hooks;

        return $options;
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLCERTTYPE, strtoupper(self::CERTIFICATE_FORMAT_P12));

        curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $this->getClientCertificatePassword());

        //
        // Not required, as this server cert is already available in that provided by the OS
        // TODO: Remove this whole line
        //
        // curl_setopt($curl, CURLOPT_CAINFO, $this->getServerCertificate());

        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
    }

    protected function getVerifyRequestContentArray(array $input)
    {
        switch ($this->action)
        {
            case Action::VERIFY:
            case Action::VERIFY_REFUND:
                $reference = [
                    ApiRequestFields::A1_INQUIRY_ORDER => [
                        ApiRequestFields::A1_ORDER_ID => $input['payment']['id'],
                        ApiRequestFields::A1_STORE_ID => $this->getStoreId(),
                    ],
                ];
                break;
            case Action::VERIFY_REVERSE:
                $reference = [
                    ApiRequestFields::A1_INQUIRY_TRANSACTION => [
                        ApiRequestFields::A1_STORE_ID        => $this->getStoreId(),
                        ApiRequestFields::A1_MERCHANT_TXN_ID => $input['payment']['id'],
                    ],
                ];
        }

        $request[ApiRequestFields::A1_ACTION] = $reference;

        return $request;
    }

    protected function getPurchaseRequestArray(array $input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::SALE;

        $body[ApiRequestFields::V1_RECURRING_TYPE] = Codes::STANDING_INSTRUCTION;

        $this->setPaymentRequestArray($body, $input, TxnType::SALE);

        // Sending merchant_txn_id is not strictly necessary. We use the order id
        // for refund and verification of purchase/sale payments, so a separate
        // reference id here is not required. However, keeping it here for future use.
        $body[ApiRequestFields::V1_TRANSACTION_DETAILS] = [
            ApiRequestFields::V1_ORDER_ID              => $input['payment']['id'],
            ApiRequestFields::V1_MERCHANT_TXN_ID       => $input['payment']['id'],
            ApiRequestFields::V1_DYNAMIC_MERCHANT_NAME => $this->getDynamicMerchantName($input['merchant']),
        ];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function prepareNotEnrolledAuthorizeRequest(array $input)
    {
        $txnType = $this->getTransactionType($input);

        $cardMonth = str_pad($input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH],
            2, '0', STR_PAD_LEFT);

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE] = [
            ApiRequestFields::V1_STORE_ID   => $this->getStoreId(),
            ApiRequestFields::V1_TYPE       => $txnType,
        ];

        $body[ApiRequestFields::V1_CREDIT_CARD_DATA] = [
            ApiRequestFields::V1_CARD_NUMBER    => $input['card']['number'],
            ApiRequestFields::V1_EXPIRY_MONTH   => $cardMonth,
            ApiRequestFields::V1_EXPIRY_YEAR    => substr($input['card']['expiry_year'], -2),
            ApiRequestFields::V1_CARD_CODE_VALUE => $input[Constants\Entity::CARD][Card\Entity::CVV],
        ];

        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $amount = $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT] / 100;
        $amount = number_format($amount, 2, '.', '');

        $body[ApiRequestFields::V1_PAYMENT] = [
            ApiRequestFields::V1_CHARGE_TOTAL => $amount,
            ApiRequestFields::V1_CURRENCY     => $currencyCode,
        ];

        // Sending merchant_txn_id is not strictly necessary. We use the order id
        // for refund and verification of purchase/sale payments, so a separate
        // reference id here is not required. However, keeping it here for future use.
        $body[ApiRequestFields::V1_TRANSACTION_DETAILS] = [
            ApiRequestFields::V1_ORDER_ID              => $input['payment']['id'],
            ApiRequestFields::V1_TRANSACTION_ORIGIN    => 'ECI',
        ];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getPurchaseRequestArrayWithCard(array $input)
    {
        $cardMonth = str_pad($input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH],
            2, '0', STR_PAD_LEFT);

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE] = [
            ApiRequestFields::V1_STORE_ID   => $this->getStoreId(),
            ApiRequestFields::V1_TYPE       => TxnType::SALE,
        ];

        $body[ApiRequestFields::V1_CREDIT_CARD_DATA] = [
            ApiRequestFields::V1_CARD_NUMBER    => $input['card']['number'],
            ApiRequestFields::V1_EXPIRY_MONTH   => $cardMonth,
            ApiRequestFields::V1_EXPIRY_YEAR    => substr($input['card']['expiry_year'],-2),
        ];

        $body[ApiRequestFields::V1_RECURRING_TYPE] = Codes::STANDING_INSTRUCTION;

        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $amountEntity = TxnType::$amountEntity[TxnType::SALE];

        $body[ApiRequestFields::V1_PAYMENT] = [
            ApiRequestFields::V1_CHARGE_TOTAL => $this->getFormattedAmount($input, $amountEntity),
            ApiRequestFields::V1_CURRENCY     => $currencyCode,
        ];

        // Sending merchant_txn_id is not strictly necessary. We use the order id
        // for refund and verification of purchase/sale payments, so a separate
        // reference id here is not required. However, keeping it here for future use.
        $body[ApiRequestFields::V1_TRANSACTION_DETAILS] = [
            ApiRequestFields::V1_ORDER_ID              => $input['payment']['id'],
            ApiRequestFields::V1_TRANSACTION_ORIGIN    => 'ECI',
        ];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getCaptureRequestArray(array $input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::CAPTURE;

        $this->setPaymentRequestArray($body, $input, TxnType::CAPTURE);

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS][ApiRequestFields::V1_ORDER_ID] = $input['payment']['id'];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getRefundRequestArray(array $input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::REFUND;

        $this->setPaymentRequestArray($body, $input, TxnType::REFUND);

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS] = [
            ApiRequestFields::V1_ORDER_ID        => $input['payment']['id'],
            ApiRequestFields::V1_MERCHANT_TXN_ID => $input['refund']['id'],
        ];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function getReverseRequestArray(array $input)
    {
        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_STORE_ID] = $this->getStoreId();

        $body[ApiRequestFields::V1_CREDIT_CARD_TX_TYPE][ApiRequestFields::V1_TYPE] = TxnType::REVERSE;

        $tdate = $this->getTdateForGatewayPaymentToBeReversed($input);

        $body[ApiRequestFields::V1_TRANSACTION_DETAILS] = [
            ApiRequestFields::V1_ORDER_ID => $input['payment']['id'],
            ApiRequestFields::V1_TDATE    => $tdate,
        ];

        $request[ApiRequestFields::V1_TRANSACTION] = $body;

        return $request;
    }

    protected function setPaymentRequestArray(array & $body, array $input, string $txnType)
    {
        $currency = $input['payment'][Payment\Entity::CURRENCY];

        $currencyCode = Currency::ISO_NUMERIC_CODES[$currency];

        $amountEntity = TxnType::$amountEntity[$txnType];

        if (($this->isSecondRecurringPayment($input) === true) and
            ($txnType === TxnType::SALE))
        {
            $body[ApiRequestFields::V1_PAYMENT] = [
                ApiRequestFields::V1_HOSTED_DATA_ID  => $input['token']->getId(),
                ApiRequestFields::V1_HOSTED_STORE_ID => $this->getHostedDataStoreId(),
            ];
        }

        $body[ApiRequestFields::V1_PAYMENT]
            [ApiRequestFields::V1_CHARGE_TOTAL] = $this->getFormattedAmount($input, $amountEntity);

        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CURRENCY] = $currencyCode;
    }

    protected function getFormattedAmount(array $input, string $amountEntity)
    {
        $amount = $input[$amountEntity]['amount'] / 100;

        // The amount should be in the format like 100.00, or 1500.00
        $amount = number_format($amount, 2, '.', '');

        return $amount;
    }

    /**
     * Fetches tdate of original gatewayPayment that is to be reversed
     *
     * Reverse request needs tdate attribute that exists in the authorize
     * action entity (or in purchase for second recurring payments)
     *
     * @param  array  $input gateway input
     * @return tdate of gatewayPayment
     */
    protected function getTdateForGatewayPaymentToBeReversed(array $input)
    {
        $requiredAction = Action::AUTHORIZE;

        if ($this->isSecondRecurringPayment($input) === true)
        {
            $requiredAction = Action::PURCHASE;
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                            $input['payment'][Payment\Entity::ID],
                                            $requiredAction);

        return $gatewayPayment[Entity::TDATE];
    }

    protected function arrayToXml(array $array, string $wrap = null)
    {
        // set initial value for XML string
        $xml = '';
        foreach ($array as $key => $value)
        {
            if (is_array($value) === true)
            {
                $xml .= $this->arrayToXml($value, $key);
            }
            else
            {
                $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
            }
        }

        // wrap XML with $wrap TAG
        if ($wrap !== null)
        {
            $xml = "<$wrap>".$xml."</$wrap>";
        }

        return $xml;
    }

    protected function traceSoapRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->removeCardDetails($request['content']);

        $this->trace->info(
            TraceCode::GATEWAY_SOAP_REQUEST,
            ['gateway_soap_request' => $request]);
    }

    protected function removeCardDetails(string &$content)
    {
        $patternAndReplacement = [
            '#<v1:CardNumber>[0-9]{12,19}</v1:CardNumber>#'  => '<v1:CardNumber>redacted</v1:CardNumber>',
            '#<v1:CardCodeValue>[0-9]{3}</v1:CardCodeValue>#' => '<v1:CardCodeValue>redacted</v1:CardCodeValue>'
        ];

        foreach ($patternAndReplacement as $pattern => $replacement)
        {
            $content = preg_replace($pattern, $replacement, $content); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval
        }
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->scrubCardInfo($request['content']['v1:Transaction']['v1:CreditCardData']);
        $this->scrubCardInfo($request['content']);

        parent::traceGatewayPaymentRequest($request, $input, $traceCode);
    }

    protected function traceGatewayCallback(array $gatewayCallback)
    {
        $this->scrubCardInfo($gatewayCallback);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $gatewayCallback
        );
    }

    protected function traceGatewayRequest(
        array $request,
        array $input,
        $traceCode = TraceCode::GATEWAY_ENROLL_REQUEST)
    {
        $this->scrubCardInfoForEnroll($request['v1:Transaction']['v1:CreditCardData']);

        parent::traceGatewayPaymentRequest($request, $input, $traceCode);
    }

    protected function scrubCardInfo(& $content)
    {
        $scrubFields = [
            ConnectRequestFields::CARD_NUMBER,
            ConnectRequestFields::CVV,
            ConnectRequestFields::EXP_MONTH,
            ConnectRequestFields::EXP_YEAR
        ];

        foreach ($scrubFields as $scrubField)
        {
            unset($content[$scrubField]);
        }
    }

    protected function scrubCardInfoForEnroll(array & $content)
    {
        $scrubFields = [
            ApiRequestFields::V1_CARD_NUMBER,
            ApiRequestFields::V1_CARD_CODE_VALUE,
        ];

        foreach ($scrubFields as $scrubField)
        {
            unset($content[$scrubField]);
        }
    }

    // FirstData creds
    //
    // Store ID              => Terminal attr (GATEWAY_MERCHANT_ID)
    // Shared Secret         => env(FIRST_DATA_LIVE_HASH_SECRET)
    // User ID               => env(FIRST_DATA_LIVE_USER_ID)
    // Password              => env(FIRST_DATA_LIVE_PASSWORD)
    // Client Cert           => env(FIRST_DATA_LIVE_CLIENT_CERTIFICATE)
    // Client Cert Password  => env(FIRST_DATA_LIVE_CLIENT_CERTIFICATE_PASSWORD)

    public function getStoreId()
    {
        $storeId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $storeId = $this->config['test_store_id'];
        }

        return $storeId;
    }

    /**
     * Non-3DS recurring payments require the store id that the original 3DS
     * payment was made on. This was earlier retrieved through the token used,
     * but is now simply stored as an extra attribute in terminal entity
     *
     * @return string hostedDataStoreId
     * @throws Exception\LogicException
     */
    public function getHostedDataStoreId()
    {
        $hostedDataStoreId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];

        if ($this->isOldStoreId() === true)
        {
            throw new Exception\LogicException(
                'Gateway Merchant ID2 has different meaning for old store ids.',
                null,
                [
                    'payment_id'           => $this->input['payment']['id'],
                    'gateway_merchant_id'  => $this->getStoreId,
                    'gateway_merchant_id2' => $hostedDataStoreId,
                ]);
        }

        return $hostedDataStoreId;
    }

    protected function getLiveSecret()
    {
        $liveSecret = $this->config['live_hash_secret'];

        if ($this->isOldStoreId() === true)
        {
            $liveSecret = $this->input['terminal']['gateway_secure_secret'];
        }

        return $liveSecret;
    }

    protected function getCredentials()
    {
        $username = $this->config['live_user_id'];
        $password = $this->config['live_password'];

        if ($this->isOldStoreId() === true)
        {
            $username = $this->getUsernameForOldStoreId();
            $password = $this->terminal[Terminal\Entity::GATEWAY_ACCESS_CODE];
        }

        if ($this->mode === Mode::TEST)
        {
            $username = $this->config['test_user_id'];
            $password = $this->config['test_password'];
        }

        return [$username, $password];
    }

    /**
     * Old terminal had a gateway_merchant_id2 that was just f(gateway_merchant_id)
     *
     * @return array credentials
     */
    protected function getUsernameForOldStoreId()
    {
        $gatewayMerchantId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        $username = 'WS'.$gatewayMerchantId.'._.1';

        return $username;
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    protected function getServerCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        return $gatewayCertPath . '/' . $this->config['server_certificate'];
    }

    public function getClientCertificateName()
    {
        $certName = $this->config['client_certificate'];

        if ($this->isOldStoreId() === true)
        {
            $certName = $this->getStoreId() . '.' . self::CERTIFICATE_FORMAT_P12;
        }

        return $certName;
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            $clientCertFile = fopen($clientCertPath, 'w');

            $encodedCert = $this->config['live_client_certificate'];

            if ($this->isOldStoreId() === true)
            {
                $encodedCert = $this->terminal[Terminal\Entity::GATEWAY_CLIENT_CERTIFICATE];
            }

            if ($this->mode === Mode::TEST)
            {
                $encodedCert = $this->config['test_client_certificate'];
            }

            $key = base64_decode($encodedCert);

            fwrite($clientCertFile, $key);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    protected function getClientCertificatePassword()
    {
        $password = $this->config['live_client_certificate_password'];

        if ($this->isOldStoreId() === true)
        {
            $password = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];
        }

        if ($this->mode === Mode::TEST)
        {
            $password = $this->config['test_client_certificate_password'];
        }

        return $password;
    }

    /**
     * The oldest FirstData terminals, had several values stored differently
     *
     * @return boolean
     */
    protected function isOldStoreId()
    {
        $gatewayMerchantId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        return (in_array($gatewayMerchantId, self::OLD_STORE_IDS, true) === true);
    }

    /**
     * FirstData has a minimum limit on card name(bname)
     *
     * @param array $card
     * @return string
     */
    protected function getFormattedCardName(array $card): string
    {
        $name = trim($card[Card\Entity::NAME]);

        if (strlen($name) < self::MINIMUM_CARD_NAME_LENGTH)
        {
            $name = str_pad($name, self::MINIMUM_CARD_NAME_LENGTH, self::CARD_NAME_PADDING);
        }

        return $name;
    }

    protected function isS2sFlow(array $input)
    {
        if ((isset($input[ApiResponseFields::MD]) === true) and
            (isset($input[ApiResponseFields::PA_RES]) === true))
        {
            return true;
        }

        return false;
    }

    protected function authorizeNotEnrolled(array $input, $authorizeRequest)
    {
        $this->traceGatewayRequest($authorizeRequest, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->postSoapRequest($authorizeRequest, ApiRequestFields::ORDER_REQUEST);

        $responseArray = $this->parseOrderResponse($response);

        $this->traceGatewayPaymentResponse($responseArray, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->processAuthorizeResponse($responseArray);
    }

    protected function authorizeEnrolled(array $input, $gatewayPayment, $authorizeRequest)
    {
        $this->traceGatewayRequest($authorizeRequest, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->postSoapRequest($authorizeRequest, ApiRequestFields::ORDER_REQUEST);

        $responseArray = $this->parseXmlAndReturnArray(trim($response->asXML()));

        $this->traceGatewayPaymentResponse($responseArray, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->processAuthorizeResponse($responseArray, $gatewayPayment);
    }

    protected function getAuthorizeRequest(array $input, $gatewayPayment)
    {
        $txnType = $this->getTransactionType($input);

        $this->setCardCvv($input);

        $gatewayInput = $input['gateway'];

        $requestArray = [
            ApiRequestFields::V1_TRANSACTION => [
                ApiRequestFields::V1_CREDIT_CARD_TX_TYPE => [
                    ApiRequestFields::V1_STORE_ID => $this->getStoreId(),
                    ApiRequestFields::V1_TYPE     => $txnType,
                ],
                ApiRequestFields::V1_CREDIT_CARD_DATA => [
                    ApiRequestFields::V1_CARD_CODE_VALUE => $input[Constants\Entity::CARD][Card\Entity::CVV]
                ],
                ApiRequestFields::V1_CREDIT_CARD_3D_SECURE => [
                    ApiRequestFields::V1_SECURE_3D_REQUEST => [
                        ApiRequestFields::V1_SECURE_3D_AUTHENTICATION_REQUEST => [
                            ApiRequestFields::V1_ACS_RESPONSE => [
                                ApiRequestFields::V1_MD     => $gatewayInput[ApiResponseFields::MD],
                                ApiRequestFields::V1_PA_RES => $gatewayInput[ApiResponseFields::PA_RES],
                            ]
                        ],
                    ],
                ],
                ApiRequestFields::V1_TRANSACTION_DETAILS => [
                    ApiRequestFields::V1_IPG_TRANSACTION_ID => $gatewayPayment[Entity::GATEWAY_TRANSACTION_ID],
                    ApiRequestFields::V1_TRANSACTION_ORIGIN => ApiRequestFields::ECI,
                ],
            ]
        ];

        return $requestArray;
    }

    protected function getTransactionType(array $input)
    {
        $networkCode = $input[Constants\Entity::CARD][Card\Entity::NETWORK_CODE];

        $txnType = TxnType::AUTH;

        if ((Payment\Gateway::supportsAuthAndCapture($this->gateway, $networkCode) === false) or
            (($input['card'][Card\Entity::ISSUER] === Card\Issuer::ICIC) and
             ($input['card'][Card\Entity::TYPE] === Card\Type::DEBIT)))
        {
            $txnType = TxnType::SALE;
        }

        $terminalMode = $input['terminal']['mode'];

        if ($terminalMode === Terminal\Mode::PURCHASE)
        {
            $txnType = TxnType::SALE;
        }

        return $txnType;
    }

    protected function parseXmlAndReturnArray($xml)
    {
        $xml = preg_replace('/(<\/?)(\w+-*\w+):([^>]*>)/', '$1$2$3', $xml);

        $formattedXml = simplexml_load_string($xml);

        $responseArray = json_decode(json_encode($formattedXml), true);

        return $responseArray;
    }

    /*
     * This method is responsible to process authorize response we are getting from firstdata.
     * If a status code other that Y(approved) is returned, we throw an exception.
     * Only when we get status Y, we save the payment and mark payment as authorized.
     */
    protected function processAuthorizeResponse(array $response, $gatewayPayment = null)
    {
        $currencyCode = Currency::getIsoCode($this->input['payment']['currency']);
        $content = [];

        // this checks whether firstdata returned a failed xml with fault tag and handles the response accordingly
        if (isset($response[ApiResponseFields::SOAP_ENV_BODY][ApiResponseFields::SOAP_ENV_FAULT]
                    [ApiResponseFields::DETAIL][ApiResponseFields::IPGAPI_ORDER_RESPONSE]) === true)
        {
            $content = $response[ApiResponseFields::SOAP_ENV_BODY][ApiResponseFields::SOAP_ENV_FAULT]
                       [ApiResponseFields::DETAIL][ApiResponseFields::IPGAPI_ORDER_RESPONSE];
        }
        else if (isset($response[ApiResponseFields::SOAP_ENV_BODY][ApiResponseFields::IPGAPI_ORDER_RESPONSE]) === true)
        {
            $content = $response[ApiResponseFields::SOAP_ENV_BODY][ApiResponseFields::IPGAPI_ORDER_RESPONSE];
        }
        else if (isset($response[ApiResponseFields::APPROVAL_CODE]) === true)
        {
            $content = $response;
        }
        else
        {
            // in this case firstdata has returned a failed xml, but the structure is totally different,
            // we will throw the exception as it can be anything random
            $e = new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR);

            $data = [
                'response'    => $response,
                'gateway'     => $this->gateway,
            ];

            $e->setData($data);

            throw $e;
        }

        if (isset($response[ApiResponseFields::APPROVAL_CODE]) === false)
        {
            $this->mockApprovalCodeForS2s($content);

            $this->setApproval($content[ApiResponseFields::IPGAPI_APPROVAL_CODE]);
        }
        else
        {
            $this->setApproval($content[ApiResponseFields::APPROVAL_CODE]);
        }

        $attributes = $this->getS2sCallbackFields($content);

        $attributes[Entity::CURRENCY] = $currencyCode;

        if ($gatewayPayment === null)
        {
            $attributes[Entity::AMOUNT] = $this->input['payment']['amount'];

            $attributes[Entity::GATEWAY_PAYMENT_ID] = $this->input['payment']['id'];

            $gatewayPayment = $this->createGatewayPaymentEntity($attributes, $this->input);
        }
        else
        {
            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        $this->checkApprovalCode($gatewayPayment, $response, $attributes);
    }

    protected function mockApprovalCodeForS2s(array & $input)
    {
        if (empty($input[ApiResponseFields::IPGAPI_APPROVAL_CODE]) === true)
        {
            $mockedApprovalCode = implode(':', ['N', Codes::MOCK_FAIL_APPROVAL_CODE]);

            $input[ApiResponseFields::IPGAPI_APPROVAL_CODE] = $mockedApprovalCode;
        }
    }

    protected function getS2sCallbackFields(array $callbackBody)
    {
        $attributes = [
            Entity::RECEIVED      => true,
            Entity::APPROVAL_CODE => $this->approvalCode->getFormattedCode(),
        ];

        $this->setFieldIfPresent($attributes, Entity::TRANSACTION_RESULT,
            ApiResponseFields::IPGAPI_TRANSACTION_RESULT, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TRANSACTION_ID,
            ApiResponseFields::IPGAPI_IPG_TRANSACTION_ID, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_TERMINAL_ID,
            ApiResponseFields::IPGAPI_TERMINAL_ID, $callbackBody);

        $this->setFieldIfPresent($attributes, Entity::GATEWAY_PAYMENT_ID,
            ApiResponseFields::IPGAPI_ORDER_ID, $callbackBody);

        if ($attributes[Entity::TRANSACTION_RESULT] === Status::APPROVED)
        {
            $attributes[Entity::STATUS] = Status::AUTHORIZED;

            $attributes[Entity::AUTH_CODE] = $this->approvalCode->getAuthCode();

            $attributes[Entity::TDATE] = $callbackBody[ApiResponseFields::IPGAPI_TDATE];
        }

        $this->setErrorMessageIfNeeded($attributes);

        return $attributes;
    }

    public function setS2sFlowFlag($input)
    {
        $this->s2sFlowFlag = true;
    }

    protected function enroll($input)
    {
        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED,
            $input);

        $request = $this->getEnrollRequest($input);

        $this->traceGatewayRequest($request, $input);

        $this->getCardCacheKey($input);

        $response = $this->postSoapRequest($request, ApiRequestFields::ORDER_REQUEST);

        $enrolled = isset($response[ApiResponseFields::SOAP_ENV_BODY][ApiResponseFields::IPGAPI_ORDER_RESPONSE]) ? 'Y' : 'N';

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED,
            $input,
            null,
            [
                'enrolled' => $enrolled
            ]);

        return $response;
    }

    protected function getEnrollRequest(array $input)
    {
        $txnType = $this->getTransactionType($input);

        $cardMonth = str_pad($input[Constants\Entity::CARD][Card\Entity::EXPIRY_MONTH],
                    2, '0', STR_PAD_LEFT);

        $request = [
            ApiRequestFields::V1_TRANSACTION => [
                ApiRequestFields::V1_CREDIT_CARD_TX_TYPE => [
                    ApiRequestFields::V1_STORE_ID => $this->getStoreId(),
                    ApiRequestFields::V1_TYPE     => $txnType,
                ],
                ApiRequestFields::V1_CREDIT_CARD_DATA => [
                    ApiRequestFields::V1_CARD_NUMBER     => $input[Constants\Entity::CARD][Card\Entity::NUMBER],
                    ApiRequestFields::V1_EXPIRY_MONTH    => $cardMonth,
                    ApiRequestFields::V1_EXPIRY_YEAR     => substr($input[Constants\Entity::CARD]
                                                                   [Card\Entity::EXPIRY_YEAR], -2),
                    ApiRequestFields::V1_CARD_CODE_VALUE => $input[Constants\Entity::CARD][Card\Entity::CVV],
                ],
                ApiRequestFields::V1_CREDIT_CARD_3D_SECURE => [
                    ApiRequestFields::V1_AUTHENTICATE_TRANSACTION => true,
                ],
            ],
        ];

        // first data is validating the order of the xml, we are sending them. In case of recurring payments we
        // need to pass HostedDataId before other fields in payment tag
        if ($this->isFirstRecurringPayment($input) === true)
        {
            $request[ApiRequestFields::V1_TRANSACTION][ApiRequestFields::V1_PAYMENT]
            [ApiRequestFields::V1_HOSTED_DATA_ID]   = $input['token']->getId();
        }

        $request[ApiRequestFields::V1_TRANSACTION][ApiRequestFields::V1_PAYMENT]
        [ApiRequestFields::V1_CHARGE_TOTAL]         = $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT] / 100;

        $request[ApiRequestFields::V1_TRANSACTION][ApiRequestFields::V1_PAYMENT]
        [ApiRequestFields::V1_CURRENCY]             = Currency::getIsoCode($input[Constants\Entity::PAYMENT]
                                                        [Payment\Entity::CURRENCY]);

        $request[ApiRequestFields::V1_TRANSACTION][ApiRequestFields::V1_TRANSACTION_DETAILS] = [
                    ApiRequestFields::V1_ORDER_ID => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
                ];

        return $request;
    }

    protected function getCardCacheKey(array $input)
    {
        $cvv = $input['card']['cvv'];

        $key = $this->getCacheKey($input);

        $data = [
            'cvv' => $this->app['encrypter']->encrypt($cvv),
        ];

        // Multiplying by 60 since cache put() expect ttl in seconds
        $this->app['cache']->store($this->secureCacheDriver)->put($key, $data, static::CARD_CACHE_TTL * 60);
    }

    protected function setCardCvv(array & $input)
    {
        //For firstdata we need to send only cvv
        $data = $this->getCardDetailsFromCache($input);

        $input['card']['cvv'] = $this->app['encrypter']->decrypt($data['cvv']);
    }

    protected function isS2sFlowSupported(array $input)
    {
        $cardNetwork = $input[Constants\Entity::CARD][Card\Entity::NETWORK_CODE];

        if ($cardNetwork !== Card\Network::RUPAY)
        {
            return true;
        }

        // updating the s2s flag to false as this flag will be used in select the request url
        // s2s flag true and false point to different urls.
        $this->s2sFlowFlag = false;

        return false;
    }

    /*
     * In case a user cancels payment on ACES page or the user is not authorized for some reason
     * Firstdata is returning 500 http status code. This method ensure payments failed with proper
     * error code
     */
    protected function traceAndHandleRequestErrorIfApplicable($e)
    {
        if ($e instanceof Exception\GatewayErrorException)
        {
            $data = $e->getData();

            if ((isset($data['body']) === true) and
                (isset($data['status_code']) === true))
            {
                $responseBody = $data['body'];

                $responseCode = $data['status_code'];

                if ($responseCode >= 500)
                {
                    $responseArray = $this->parseXmlAndReturnArray($responseBody);

                    $this->traceGatewayPaymentResponse($responseArray, $this->input,
                        TraceCode::GATEWAY_RESPONSE);

                    $this->processAuthorizeResponse($responseArray);
                }
            }
        }
    }

    protected function getPaymentToVerify(Base\Verify $verify)
    {
        $action = Action::AUTHORIZE;

        if ($this->isSecondRecurringPayment($verify->input) === true)
        {
           $action = Action::PURCHASE;
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $verify->input['payment']['id'], $action);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function getActionsToRetry()
    {
        return [Action::AUTHORIZE, Action::CAPTURE];
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
        if ($this->isRoutedThroughCardPayments($input))
        {
            return $this->forceAuthorizeFailedViaCps($input);
        }

        $requiredAction = Action::AUTHORIZE;

        if ($this->isSecondRecurringPayment($input) === true)
        {
            $requiredAction = Action::PURCHASE;
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'],
                                                                      $requiredAction);

        // If it's already authorized on gateway side, there's nothing to do here. We just return back.
        if (($gatewayPayment[Entity::TRANSACTION_RESULT] === Status::APPROVED) and
            ($gatewayPayment[Entity::RECEIVED] === true))
        {
            return true;
        }

        $attributes = [
            Entity::TRANSACTION_RESULT  => Status::APPROVED,
            Entity::AUTH_CODE           => $input['gateway'][Entity::AUTH_CODE],
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;

    }

    /*
     * For CPS payments, we dont have entry in Hitachi table.
     * so push the relevant param in the CPS queue to that
     * CPS service can mark the payment as authorized.
     */
    protected function forceAuthorizeFailedViaCps(array $input)
    {
        // Fetch auth response and check authorize status
        // in CPS gateway entity
        $paymentId = $input['payment']['id'];

        $request = [
            'fields'        => [self::STATUS],
            'payment_ids'   => [$paymentId],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_RECON_QUEUE_CPS_REQUEST,
            $request
        );

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'     => InfoCode::CPS_RESPONSE_AUTHORIZATION_DATA,
                'response'      => $response,
            ]);

        if (empty($response[$paymentId]) === false)
        {
            if ($response[$paymentId][self::STATUS] === "failed")
            {
                // Push to queue in order to update/force auth
                // Note : This push part we can do in async way and
                // just return true here, as there is no failure case ahead.

                $entity = [
                    self::AUTH_CODE           => $input['gateway'][Entity::AUTH_CODE]
                ];

                $attr = [
                    self::PAYMENT_ID          =>  $paymentId,
                    self::ENTITY_TYPE         =>  self::GATEWAY,
                    self::GATEWAY             =>  $entity
                ];

                $queueName = $this->app['config']->get('queue.payment_card_api_reconciliation.' . $this->mode);

                Queue::pushRaw(json_encode($attr), $queueName);

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'info_code' => InfoCode::RECON_CPS_QUEUE_DISPATCH,
                        'message'   => 'Update gateway data in order to Force Authorize payment',
                        'payment_id'=> $paymentId,
                        'queue'     => $queueName,
                        'payload'   => json_encode($attr),
                    ]
                );
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => InfoCode::CPS_PAYMENT_AUTH_DATA_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => \RZP\Reconciliator\RequestProcessor\Base::FIRST_DATA,
                ]);

            return false;
        }

        return true;
    }

    public function isRoutedThroughCardPayments($input): bool
    {
        /**
         * This checks if the current request has to be routed to
         * card payment service or not.
         */
        if ((is_array($input) === true) and
            (isset($input[E::PAYMENT]) === true) and
            ($input[E::PAYMENT][Payment\Entity::CPS_ROUTE] === Payment\Entity::CARD_PAYMENT_SERVICE))
        {
            return true;
        }

        return false;
    }
}
