<?php

namespace RZP\Gateway\FirstData;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Requests_Hooks;
use SimpleXMLElement;

use RZP\Error;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';
    const CERTIFICATE_FORMAT_P12     = 'p12';

    const PROCESSING                 = 'PROCESSING';
    const SERVICES                   = 'SERVICES';

    const CHECKSUM_ATTRIBUTE         = ConnectResponseFields::RESPONSE_HASH;

    protected $gateway = Constants\Entity::FIRST_DATA;

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

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isSecondRecurringPayment($input) === true)
        {
            return $this->secondRecurring($input);
        }

        $requestContent = $this->getPreAuthRequestContentArray($input);

        $authorizeFields = $this->getAuthorizeFields($requestContent);

        $authorizeEntity = $this->createGatewayPaymentEntity($authorizeFields, $input);

        $request = $this->getStandardRequestArray($requestContent);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    protected function secondRecurring(array $input)
    {
        parent::action($input, Action::PURCHASE);

        $requestContent = $this->getPurchaseRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PURCHASE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_PURCHASE_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'response'   => $response
            ]
        );

        $purchaseFields = $this->getPurchaseFields($response, $input['payment']);

        $purchaseEntity = $this->createGatewayPaymentEntity($purchaseFields, $input);

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

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['gateway'][ConnectResponseFields::ORDER_ID],
            Action::AUTHORIZE);

        $this->verifySecureHash($input['gateway']);

        $this->mockApprovalCodeIfNeeded($input['gateway']);

        $this->assertPaymentId($input['payment']['id'], $input['gateway'][ConnectResponseFields::ORDER_ID]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($input['gateway'][ConnectResponseFields::CHARGE_TOTAL], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $attributes = $this->getCallbackFields($input['gateway']);

        $gatewayPayment->fill($attributes);

        $this->runCallbackVerify($input);

        $this->repo->saveOrFail($gatewayPayment);

        $this->checkApprovalCode($gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function runCallbackVerify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        $gatewayPayment = $this->getPaymentToVerify($verify);

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

        $captureFields = $this->getCaptureFields($response, $input['payment']);

        $captureEntity = $this->createGatewayPaymentEntity($captureFields, $input);

        $this->checkApprovalCode($captureEntity);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        // All refunds temporarily blocked, due to FirstData issues
        $this->failRefund();

        $requestContent = $this->getRefundRequestArray($input, TxnType::REFUND);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'response' => $response
            ]
        );

        $refundFields = $this->getRefundFields($response, $input['refund']);

        $refundEntity = $this->createGatewayPaymentEntity($refundFields, $input);

        $this->checkApprovalCode($refundEntity);
    }

    public function reverse(array $input)
    {
        parent::reverse($input);

        // All refunds temporarily blocked, due to FirstData issues
        $this->failRefund();

        $requestContent = $this->getReverseRequestArray($input, TxnType::REVERSE);

        $this->trace->info(TraceCode::GATEWAY_REVERSE_REQUEST, $requestContent);

        $response = $this->getSoapResponse($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_REVERSE_RESPONSE,
            [
                'response' => $response
            ]
        );

        $reverseFields = $this->getReverseFields($response, $input['refund']);

        $reverseEntity = $this->createGatewayPaymentEntity($reverseFields, $input);

        $this->checkApprovalCode($reverseEntity);
    }

    /**
     * Failing all FirstData refunds for now
     * Will be retried later via cron
     * @throws Exception\GatewayErrorException
     */
    protected function failRefund()
    {
        $this->trace->info(
            TraceCode::GATEWAY_FIRST_DATA_REFUND_BLOCKED,
            [
                'payment_id' => $this->input['payment']['id'],
                'refund_id'  => $this->input['refund']['id'],
                'action'     => $this->action,
            ]);

        throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
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

        if ($input['refund']['reverse'] === true)
        {
            parent::action($input, Action::VERIFY_REVERSE);
        }

        $this->validateVerifyRefundIsPossible($input);

        $verify = new Base\Verify($this->gateway, $input);

        $this->sendVerifyRequest($verify);

        $verifyRefundResponse = $verify->verifyResponseContent;

        if ($verifyRefundResponse === null)
        {
            // FirstData is returning an an invalid response, i.e. success flag
            // set to false, implying that the id does not exist on their end
            return false;
        }

        $xmlResponse  = $verifyRefundResponse->children('a1', true)
                                             ->TransactionValues
                                             ->children('ipgapi', true)
                                             ->IPGApiOrderResponse
                                             ->children('ipgapi', true);

        $refundResponse = json_decode(json_encode($xmlResponse), true);

        $refundFields = $this->getRefundFields($refundResponse, $input['refund']);

        $this->updateOrCreateRefundEntity($refundFields, $input);

        $refundGatewayStatus = (string) $verifyRefundResponse->children('a1', true)->TransactionState;

        return in_array($refundGatewayStatus, Status::SUCCESSFUL_REFUND_STATES, true);
    }

    protected function updateOrCreateRefundEntity(array $refundFields, array $input)
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

        // For refunds older than this, verification is not possible.
        throw new Exception\LogicException(
                'Verification is not possible for older refunds.',
                null,
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
            $this->setApproval($gatewayCallback[ConnectResponseFields::APPROVAL_CODE]);

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

        $this->setApproval($mockedApprovalCode);

        $gatewayCallback[ConnectResponseFields::APPROVAL_CODE] = $mockedApprovalCode;
    }

    protected function setApproval(string $approvalCode)
    {
        // Request has failed if the first character
        // of the approval code string isn't 'Y'
        if ($approvalCode[0] === 'Y')
        {
            $this->approval = true;
        }
        else
        {
            $this->approval = false;
        }
    }

    protected function getSoapResponse(array $requestContent)
    {
        $xmlResponse = $this->postSoapRequest($requestContent, ApiRequestFields::ORDER_REQUEST);

        $traceCode = $this->getTraceCode();

        $this->trace->info($traceCode, [$xmlResponse->asXml()]);

        $response = $this->parseOrderResponse($xmlResponse);

        return $response;
    }

    protected function checkApprovalCode(Entity $gatewayEntity)
    {
        if ($this->approval === false)
        {
            $approvalCode = $this->getActualCodeFromApprovalCode($gatewayEntity->getApprovalCode());

            $gatewayErrorDesc = ErrorCodes::getErrorDesc($approvalCode);

            $errorCode = ErrorCodes::getMappedCode($approvalCode);

            // Cryptic error messages that First Data keeps sending us
            $this->checkSpecialCases($approvalCode, $gatewayEntity, $gatewayErrorDesc);

            throw new Exception\GatewayErrorException($errorCode, $approvalCode, $gatewayErrorDesc);
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

    protected function getActualCodeFromApprovalCode(string $approvalCode)
    {
        // Approval Code is sent as a concatenation of the code ('N:224')
        // and the reason ('Timed out') separated by a ':'.
        // Eg. "N:87:Bad Track Data"
        // Break it using the ':' separator.
        $approvalCodeArray = explode(':', $approvalCode);

        // Retrieve only approval code
        $code = implode(array_slice($approvalCodeArray, 0, 2), ':');

        return $code;
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

    protected function getCallbackFields(array $callbackBody)
    {
        $attributes = [
            Entity::RECEIVED                => true,
            Entity::APPROVAL_CODE           => $callbackBody[ConnectResponseFields::APPROVAL_CODE],
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

            $attributes[Entity::AUTH_CODE] = $this->getAuthCodeFromCallback($callbackBody);

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
            Entity::APPROVAL_CODE => $response[ApiResponseFields::APPROVAL_CODE],
            Entity::AMOUNT        => $input['amount'],
            Entity::CURRENCY      => $currencyCode,
            Entity::STATUS        => Status::CAPTURED,
        ];

        $this->setApproval($attributes[Entity::APPROVAL_CODE]);

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
            $approvalCode = $this->getActualCodeFromApprovalCode($attributes[Entity::APPROVAL_CODE]);

            $attributes[Entity::ERROR_MESSAGE] = ErrorCodes::getErrorDesc($approvalCode);
            $attributes[Entity::STATUS]        = Status::FAILED;

            if ($approvalCode === ErrorCodes::getTimeoutCode())
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

        if($verifyResponse === null)
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
            foreach ($verifyResponse->children('a1', true) as $transactionValue)
            {
                // FirstData has several components or services
                // The auth request is sent to the Connect service,
                // so here we're only interested in that one.
                $component = (string) $transactionValue->children('a1', true)->SubmissionComponent;

                if ($component !== Component::CONNECT)
                {
                    continue;
                }

                $type = (string) $transactionValue->children('v1', true)->CreditCardTxType->Type;

                // Verify response contains separate states for all transactions, possibly multiple for refund/capture.
                // We're only interested in one transaction state, so loop to that one, and check status.
                if ($this->isRelevantVerifyType($type) === true)
                {
                    $verifyAuthResponse = $transactionValue;

                    // This shouldn't be happening, but sometimes FirstData is returning two separate
                    // preauth transactions in a single verify response. In these cases, the second
                    // preauth is usually declined due to the order existing already in an unexpected
                    // state. So we avoid the second transaction, and break after finding the first.
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
                                                                           ->ProcessorApprovalCode
            ];

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

            if ($gatewayPayment['status'] !== Status::AUTHORIZED)
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

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'body'    => $response->body,
                'headers' => $response->headers,
                'code'    => $response->status_code,
            ]
        );

        if ($response->body === null)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
        }

        $xml = simplexml_load_string(trim($response->body));

        return $xml;
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
        $component = Component::ACTION_MAPPING[$this->action];

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

        $txnType = TxnType::AUTH;

        if ((Payment\Gateway::supportsAuthAndCapture($this->gateway, $networkCode) === false) or
            (($input['card'][Card\Entity::ISSUER] === Card\Issuer::ICIC) and
             ($input['card'][Card\Entity::TYPE] === Card\Type::DEBIT)))
        {
            $txnType = TxnType::SALE;
        }

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
            ConnectRequestFields::NAME                      => $input['card'][Card\Entity::NAME],
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

        curl_setopt($curl, CURLOPT_CAINFO, $this->getServerCertificate());

        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: text/xml"]);
    }

    protected function getVerifyRequestContentArray(array $input)
    {
        switch ($this->action)
        {
            case Action::VERIFY:
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
                break;
            case Action::VERIFY_REFUND:
                $reference = [
                    ApiRequestFields::A1_INQUIRY_TRANSACTION => [
                        ApiRequestFields::A1_STORE_ID        => $this->getStoreId(),
                        ApiRequestFields::A1_MERCHANT_TXN_ID => $input['refund']['id'],
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

        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CHARGE_TOTAL] = $input[$amountEntity]['amount'] / 100;

        $body[ApiRequestFields::V1_PAYMENT][ApiRequestFields::V1_CURRENCY] = $currencyCode;
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

        $this->trace->info(
            TraceCode::GATEWAY_SOAP_REQUEST,
            ['gateway_soap_request' => $request]);
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
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

    protected function scrubCardInfo(array & $content)
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

    protected function getAuthCodeFromCallback($callbackBody)
    {
        $authCode = null;

        $approvalCodeArray = explode(':', $callbackBody[ConnectResponseFields::APPROVAL_CODE]);

        // Only when call had succeed, we get authCode in approvalCode
        if (($approvalCodeArray[0] === 'Y') and
            (isset($approvalCodeArray[1]) === true))
        {
            $authCode = $approvalCodeArray[1];
        }

        return $authCode;
    }
}
