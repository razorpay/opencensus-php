<?php

namespace RZP\Gateway\Cybersource;

use Cache;
use Config;
use SoapVar;
use SoapFault;
use SoapClient;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Base\JitValidator;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Cybersource\Fields as F;
use RZP\Gateway\Cybersource\Entity as E;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use Base\AuthorizeFailed;

    const CACHE_KEY = 'cybersource_%s_card_details';

    const CACHE_TTL = 15;

    // Request timeout limit in seconds
    const TIMEOUT = 60;

    const TEST_MERCHANT_ID      = 'test_merchant_id';
    const TEST_MERCHANT_SECRET  = 'test_merchant_secret';
    const TEST_USERNAME         = 'test_username';
    const TEST_PASSWORD         = 'test_password';

    protected $bankAcsResponseRules = [
        'PaRes'     => 'required',
        'MD'        => 'required',
        'PaReq'     => 'sometimes'
    ];

    protected $gateway = 'cybersource';

    protected $secureCacheDriver;

    protected $eci;

    public function __construct()
    {
        parent::__construct();

        $this->secureCacheDriver = Config::get('cache.secure_default');
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            return $this->authorizeRecurring($input);
        }

        $response = $this->enroll($input);

        return $this->decideAuthStepAfterEnroll($input, $response);
    }

    public function capture(array $input)
    {
        // We are using action to allow force capture on
        // already captured payment entity, when they are not
        // captured on gateway
        parent::action($input, Action::CAPTURE);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                $input['payment']['id'], Action::AUTHORIZE);

        $request = $this->getCaptureRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_CAPTURE_REQUEST);

        try
        {
            $response = $this->postRequest($request);

            $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_CAPTURE_RESPONSE);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }

            $gatewayAttributes = $this->getAttributeFromCaptureResponse($input, $response);

            $this->createGatewayPaymentEntity($gatewayAttributes, $input);
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, 'Payment capture failed');
        }
    }

    public function manualGatewayCapture(array $input)
    {
        $canManualCapture = $this->canForceCapture($input);

        if ($canManualCapture)
        {
            $this->capture($input);

            // Successfully captured on the gateway
            return true;
        }

        // Did not capture on the gateway side
        return false;
    }

    protected function canForceCapture($input)
    {
        $paymentId = $input['payment'][Payment\Entity::ID];

        $gatewayPaymentEntity = $this->repo->findSuccessfulCapturedEntity($paymentId);

        if (($gatewayPaymentEntity !== null) and
            ($gatewayPaymentEntity->getAmount() === $input['amount']))
        {
            return false;
        }

        return true;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->validateCallbackGatewayFields($input);

        $this->fixParesIfRequired($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                $input['payment']['id'], Action::AUTHORIZE);

        $this->setCardNumberAndCvv($input);

        $response = $this->validateAuthReply($input, $gatewayPayment);

        $this->authorizeEnrolled($input, $response, $gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                $input['payment']['id'], Action::CAPTURE);

        $request = $this->getRefundRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        try
        {
            $response = $this->postRequest($request);

            $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

            $gatewayAttributes = $this->getAttributeFromRefundResponse($input, $response);

            $this->createGatewayRefundEntity($gatewayAttributes, $input);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, 'Refund failed');
        }
    }

    public function reverse(array $input)
    {
        parent::action($input, Action::REVERSE);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                $input['payment']['id'], Action::AUTHORIZE);

        $request = $this->getAuthReversalRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REVERSE_REQUEST);

        try
        {
            $response = $this->postRequest($request);

            $this->traceGatewayPaymentResponse(
                $response, $input, TraceCode::GATEWAY_REVERSE_RESPONSE);

            $gatewayAttributes = $this->getAttributeFromAuthReversalResponse($input, $response);

            $this->createGatewayRefundEntity($gatewayAttributes, $input);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, 'Reverse failed');
        }
    }

    /**
     * Calls gateway to verify if a refund has
     * been successfully performed or not.
     *
     * true  if refunded
     * false if not refunded
     *
     * @param array $input
     *
     * @return bool
     * @throws Exception\LogicException
     */
    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $content = $this->sendRefundVerifyRequest($input);

        $refundReplies = $this->fetchRefundGatewayReplyFromContent($content);

        foreach ($refundReplies as $refundReply)
        {
            if ((isset($refundReply[0][F::R_FLAG]) === true) and
                ($refundReply[0][F::R_FLAG] === ReplyFlag::SOK))
            {
                if ($refundReply[0]['@attributes'][F::NAME] === 'ics_auth_reversal')
                {
                    $status = Status::REVERSED;
                }
                else if ($refundReply[0]['@attributes'][F::NAME] === 'ics_credit')
                {
                    $status = Status::REFUNDED;
                }
                else
                {
                    throw new Exception\LogicException(
                        'Unexpected status',
                        null,
                        [
                            'received_status' => $refundReply[0]['@attributes'][F::NAME]
                        ]);
                }

                $responseRequest = $refundReply[1];

                $gatewayEntity = $this->repo->findByRefundId($input['refund']['id']);

                if ($gatewayEntity !== null)
                {
                    $gatewayEntity->setStatus($status);

                    $this->repo->saveOrFail($gatewayEntity);
                }
                else
                {
                    //
                    // Else condition is needed for the case where refund request fails
                    // at the soap level. In that case, we don't create a gateway refund
                    // entity.
                    //
                    $attributes = $this->getRefundAttributesFromVerify($responseRequest);
                    $attributes[E::STATUS] = $status;

                    $this->createGatewayRefundEntity($attributes, $input);
                }

                return true;
            }
        }

        return false;
    }

    protected function getRefundAttributesFromVerify(array $request)
    {
        return [
            E::REF           => $request[F::PAYMENT_DATA][F::PAYMENT_REQUEST_ID],
            E::REASON_CODE   => 200,
            E::RECEIVED      => true
        ];
    }

    protected function sendRefundVerifyRequest($input)
    {
        $request = $this->getRefundVerifyRequestContent($input);

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST);

        $this->setCybersourceCredentials($request);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse(
            $response->body,
            $input,
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE);

        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        // We are adding this condition as Cybersource updates the cache
        // after sometime (read as 30 seconds). It a payment has been authorized
        // recently (30 seconds), we skip the verify for that bucket.
        if (($input['payment']['authorized_at'] !== null) and
            ($input['payment']['authorized_at'] >= strtotime('-30 seconds')))
        {
            return null;
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getPaymentVerifyRequestContent($input);

        $this->traceGatewayPaymentRequest(
            $request,
            $input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $this->setCybersourceCredentials($request);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse(
            $response->body,
            $input,
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestContent(array $input)
    {
        return $this->getVerifyRequestContent($input, 'payment');
    }

    protected function getRefundVerifyRequestContent(array $input)
    {
        $request = $this->getVerifyRequestContent($input, 'refund');

        $targetDate = Carbon::createFromTimestamp($input['refund']['last_attempted_at'], Timezone::IST)
                            ->format('Ymd');

        $request['content'][F::TARGET_DATE] = $targetDate;

        return $request;
    }

    protected function getVerifyRequestContent(array $input, $entity)
    {
        $targetDate = Carbon::createFromTimestamp($input[$entity]['created_at'], Timezone::IST)
                            ->format('Ymd');

        $content = [
            F::TYPE                      => 'transaction',
            F::SUBTYPE                   => 'transactionDetail',
            F::MERCHANT_ID               => $this->getMerchantID($input['terminal']),
            F::TARGET_DATE               => $targetDate,
            F::VERSION_NUMBER            => '1.90',
            F::MERCHANT_REFERENCE_NUMBER => $input[$entity]['id'],
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        list($authReply, $requestContent) = $this->fetchPaymentGatewayReplyFromContent($content);

        // Payment is failed when ics_auth is not present
        if ((isset($authReply[F::R_FLAG]) === false) or
            ($authReply[F::R_FLAG] !== ReplyFlag::SOK))
        {
            $this->verifyNonExistentCase($verify);
        }
        else if ($authReply[F::R_FLAG] === ReplyFlag::SOK)
        {
            $this->verifyPaymentReconcileWithGatewayResponse($verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->verifyResponseContent = $this->getVerifyContentFromResponse($requestContent);

        return $verify->status;
    }

    protected function verifyNonExistentCase($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $verify->gatewaySuccess = false;

        if (($payment === null) and
            (($input['payment']['status'] === 'failed') or
             ($input['payment']['status'] === 'created')))
        {
            $verify->apiSuccess = false;
        }
        else if (($payment['received'] === false) and
                 (($payment['status'] === null) or
                  ($payment['status'] !== Status::AUTHORIZED)))
        {
            $verify->apiSuccess = false;
        }
        else if ($payment['status'] === Status::AUTHORIZED)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyPaymentReconcileWithGatewayResponse($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $verify->gatewaySuccess = true;

        if (($input['payment']['status'] !== 'created') and
            ($input['payment']['status'] !== 'failed'))
        {
            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
        }
    }

    protected function fetchRefundGatewayReplyFromContent($content)
    {
        return $this->fetchGatewayReplyFromContent($content, ['ics_credit', 'ics_auth_reversal']);
    }

    protected function fetchPaymentGatewayReplyFromContent($content)
    {
        return $this->fetchGatewayReplyFromContent($content, ['ics_auth'])[0];
    }

    protected function fetchGatewayReplyFromContent($content, array $types)
    {
        $requests = $content[F::REQUESTS][F::REQUEST] ?? null;

        $response = null;

        if ($requests !== null)
        {
            if ($this->isSequentialArray($requests) === false)
            {
                $requests = [$requests];
            }

            foreach($requests as $request)
            {
                $applicationReplies = $request[F::APPLICATION_REPLIES][F::APPLICATION_REPLY];

                if ($this->isSequentialArray($applicationReplies) === false)
                {
                    $applicationReplies = [$applicationReplies];
                }

                foreach($applicationReplies as $applicationReply)
                {
                    if (in_array($applicationReply['@attributes'][F::NAME], $types, true))
                    {
                        $response[] = [$applicationReply, $request];
                    }
                }
            }
        }

        return $response ?: [[[], []]];
    }

    /**
     * Get array content from XML, don't use data from this to save
     * in verify. Only use this for authorize failed
     *
     * @param array $content Parsed XML array
     * @return array
     */
    protected function getVerifyContentFromResponse(array $content)
    {
        if (empty($content[F::PAYMENT_DATA]) === false)
        {
            $attributes = [
                E::REF                => $content[F::PAYMENT_DATA][F::PAYMENT_REQUEST_ID],
                E::AUTHORIZATION_CODE => $content[F::PAYMENT_DATA]['AuthorizationCode'] ?? null,
                E::AVS_CODE           => $content[F::PAYMENT_DATA][F::AVS_RESULT] ?? null,
                E::CV_CODE            => $content[F::PAYMENT_DATA][F::CV_RESULT] ?? null,
                E::STATUS             => Status::AUTHORIZED,
                E::REASON_CODE        => 100
            ];

            if (empty($content[F::PAYMENT_DATA][F::PAYER_AUTHENTICATION_INFO]) === false)
            {
                $payerAuthInfo = $content[F::PAYMENT_DATA][F::PAYER_AUTHENTICATION_INFO];

                $attributes[E::ECI]  = $payerAuthInfo['ECI'] ?? null;
                $attributes[E::CAVV] = $payerAuthInfo['AAV_CAVV'] ?? null;
                $attributes[E::XID]  = $payerAuthInfo['XID'] ?? null;
            }

            return $attributes;
        }

        return $content;
    }

    protected function decideAuthStepAfterEnroll(array $input, array $response)
    {
        switch ($response[F::REASON_CODE])
        {
            case Result::ENROLLED:
                $this->persistCardDetailsTemporarily($input);

                return $this->getFieldsForFormSubmitToBankAcs($input, $response);

            case Result::NOT_ENROLLED:
                $payerAuthEnrollReply = $response[F::PA_ENROLL_REPLY];

                $this->validateAndSetEciValue($input, $this->gatewayPayment, $payerAuthEnrollReply);

                return $this->authorizeNotEnrolled($input, $response);
        }

        // @codeCoverageIgnoreStart
        // Adding this as a defensive code, code should never reach here.
        throw new Exception\LogicException(
            'Unexpected response',
            null,
            [
                'payment_id'  => $input['payment']['id'],
                'reason_code' => $response[F::REASON_CODE],
            ]);
        // @codeCoverageIgnoreEnd
    }

    protected function enroll(array $input)
    {
        $enrollRequest = $this->getEnrollRequestArray($input);

        $this->traceGatewayPaymentRequest($enrollRequest, $input, TraceCode::GATEWAY_ENROLL_REQUEST);

        try
        {
            $response = $this->postRequest($enrollRequest);

            $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_ENROLL_RESPONSE);

            if (($response[F::DECISION] === Decision::ERROR) or
                (($response[F::REASON_CODE] !== Result::NOT_ENROLLED) and
                 ($response[F::REASON_CODE] !== Result::ENROLLED)))
            {
                $gatewayAttributes = [
                    E::REF           => $response[F::REQUEST_ID],
                    E::STATUS        => Status::ENROLL_FAILED,
                    E::REASON_CODE   => $response[F::REASON_CODE],
                    E::RECEIVED      => '1'
                ];

                $gatewayPayment = $this->createGatewayPaymentEntity($gatewayAttributes, $input);

                $this->checkErrorsAndThrowException($response);
            }

            $gatewayAttributes = $this->getAttributeFromAuthEnrollResponse($input, $response);

            $gatewayPayment = $this->createGatewayPaymentEntity($gatewayAttributes, $input);

            $this->gatewayPayment = $gatewayPayment;

            return $response;
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, 'Auth Enroll: Server Error occured', true);
        }
    }

    protected function authorizeNotEnrolled(array $input, array $response)
    {
        $payerAuthEnrollReply = $response[F::PA_ENROLL_REPLY];

        $authRequest = $this->getAuthorizeRequestArray($input, $payerAuthEnrollReply);

        $this->traceGatewayPaymentRequest($authRequest, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        try
        {
            $response = $this->postRequest($authRequest);

            $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

            $gatewayAttributes = $this->getAttributeFromAuthorizeResponse($input, $response);

            $gatewayPayment = $this->gatewayPayment;

            $gatewayPayment->fill($gatewayAttributes);

            $this->repo->saveOrFail($gatewayPayment);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Authorization failed");
        }
    }

    protected function authorizeEnrolled(array $input, array $response, Entity $gatewayPayment)
    {
        $payerAuthValidateReply = $response[F::PA_VALIDATE_REPLY];

        $authRequest = $this->getAuthorizeEnrolledRequestArray($input, $payerAuthValidateReply, $gatewayPayment);

        $this->traceGatewayPaymentRequest(
            $authRequest, $input, TraceCode::GATEWAY_ENROLLED_AUTH_REQUEST);

        try
        {
            $response = $this->postRequest($authRequest);

            $this->traceGatewayPaymentResponse(
                $response, $input, TraceCode::GATEWAY_ENROLLED_AUTH_RESPONSE);

            if (($response[F::DECISION] === Decision::REJECT) or
                ($response[F::DECISION] === Decision::ERROR))
            {
                $gatewayAttributes = [
                    E::REF                => $response[F::REQUEST_ID],
                    E::STATUS             => Status::AUTHORIZE_FAILED,
                    E::REASON_CODE        => $response[F::REASON_CODE],
                    E::ECI                => $payerAuthValidateReply[F::ECI] ?? null,
                    E::COMMERCE_INDICATOR => $payerAuthValidateReply[F::COMMERCE_INDICATOR] ?? null,
                    E::PARES_STATUS       => $payerAuthValidateReply[F::PARES_STATUS] ?? null,
                    E::RECEIVED           => '1'
                ];

                if (isset($payerAuthValidateReply[F::UCAF_COLLECTION_INDICATOR]) === true)
                {
                    $gatewayAttributes[E::ECI] = $payerAuthValidateReply[F::UCAF_COLLECTION_INDICATOR];
                }

                $gatewayPayment->fill($gatewayAttributes);
                $gatewayPayment->save();

                $this->checkErrorsAndThrowException($response);
            }

            $gatewayAttributes = $this->getAttributeFromAuthorizeEnrolledResponse($input, $response);

            $gatewayPayment->fill($gatewayAttributes);
            $gatewayPayment->save();
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Authorization failed");
        }
    }

    protected function authorizeRecurring(array $input)
    {
        $authRequest = $this->getAuthorizeRecurringRequestArray($input);

        $this->traceGatewayPaymentRequest(
            $authRequest, $input, TraceCode::GATEWAY_RECURRING_AUTH_REQUEST);

        try
        {
            $response = $this->postRequest($authRequest);

            $this->traceGatewayPaymentResponse(
                $response, $input, TraceCode::GATEWAY_RECURRING_AUTH_RESPONSE);

            $gatewayAttributes = $this->getAttributeFromAuthorizeResponse($input, $response);

            $gatewayAttributes[E::COMMERCE_INDICATOR] = CommerceIndicator::RECURRING;

            $this->createGatewayPaymentEntity($gatewayAttributes, $input);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Authorization failed");
        }
    }

    protected function validateAuthReply(array $input, Entity $gatewayPayment)
    {
        $request = $this->getValidateAuthRequestArray($input);

        $this->traceGatewayPaymentRequest(
            $request, $input, TraceCode::GATEWAY_VALIDATE_AUTH_REQUEST);

        try
        {
            $response = $this->postRequest($request);

            $this->traceGatewayPaymentResponse(
                $response, $input, TraceCode::GATEWAY_VALIDATE_AUTH_RESPONSE);

            $payerAuthValidateReply = $response[F::PA_VALIDATE_REPLY];

            $this->validateXidIfApplicable($gatewayPayment, $payerAuthValidateReply);

            $gatewayAttributes = $this->getAttributeFromAuthValidateResponse($input, $response);

            $gatewayPayment->fill($gatewayAttributes);

            $this->validateAndSetEciValue($input, $gatewayPayment, $payerAuthValidateReply);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->repo->saveOrFail($gatewayPayment);

                $this->checkErrorsAndThrowException($response);
            }
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, "Authorization failed");
        }

        return $response;
    }

    protected function validateAndSetEciValue(array $input, Entity $gatewayPayment, array $response)
    {
        $networkCode = $input['card']['network_code'];

        switch ($networkCode)
        {
            case Card\Network::VISA:

                $eciRaw = $response[F::ECI] ?? '07';

                // NOTE: Make sure PHP return correct int on conversion
                // Example: '012' should be converted to decimal 12 not octal 12
                $eci = (int) $eciRaw;

                if ($eci === 7)
                {
                    $desc = 'ECI value shouldn\'t be 7.';
                }

                break;

            case Card\Network::MC:
                $eciRaw = $response[F::UCAF_COLLECTION_INDICATOR] ?? '07';

                $eci = (int) $eciRaw;

                if (($eci === 7) or ($eci === 0))
                {
                    $desc = 'ECI value shouldn\'t be 7 or 0. ECI: ' . $eci;
                }

                break;
        }

        if (isset($desc) === true)
        {
            $gatewayPayment->setStatus(Status::AUTHORIZE_FAILED);
            $this->repo->saveOrFail($gatewayPayment);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                $eciRaw,
                $desc);
        }

        $this->eci = $eciRaw;
    }

    protected function getFieldsForFormSubmitToBankAcs(array $input, array $response)
    {
        $content = [
            'TermUrl' => $input['callbackUrl'],
            'MD'      => $input['payment']['id'],
            'PaReq'   => $response[F::PA_ENROLL_REPLY][F::PA_REQ]
        ];

        $request = [
            'url'     => $response[F::PA_ENROLL_REPLY][F::ACS_URL],
            'method'  => 'post',
            'content' => $content
        ];

        return $request;
    }

    protected function getAttributeFromAuthEnrollResponse(array $input, array $response)
    {
        $payerAuthEnrollReply = $response[F::PA_ENROLL_REPLY];

        $attributes = [
            E::REF                  => $response[F::REQUEST_ID],
            E::REASON_CODE          => $response[F::REASON_CODE],
            E::XID                  => $payerAuthEnrollReply[F::XID] ?? null,
            E::VERES_ENROLLED       => $payerAuthEnrollReply[F::VERES_ENROLLED] ?? null,
            E::COMMERCE_INDICATOR   => $payerAuthEnrollReply[F::COMMERCE_INDICATOR] ?? null,
            E::STATUS               => Status::CREATED
        ];

        if ($input['card']['network_code'] === Card\Network::MC)
        {
            $attributes[E::ECI] = $payerAuthEnrollReply[F::UCAF_COLLECTION_INDICATOR] ?? null;
        }

        if ($input['card']['network_code'] === Card\Network::VISA)
        {
            $attributes[E::ECI] = $payerAuthEnrollReply[F::ECI] ?? null;
        }

        return $attributes;
    }

    protected function getAttributeFromAuthorizeResponse(array $input, array $response)
    {
        $ccAuthReply = $response[F::CC_AUTH_REPLY];

        $attributes = [
            E::REF                      => $response[F::REQUEST_ID],
            E::REASON_CODE              => $response[F::REASON_CODE],
            E::RECEIPT_NUMBER           => $response[F::RECEIPT_NUMBER] ?? null,
            E::AUTHORIZATION_CODE       => $ccAuthReply[F::AUTHORIZATION_CODE] ?? null,
            E::AVS_CODE                 => $ccAuthReply[F::AVS_CODE] ?? null,
            E::CARD_CATEGORY            => $ccAuthReply[F::CARD_CATEGORY] ?? null,
            E::CARD_GROUP               => $ccAuthReply[F::CARD_GROUP] ?? null,
            E::CV_CODE                  => $ccAuthReply[F::CV_CODE] ?? null,
            E::MERCHANT_ADVICE_CODE     => $ccAuthReply[F::MERCHANT_ADVICE_CODE] ?? null,
            E::GATEWAY_TRANSACTION_ID   => $ccAuthReply[F::PAYMENT_NETWORK_TXN_ID] ?? null,
            E::PROCESSOR_RESPONSE       => $ccAuthReply[F::PROCESSOR_RESPONSE],
            E::STATUS                   => Status::AUTHORIZED,
            E::RECEIVED                 => true
        ];

        if ($response[F::REASON_CODE] !== Result::SUCCESS)
        {
            $attributes[E::STATUS] = Status::AUTHORIZE_FAILED;
        }
        else
        {
            // We are doing this because paymentNetworkTransactionId should
            // be present if payment is successful.
            $attributes[E::GATEWAY_TRANSACTION_ID] = $ccAuthReply[F::PAYMENT_NETWORK_TXN_ID];
        }

        return $attributes;
    }

    protected function getAttributeFromAuthValidateResponse(array $input, array $response)
    {
        $payerAuthValidateReply = $response[F::PA_VALIDATE_REPLY];

        $attributes = [
            E::REF                      => $response[F::REQUEST_ID],
            E::REASON_CODE              => $response[F::REASON_CODE],
            E::CAVV                     => $payerAuthValidateReply[F::CAVV] ?? null,
            E::XID                      => $payerAuthValidateReply[F::XID] ?? null,
            E::PARES_STATUS             => $payerAuthValidateReply[F::PARES_STATUS] ?? null,
            E::COMMERCE_INDICATOR       => $payerAuthValidateReply[F::COMMERCE_INDICATOR] ?? null,
            E::ECI                      => $payerAuthValidateReply[F::ECI_RAW] ?? null,
        ];

        if ($attributes[E::ECI] === null)
        {
            if (isset($payerAuthValidateReply[F::ECI]) === true)
            {
                $attributes[E::ECI] = $payerAuthValidateReply[F::ECI];
            }

            if (isset($payerAuthValidateReply[F::UCAF_COLLECTION_INDICATOR]) === true)
            {
                $attributes[E::ECI] = $payerAuthValidateReply[F::UCAF_COLLECTION_INDICATOR];
            }
        }

        if (isset($payerAuthValidateReply[F::UCAF_AUTHENTICATION_DATA]) === true)
        {
            $attributes[E::AUTH_DATA] = $payerAuthValidateReply[F::UCAF_AUTHENTICATION_DATA];
        }

        if ($response[F::REASON_CODE] !== Result::SUCCESS)
        {
            $attributes[E::STATUS] = Status::AUTHORIZE_FAILED;
        }

        return $attributes;
    }

    protected function getAttributeFromAuthorizeEnrolledResponse(array $input, array $response)
    {
        // $payerAuthValidateAttributes = $this->getAttributeFromAuthValidateResponse($input, $response);
        $ccAuthAttributes = $this->getAttributeFromAuthorizeResponse($input, $response);

        // $attributes = array_merge($payerAuthValidateAttributes, $ccAuthAttributes);

        return $ccAuthAttributes;
    }

    protected function getAttributeFromCaptureResponse(array $input, array $response)
    {
        $attributes = [
            E::REF           => $response[F::REQUEST_ID],
            E::REASON_CODE   => $response[F::REASON_CODE],
            E::STATUS        => Status::CAPTURED,
            E::RECEIVED      => true
        ];

        return $attributes;
    }

    protected function getAttributeFromRefundResponse(array $input, array $response)
    {
        $attributes = [
            E::REF           => $response[F::REQUEST_ID],
            E::REASON_CODE   => $response[F::REASON_CODE],
            E::STATUS        => Status::REFUNDED,
            E::RECEIVED      => true
        ];

        if ($response[F::REASON_CODE] !== Result::SUCCESS)
        {
            $attributes[E::STATUS] = Status::REFUND_FAILED;
        }

        return $attributes;
    }

    protected function getAttributeFromAuthReversalResponse(array $input, array $response)
    {
        $ccAuthReversalReply = $response[F::CC_AUTH_REVERSAL_REPLY];

        $attributes = [
            E::REF                => $response[F::REQUEST_ID],
            E::REASON_CODE        => $response[F::REASON_CODE],
            E::STATUS             => Status::REVERSED,
            E::RECEIVED           => true
        ];

        if ($response[F::REASON_CODE] !== Result::SUCCESS)
        {
            $attributes[E::STATUS] = Status::REVERSE_FAILED;
        }

        return $attributes;
    }

    protected function getEnrollRequestArray(array $input)
    {
        $content = [];

        $content[F::MERCHANT_ID] = $this->getMerchantId($input['terminal']);
        $content[F::MERCHANT_REFERENCE_CODE] = $input['payment']['id'];

        $content[F::PA_ENROLL_SERVICE] = [
            F::RUN => 'true'
        ];

        $content[F::CARD] = [
            F::ACCOUNT_NUMBER   => $input['card']['number'],
            F::EXPIRATION_MONTH => $input['card']['expiry_month'],
            F::EXPIRATION_YEAR  => $input['card']['expiry_year'],
        ];

        $content[F::PURCHASE_TOTALS] = [
            F::CURRENCY           => $input['payment']['currency'],
            F::GRAND_TOTAL_AMOUNT => ($input['payment']['amount'] / 100)
        ];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getAuthorizeRequestArray(array $input, array $response = null)
    {
        $content = [];

        $content[F::MERCHANT_ID] = $this->getMerchantId($input['terminal']);
        $content[F::MERCHANT_REFERENCE_CODE] = $input['payment']['id'];

        $content[F::CC_AUTH_SERVICE] = [
            F::RUN => 'true',
            F::RECONCILIATION_ID => $input['payment']['id'],
        ];

        $content[F::INVOICE_HEADER] = [
            F::MERCHANT_DESCRIPTOR => $this->getDynamicMerchantDescription($input['merchant'])
        ];

        $content[F::BUSINESS_RULES] = [
            F::IGNORE_AVS_RESULT => 'true'
        ];

        if (isset($this->eci) === true)
        {
            $cardNetwork = $input['card']['network_code'];

            if ($cardNetwork === Card\Network::VISA)
            {
                $content[F::CC_AUTH_SERVICE][F::ECI] = $this->eci;
            }

            if ($cardNetwork === Card\Network::MC)
            {
                $content[F::UCAF][F::COLLECTION_INDICATOR] = $this->eci;
            }
        }

        if ($response !== null)
        {
            if (isset($response[F::VERES_ENROLLED]) === true)
            {
                $content[F::CC_AUTH_SERVICE][F::VERES_ENROLLED] = $response[F::VERES_ENROLLED];
            }

            $content[F::CC_AUTH_SERVICE][F::COMMERCE_INDICATOR] = $response[F::COMMERCE_INDICATOR];
        }

        $content[F::CARD] = [
            F::ACCOUNT_NUMBER   => $input['card']['number'],
            F::EXPIRATION_MONTH => $input['card']['expiry_month'],
            F::EXPIRATION_YEAR  => $input['card']['expiry_year'],
            F::CVN              => $input['card']['cvv'] ?? null,
        ];

        $content[F::PURCHASE_TOTALS] = [
            F::CURRENCY           => $input['payment']['currency'],
            F::GRAND_TOTAL_AMOUNT => ($input['payment']['amount'] / 100)
        ];

        $content[F::BILL_TO] = $this->getBillingInfo($input);

        //
        // We are doing this because not all the terminals have this configuration
        // from CYBS end. This is to decrease the cases of "Do Not Honour" which was
        // happening because of the AVS checks at the issuer end.
        //
        if (($input['terminal']['gateway_terminal_id'] === 'RAZORPAYCYBS') or
            ($input['terminal']['gateway_terminal_id'] === 'hdfc_89050055'))
        {
            unset($content[F::BILL_TO]);
        }

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getAuthorizeRecurringRequestArray(array $input)
    {
        $authRequest = $this->getAuthorizeRequestArray($input);

        // Unset CVV number as it's not required in recurring
        unset($authRequest['content'][F::CARD][F::CVN]);

        // Set commerceIndicator as recurring
        $authRequest['content'][F::CC_AUTH_SERVICE] = [
            F::RUN                => 'true',
            F::COMMERCE_INDICATOR => CommerceIndicator::RECURRING
        ];

        return $authRequest;
    }

    protected function getAuthorizeEnrolledRequestArray(
        array $input,
        array $payerAuthValidateReply,
        Entity $gatewayPayment)
    {
        $authServiceRequest  = $this->getAuthorizeRequestArray($input, $payerAuthValidateReply);

        $ccAuthService = $authServiceRequest['content'][F::CC_AUTH_SERVICE];

        $ccAuthService = [
            F::RUN                => 'true',
            F::XID                => $gatewayPayment->getXid(),
            F::ECI_RAW            => $gatewayPayment->getEci(),
            F::PARES_STATUS       => $gatewayPayment->getParesStatus(),
            F::VERES_ENROLLED     => $gatewayPayment->getVeresEnrolled(),
            F::COMMERCE_INDICATOR => $gatewayPayment->getCommerceIndicator(),
            F::RECONCILIATION_ID  => $input['payment']['id'],
        ];

        $cardNetwork = $input['card']['network_code'];

        if ($cardNetwork === Card\Network::VISA)
        {
            $ccAuthService[F::CAVV] = $gatewayPayment->getCavv();
        }

        if ($cardNetwork === Card\Network::MC)
        {
            $ucafAuthData = $gatewayPayment->getUcafAuthenticationData();

            $authServiceRequest['content'][F::UCAF][F::AUTHENTICATION_DATA] = $ucafAuthData;
        }

        $authServiceRequest['content'][F::CC_AUTH_SERVICE] = $ccAuthService;

        return $authServiceRequest;
    }

    protected function getRefundRequestArray(array $input, Entity $gatewayPayment)
    {
        $content = [];

        $content[F::MERCHANT_ID] = $this->getMerchantId($input['terminal']);
        $content[F::MERCHANT_REFERENCE_CODE] = $input['refund']['id'];

        $content[F::CC_CREDIT_SERVICE] = [
            F::RUN                => 'true',
            F::CAPTURE_REQUEST_ID => $gatewayPayment->getCaptureRequestId(),
            F::RECONCILIATION_ID  => $input['refund']['id'],
        ];

        $content[F::INVOICE_HEADER] = [
            F::MERCHANT_DESCRIPTOR => $this->getDynamicMerchantDescription($input['merchant'])
        ];

        $content[F::PURCHASE_TOTALS] = [
            F::CURRENCY           => $input['payment']['currency'],
            F::GRAND_TOTAL_AMOUNT => ($input['refund']['amount'] / 100)
        ];

        $content[F::MERCHANT_DEFINED_DATA] = [
            F::MDD_FIELD => [
                [
                    'id' => '1',
                    '_'  => UserDefinedField::CURRENT_VERSION
                ],
                [
                    'id' => '2',
                    '_'  => $input['payment']['id']
                ]
            ]
        ];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getAuthReversalRequestArray(array $input, Entity $gatewayPayment)
    {
        $content = [];

        $content[F::MERCHANT_ID] = $this->getMerchantId($input['terminal']);
        $content[F::MERCHANT_REFERENCE_CODE] = $input['refund']['id'];

        $content[F::CC_AUTH_REVERSAL_SERVICE] = [
            F::RUN                => 'true',
            F::AUTH_REQUEST_ID    => $gatewayPayment->getRequestId()
        ];

        $content[F::PURCHASE_TOTALS] = [
            F::CURRENCY           => $input['payment']['currency'],
            F::GRAND_TOTAL_AMOUNT => ($input['refund']['amount'] / 100)
        ];

        $content[F::MERCHANT_DEFINED_DATA] = [
            F::MDD_FIELD => [
                [
                    'id' => '1',
                    '_'  => UserDefinedField::CURRENT_VERSION
                ],
                [
                    'id' => '2',
                    '_'  => $input['payment']['id']
                ]
            ]
        ];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getCaptureRequestArray(array $input, Entity $gatewayPayment)
    {
        $content = [];

        $content[F::MERCHANT_ID] = $this->getMerchantId($input['terminal']);
        $content[F::MERCHANT_REFERENCE_CODE] = $input['payment']['id'];

        $content[F::CC_CAPTURE_SERVICE] = [
            F::RUN => 'true',
            F::AUTH_REQUEST_ID => $gatewayPayment->getRequestId(),
            F::RECONCILIATION_ID  => $input['payment']['id'],
        ];

        $content[F::INVOICE_HEADER] = [
            F::MERCHANT_DESCRIPTOR => $this->getDynamicMerchantDescription($input['merchant'])
        ];

        $content[F::PURCHASE_TOTALS] = [
            F::CURRENCY           => $input['payment']['currency'],
            F::GRAND_TOTAL_AMOUNT => ($input['payment']['amount'] / 100)
        ];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    protected function getValidateAuthRequestArray(array $input)
    {
        $content = [];

        $content[F::PA_VALIDATE_SERVICE] = [
            F::RUN                => 'true',
            F::SIGNED_PA_RES      => $input['gateway'][F::PA_RES],
        ];

        $content[F::MERCHANT_ID] = $this->getMerchantId($input['terminal']);
        $content[F::MERCHANT_REFERENCE_CODE] = $input['payment']['id'];

        $content[F::CARD] = [
            F::ACCOUNT_NUMBER   => $input['card']['number'],
            F::EXPIRATION_MONTH => $input['card']['expiry_month'],
            F::EXPIRATION_YEAR  => $input['card']['expiry_year'],
        ];

        $content[F::PURCHASE_TOTALS] = [
            F::CURRENCY           => $input['payment']['currency']
        ];

        $request = $this->getStandardSoapRequest($content);

        return $request;
    }

    /**
     * Sets dummy billing info as AVS is not
     * supported in India
     */
    protected function getBillingInfo(array $input)
    {
        $billingInfo = [
            F::FIRST_NAME  => 'noreal',
            F::LAST_NAME   => 'name',
            F::STREET      => '1295 Charleston Rd',
            F::CITY        => 'Mountain View',
            F::STATE       => 'CA',
            F::POSTAL_CODE => '94043',
            F::COUNTRY     => 'US',
            F::EMAIL       => $input['payment']['email']
        ];

        return $billingInfo;
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $currency  = $input['payment']['currency'];
        $acquirer  = $input['terminal']->getGatewayAcquirer();

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAmount($amount);

        $gatewayPayment->setCurrency($currency);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setAcquirer($acquirer);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId    = $input['payment']['id'];
        $refundId     = $input['refund']['id'];
        $refundAmount = $input['refund']['amount'];
        $currency     = $input['refund']['currency'];
        $acquirer  = $input['terminal']->getGatewayAcquirer();

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setRefundId($refundId);

        $gatewayPayment->setAmount($refundAmount);

        $gatewayPayment->setCurrency($currency);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setAcquirer($acquirer);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function postRequest($request)
    {
        $soapClient = $this->getSoapClientObject($request);

        $response = $soapClient->runTransaction($request['content']);

        // Hack to convert object to array recursively
        return json_decode(json_encode($response), true);
    }

    protected function getStandardSoapRequest($content = [])
    {
        $request = [
            'wsdl'    => $this->getWsdlFile(),
            'content' => $content,
            'auth'    => $this->getCredentials(),
            'options' => [
                'encoding'           => 'UTF-8',
                'exception'          => true,
                'connection_timeout' => self::TIMEOUT
            ],
        ];

        return $request;
    }

    protected function getCredentials()
    {
        $terminal = $this->terminal;

        $auth = [
            'username' => $terminal['gateway_terminal_id'],
            'password' => $terminal['gateway_terminal_password']
        ];

        if ($this->mode === Mode::TEST)
        {
            $auth = [
                'username' => $this->config[self::TEST_USERNAME],
                'password' => $this->config[self::TEST_PASSWORD]
            ];
        }

        return $auth;
    }

    protected function getMerchantId($terminal)
    {
        $mid = $terminal['gateway_terminal_id'];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config[self::TEST_USERNAME];
        }

        return $mid;
    }

    protected function getWsdlFile()
    {
        $file = __DIR__ . '/Wsdl/cybslive.wsdl.xml';

        if ($this->mode === Mode::TEST)
        {
            $file = __DIR__ . '/Wsdl/cybstest.wsdl.xml';
        }

        return $file;
    }

    /**
     * @codeCoverageIgnore
     * Returns SoapClient Object when mock is disabled
     */
    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['wsdl'], $request['options']);

        $headers = $this->getSoapHeader($request);
        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function getSoapHeader($request)
    {
        $username = $request['auth']['username'];
        $password = $request['auth']['password'];

        // Must understand should be omitted in case of test cases
        $mustUnderstand = ! $this->mock;

        $wsseNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        // $passwordObj->Type = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordTex';

        $wsseAuth = [
            'Username' => (new SoapVar($username, XSD_STRING, null, $wsseNs, null, $wsseNs)),
            'Password' => (new SoapVar($password, XSD_STRING, null, $wsseNs, null, $wsseNs)),
        ];

        $wsseToken = [
            'UsernameToken' => (new SoapVar($wsseAuth, SOAP_ENC_OBJECT, null, $wsseNs, 'UsernameToken', $wsseNs))
        ];

        $wsseTokenSoap = new SoapVar($wsseToken, SOAP_ENC_OBJECT, null, $wsseNs, 'UsernameToken', $wsseNs);

        $wsseHeaderSoap = new SoapVar($wsseTokenSoap, SOAP_ENC_OBJECT, null, $wsseNs, 'Security', $wsseNs);

        $objSoapVarWSSEHeader = new \SoapHeader($wsseNs, 'Security', $wsseHeaderSoap, $mustUnderstand);

        return $objSoapVarWSSEHeader;
    }

    protected function setCybersourceCredentials(&$request)
    {
        $terminal = $this->terminal;

        $auth = array(
            'username' => $terminal['gateway_merchant_id'],
            'password' => $terminal['gateway_secure_secret']
        );

        if ($this->mode === Mode::TEST)
        {
            $auth = array(
                'username' => $this->config[self::TEST_MERCHANT_ID],
                'password' => $this->config[self::TEST_MERCHANT_SECRET]
            );
        }

        $request['options']['auth'] = [$auth['username'], $auth['password']];
    }

    // Logging

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        unset($request['content']['card']);
        unset($request['card']);
        unset($request['auth']);

        $this->trace->info($traceCode,
            [
                'request'    => $request,
                'gateway'    => 'cybersource',
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function xmlToArray($data)
    {
        $xml_values = simplexml_load_string($data);

        return json_decode(json_encode($xml_values), true);
    }

    protected function isSequentialArray($array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    // Exception handling

    /**
     * @param \SoapFault $sf
     * @throws Exception\GatewayTimeoutException
     * @throws Exception\RuntimeException
     */
    protected function handleSoapFault(SoapFault $sf, $errMsg, $safeRetry = false)
    {
        if (Utility::checkSoapTimeout($sf) === true)
        {
            throw new Exception\GatewayTimeoutException(
                        $sf->getMessage(), $sf, $safeRetry);
        }

        throw new Exception\RuntimeException(
            $errMsg, null, $sf);
    }

    protected function checkErrorsAndThrowException(array $response, $code = null, $desc = null)
    {
        $reasonCode = $response[F::REASON_CODE];

        $code = $code ?: ResponseCode::getMappedCode($reasonCode);
        $desc = $desc ?: ResponseCode::getDescription($reasonCode);

        throw new Exception\GatewayErrorException(
                $code, $reasonCode, $desc);
    }

    protected function validateCallbackGatewayFields(array $input)
    {
        try
        {
            (new JitValidator)->rules($this->bankAcsResponseRules)
                              ->input($input['gateway'])
                              ->strict(false)
                              ->validate();
        }
        catch (Exception\RecoverableException $e)
        {
            $this->trace->info(
                TraceCode::GATEWAY_CALLBACK_EMPTY,
                [
                    'gateway'       => 'cybersource',
                    'gateway_input' => $input['gateway'],
                    'payment_id'    => $input['payment']['id']
                ]
            );

            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function validateXidIfApplicable($gatewayPayment, $payerAuthValidateReply)
    {
        $expectedXid = $gatewayPayment->getXid();

        $actualXid = $payerAuthValidateReply[F::XID] ?? null;

        if (($actualXid !== null) and
            ($actualXid !== $expectedXid))
        {
            throw new Exception\LogicException(
                'Invalid XID given',
                null,
                [
                    'payment_id'   => $gatewayPayment->getPaymentId(),
                    'expected_xid' => $expectedXid,
                    'actual_xid'   => $actualXid,
                ]);
        }
    }

    protected function getDynamicMerchantDescription($merchant)
    {
        $billingLabel = $merchant->getBillingLabel();

        $label = preg_replace('/[^a-zA-Z0-9 ]/', '', $billingLabel);

        if (empty($label) === true)
        {
            $label = 'Razorpay Payments';
        }

        return str_limit($label, 19);
    }

    protected function fixParesIfRequired(&$input)
    {
        $input['gateway']['PaRes'] = str_replace(["\n", "\r"], "", $input['gateway']['PaRes']);
    }

    /**
     * @codeCoverageIgnore
     * @incomplete Optimize callback response verification
     */
    protected function validateParesStatus(array $input)
    {
        $PaRes = $input['gateway'][F::PA_RES];

        $PaRes = base64_decode($PaRes);
        $PaRes = gzinflate(substr($PaRes, 2));

        $PaResObject = simplexml_load_string($PaRes);
        $PaRes = json_decode(json_encode($PaResObject), true);

        if ((isset($PaRes['Message']['PaRes']['TX']['status']) === true) and
            ($PaRes['Message']['PaRes']['TX']['status'] === 'Y'))
        {
            $this->trace->info(TraceCode::GATEWAY_CALLBACK_PARES,
                [
                    'gateway' => 'cybersource',
                    'PaResStatus' => $PaRes['Message']['PaRes']['TX']['status']
                ]);
        }
    }
}
