<?php

namespace RZP\Gateway\Cybersource;

use Cache;
use Config;
use SoapVar;
use SoapFault;
use SoapClient;
use Carbon\Carbon;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Mpi;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Gateway\GooglePay;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Cybersource\Fields as F;
use RZP\Gateway\Cybersource\Entity as E;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use Base\AuthorizeFailed;

    const CACHE_KEY = 'cybersource_%s_card_details';

    const CARD_CACHE_TTL = 15;

    // Request timeout limit in seconds
    const TIMEOUT = 60;

    const TEST_MERCHANT_ID       = 'test_merchant_id';
    const TEST_MERCHANT_SECRET   = 'test_merchant_secret';
    const TEST_USERNAME          = 'test_username';
    const TEST_PASSWORD          = 'test_password';
    const AUTHENTICATION_FAILED  = 'Authentication Failed';


    protected $bankAcsResponseRules = [
        'PaRes'     => 'required',
        'MD'        => 'required',
        'PaReq'     => 'sometimes'
    ];

    protected $gateway = 'cybersource';

    protected $secureCacheDriver;

    protected $eci;
    protected $map = [
        'gateway_reference_id1'     => 'ref',
        'gateway_reference_id2'     => 'gatewayTransactionId',
        'gateway_reference_id3'     => 'authorizationCode',
        'rrn'                       => 'receiptNumber',
        'enrollment_status'         => 'veresEnrolled',
        'authentication_status'     => 'pares_status',
        'avs_code'                  => 'avsCode',
        'cv_code'                   => 'cvCode',
        'processor_code'            => 'merchantAdviceCode',
        'status'                    => 'status',
        'payment_id'                => 'payment_id',
        'commerce_indicator'        => 'commerce_indicator',
        'eci'                       => 'eci',
        'reason_code'               => 'reason_code',
        'xid'                       => 'xid',
        'cavv'                      => 'cavv',
        'refund_id'                 => 'refund_id',
        'refundAmount'              => 'amount',
        'received'                  => 'received',
        'processorResponse'         => 'processorResponse',
        'card_category'             => 'cardCategory',
        'card_group'                => 'cardGroup',
    ];

    protected $actionVersion = [
        Action::VERIFY              => 'v1',
        Action::VERIFY_REFUND       => 'v2',
        Base\Action::VERIFY_REFUND  => 'v2',
    ];

    protected function getVersionForAction($input, $action)
    {
        if ((empty($input['terminal']['gateway_secure_secret2']) === false) and
            (empty($input['terminal']['gateway_access_code']) === false))
        {
            if (isset($this->actionVersion[$action]) === true)
            {
                return $this->actionVersion[$action];
            }
        }

        return 'v1';
    }

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->secureCacheDriver = $this->getDriver($input);
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if ((isset($value) === true) and
                ($value !== '') and
                (isset($map[$key])))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    public function syncGatewayTransactionDataFromCps(array $attributes, array $input)
    {
        $gatewayEntity = $this->repo->findByPaymentIdAndAction($attributes[Entity::PAYMENT_ID], $input[Entity::ACTION]);

        if (empty($gatewayEntity) === true)
        {
            $gatewayEntity = $this->createGatewayPaymentEntity($attributes, $input);
        }

        $gatewayEntity->setAction($input[Entity::ACTION]);

        $this->updateGatewayPaymentEntity($gatewayEntity, $attributes, false);
    }

    protected function mapInReverseWay($gatewayPayment)
    {
        $this->map = array_flip($this->map);

        $attr = $this->getMappedAttributes($gatewayPayment->toArrayAdmin());

        $this->map = array_flip($this->map);

        return $attr;
    }

    public function authorize(array $input)
    {
        parent::action($input, Base\Action::AUTHENTICATE);

        // directly send authorize request for 2nd recurring payment
        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            parent::action($input, 'pay_init');

            $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

            return $this->sendMozartRequest($input);
        }

        if ($this->isMotoTransactionRequest($input) === true)
        {
            parent::action($input, 'pay_init');

            $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

            return $this->sendMozartRequest($input);
        }

        $authenticationGateway = $this->decideAuthenticationGateway($input);

        switch ($authenticationGateway)
        {
            case Payment\Gateway::MPI_BLADE:
            case Payment\Gateway::MPI_ENSTAGE:
                $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

                if ($authResponse !== null)
                {
                    $this->persistCardDetailsTemporarily($input);

                    return $authResponse;
                }

                $this->mpiEntity = $this->app['repo']
                                        ->mpi
                                        ->findByPaymentIdAndAction($input['payment']['id'], Base\Action::AUTHORIZE);

                $this->validateEci($input, $this->mpiEntity);

                return $this->authorizeNotEnrolled($input);

            case Payment\Gateway::GOOGLE_PAY:
                $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

                return $authResponse;

            default:
                // send enroll request to check status of enrollment of card.
                parent::action($input, 'authenticate_init');

                $input['gateway']['payment'] = [
                    'callbackUrl' => $input['callbackUrl'],
                ];

                $this->app['diag']->trackGatewayPaymentEvent(
                    EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED,
                    $input);

                $request = $this->sendMozartRequest($input);

                $authenticateInit = $this->gatewayPayment;

                $this->app['diag']->trackGatewayPaymentEvent(
                    EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED,
                    $input,
                    null,
                    [
                        'enrolled' => $authenticateInit['veresEnrolled']
                    ]);

                // some unexpected enrollment status. not taking the call to go ahead with pay_init
                if (in_array($authenticateInit['veresEnrolled'], ['Y', 'N'], true) === false)
                {
                    $this->updateGatewayPaymentEntity($this->gatewayPayment, [
                        'action'    => 'authorize',
                        'status'    => Status::AUTHORIZE_FAILED,
                    ], false);

                    throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
                        'enrollment_status:' . $authenticateInit['veresEnrolled'],
                        'Unexpected response',
                        [
                            'payment_id'        => $input['payment']['id'],
                            'reason_code'       => $authenticateInit['reason_code'],
                            'enrollment_status' => $authenticateInit['veresEnrolled'],
                        ],
                        null,
                        Action::AUTHENTICATE,
                        true);
                }

                // enrolled card. return OTP page request.
                if ($request !== null)
                {
                    $this->persistCardDetailsTemporarily($input);

                    return $request;
                }

                $this->validateEci($input, $authenticateInit);

                $this->authorizeNotEnrolled($input);
                break;
        }
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

    public function capture(array $input)
    {
        parent::capture($input);

        $attributes = [
            'status' => 'created',
        ];

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], 'authorize');

        $input['gateway']['pay_init'] = $this->mapInReverseWay($gatewayPayment);

        $this->sendMozartRequest($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        return $this->callback($input);
    }

    public function callback(array $input)
    {
        switch ($input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY])
        {
            case Payment\Gateway::MPI_BLADE:
            case Payment\Gateway::MPI_ENSTAGE:
                parent::callback($input);

                $authResponse = $this->callAuthenticationGateway($input,
                                                        $input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY]);

                $dataForMozart = $this->formatDataForMozart($input, $authResponse);

                $input['gateway'] = $dataForMozart;

                //setting card number & cvv now as it is required in pay_init step
                $this->setCardNumberAndCvv($input);
                break;
            case Payment\Gateway::GOOGLE_PAY:
                parent::callback($input);

                $dataForMozart = $this->formatDataForMozartForTokenization($input);

                $input['gateway'] = $dataForMozart;

                $input['card']['number'] = $input['gateway']['card_number'];
                break;
            default:
                parent::action($input, 'authenticate_verify');

                $input['gateway']['redirect'] = $input['gateway'];

                $authenticateInit = $this->repo->findByPaymentIdAndActionOrFail(
                    $input['payment']['id'], 'authorize');

                $this->gatewayPayment = $authenticateInit;

                $input['gateway']['authenticate_init'] = $this->mapInReverseWay($authenticateInit);

                $authenticateInit = $this->gatewayPayment->toArrayAdmin();

                $this->sendMozartRequest($input);

                $authenticateVerify = $this->gatewayPayment;

                $this->validateEci($input, $authenticateVerify);

                $this->validateXid($authenticateInit, $authenticateVerify);

                $input['gateway']['authenticate_verify'] = $this->mapInReverseWay($authenticateVerify);

                $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHENTICATION_PROCESSED, $input);

                //setting card number & cvv now as it is required in pay_init step
                $this->setCardNumberAndCvv($input);
                break;
        }

        // callback data verified. now send actual authorize request
        parent::action($input, 'pay_init');

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $this->sendMozartRequest($input);

        $acquirerData = $this->getAcquirerData($input, $this->gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function formatDataForMozart($input, $response)
    {
        $verifyContent['commerce_indicator']     = $this->getCommerceIndicator($input, $response);
        $verifyContent['authentication_status']  = $response[Mpi\Base\Entity::STATUS];
        $verifyContent['eci']                    = (int) $response[Mpi\Base\Entity::ECI];
        $verifyContent['xid']                    = $response[Mpi\Base\Entity::XID];
        $verifyContent['cavv']                   = $response[Mpi\Base\Entity::CAVV];

        $initContent['enrollment_status']        = $response[Mpi\Base\Entity::ENROLLED];

        $data['authenticate_verify']             = $verifyContent;
        $data['authenticate_init']               = $initContent;

        return $data;
    }

    public function formatDataForMozartForTokenization($input)
    {
        $verifyContent['commerce_indicator']     = $this->getCommerceIndicatorForTokenFlow($input);
        $verifyContent['eci']                    = $this->getEciForTokenFlow($input);
        $verifyContent['cavv']                   = $input['gateway'][GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS]
                                                    [GooglePay\RequestFields::CRYPTOGRAM_3DS];
        $verifyContent['xid']                    = $verifyContent['cavv'];
        $verifyContent['network']                = strtolower($input['gateway']['network']);

        $data['authenticate_verify']             = $verifyContent;
        $data['card_number']                     = $input['gateway'][GooglePay\RequestFields::TOKEN][GooglePay\RequestFields::METHOD_DETAILS]
                                                    [GooglePay\RequestFields::CARD_NUMBER];
        return $data;
    }

    protected function getCommerceIndicatorForTokenFlow($input)
    {
        $network = strtolower($input['gateway']['network']);

        switch ($network)
        {
            Case 'visa':
                return 'internet';
            Case 'mastercard':
            default:
                return 'spa';
        }
    }

    protected function getEciForTokenFlow($input)
    {
        $network = strtolower($input['gateway']['network']);

        switch ($network)
        {
            Case 'visa':
                return '7';
            Case 'mastercard':
            default:
                return '2';
        }
    }

    protected function getCommerceIndicator($input, $response)
    {
        if (isset($response[Mpi\Base\Entity::ECI]))
        {
            $eci = $response[Mpi\Base\Entity::ECI];
        }
        else
        {
            $eci = '7';
        }

        $commerceIndicatorMap = [
            Card\Network::VISA => [
                '5'  => 'vbv',
                '05' => 'vbv',
                '6'  => 'vbv_attempted',
                '06' => 'vbv_attempted',
                '7'  => 'internet',
                '07' => 'internet',
            ]
        ];

        switch($input['card'][Card\Entity::NETWORK_CODE])
        {
            case Card\Network::VISA:
                if (isset($commerceIndicatorMap[Card\Network::VISA][$eci]) === true)
                {
                    return $commerceIndicatorMap[Card\Network::VISA][$eci];
                }
                else
                {
                    return '';
                }
            case Card\Network::MC:
            case Card\Network::MAES:
                return 'spa';
            case Card\Network::AMEX:
                return 'aesk';
            case Card\Network::RUPAY:
                return 'rpy';
            default:
                return '';
        }
    }

    protected function decideAuthenticationGateway($input)
    {
        if (empty($input['authenticate']['gateway']) === false)
        {
            $authenticationGateway = $input['authenticate']['gateway'];
        }
        else
        {
            $authenticationGateway = Payment\Gateway::CYBERSOURCE;
        }

        return $authenticationGateway;
    }

    protected function authorizeNotEnrolled($input)
    {
        // not enrolled card. send authorize request using enroll response.
        parent::action($input, 'pay_init');

        if (isset($this->mpiEntity) == true)
        {
            $input['gateway']['authenticate_init'] = $this->mapInReverseWay($this->mpiEntity);

            $input['gateway']['authenticate_init']['commerce_indicator'] =
                $this->getCommerceIndicator($input, $this->mpiEntity);
        }
        else
        {
            $input['gateway']['authenticate_init'] = $this->mapInReverseWay($this->gatewayPayment);
        }

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $this->sendMozartRequest($input);
    }

    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        return $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);
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

    protected function getPaymentToVerify(Verify $verify)
    {
        /** @var Repository $cybsRepo */
        $cybsRepo = $this->repo;

        /** @var Entity $gatewayPayment */
        $gatewayPayment = $cybsRepo->findByPaymentIdAndAction($verify->input['payment']['id'],
            'authorize');

        $verify->payment = $gatewayPayment;

        return $verify->payment;
    }


    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $verify->verifyResponseContent = $this->sendMozartRequest($input);

        $verify->verifyResponse = null;

        $verify->verifyResponseBody = null;

        return $verify->verifyResponseContent;
    }

    protected function checkGatewaySuccess($verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($content['success']) === true) and
            ($content['success'] === true))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function verifyPayment(Verify $verify)
    {
        $this->setVerifyStatus($verify);

        $verify->payment = $this->saveVerifyResponseIfNeeded($verify);
    }

    protected function saveVerifyResponseIfNeeded($verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        unset($content['data']['_raw']);

        return $this->updateGatewayPaymentEntity($gatewayPayment, $content['data']);
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MISMATCH;

        if ($verify->apiSuccess === $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->status = $status;
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


    protected function validateEci($input, $gatewayPayment)
    {
        $eci = (int) $gatewayPayment['eci'] ?: 7;
        switch ($input['card'][Card\Entity::NETWORK_CODE])
        {
            case Card\Network::VISA:

                if ($eci === 7)
                {
                    $desc = 'ECI value shouldn\'t be 7.';
                }
                break;
            case Card\Network::MC:

                if (($eci === 7) or ($eci === 0))
                {
                    $desc = 'ECI value shouldn\'t be 7 or 0. ECI: ' . $eci;
                }
                break;
        }

        if (isset($desc) === true)
        {
            $this->updateGatewayPaymentEntity($this->gatewayPayment, [
                'action'    => 'authorize',
                'status'    => Status::AUTHORIZE_FAILED,
            ], false);

            throw new Exception\LogicException(
                $desc,
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                $eci);
        }
    }

    protected function validateXid($authenticateInit, $authenticateVerify)
    {
        if ($authenticateInit['xid'] !== $authenticateVerify['xid'])
        {
            throw new Exception\LogicException(
                'xid mismatch',
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                [
                    'expected_xid'  => $authenticateInit['xid'],
                    'actual_xid'    => $authenticateVerify['xid'],
                ]);
        }
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

            $this->createGatewayRefundEntity($gatewayAttributes, $input, $this->action);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }

            return [
                Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
            ];
        }
        catch (SoapFault $exception)
        {
            $this->handleSoapFault($exception, 'Refund failed');
        }
    }

    protected function getGatewayData($refundFields)
    {
        if ((is_array($refundFields) === true) and (empty($refundFields) === false))
        {
            return [
                F::REQUEST_ID              => $refundFields[F::REQUEST_ID] ?? null,
                F::DECISION                => $refundFields[F::DECISION] ?? null,
                F::REASON_CODE             => stringify($refundFields[F::REASON_CODE] ?? null),
                F::REFUND_DATETIME         =>
                    $refundFields[Fields::CC_CREDIT_REPLY][F::REFUND_DATETIME] ?? null,
                F::REQUEST_DATETIME        =>
                    $refundFields[Fields::CC_CREDIT_REPLY][F::REQUEST_DATETIME] ?? null,
            ];
        }
        return [];
    }

    protected function getGatewayVerifyData($verifyRefundFields)
    {
        if ((is_array($verifyRefundFields) === true) and (empty($verifyRefundFields) === false))
        {
            return [
                F::ATTRIBUTES              => json_encode($verifyRefundFields[Fields::ATTRIBUTES] ?? null),
                F::R_CODE                  => $verifyRefundFields[Fields::R_CODE] ?? null,
                F::R_FLAG                  => $verifyRefundFields[Fields::R_FLAG] ?? null,
                F::R_MSG                   => $verifyRefundFields[Fields::R_MSG] ?? null,
            ];
        }
        return [];
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

            $this->createGatewayRefundEntity($gatewayAttributes, $input, $this->action);

            if ($response[F::REASON_CODE] !== Result::SUCCESS)
            {
                $this->checkErrorsAndThrowException($response);
            }

            return [
                Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
            ];
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
     * @return array
     * @throws Exception\LogicException
     */
    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        if ((empty(trim($input['terminal']['gateway_secure_secret2'])) === false) and
            (empty(trim($input['terminal']['gateway_access_code'])) === false))
        {
            return $this->verifyRefundMozart($input);
        }
        else
        {
            return $this->verifyRefundApi($input);
        }
    }

    protected function verifyRefundMozart(array $input)
    {
        $scroogeResponse = new Base\ScroogeResponse();

        $content = $this->sendMozartRequest($input, false);

        $rawResponse=$content['data']['_raw'] ?? '';

        $decodedResponse=json_decode($rawResponse, true);

        $rmsg=$decodedResponse['response']['rmsg'] ?? '';

        if ((isset($content['success']) === true) and
            ($content['success'] === true))
        {
            $attributes = $this->getRefundAttributesFromMozartVerify($content['data']);

            $status = $attributes['status'];

            if ($status == 'reversed')
            {
                $action = 'reverse';
            }
            else if ($status == 'refunded')
            {
                $action = 'refund';
            }
            else
            {
                throw new Exception\LogicException(
                    'Unexpected status',
                    ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
                    [
                        Payment\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($content['data']),
                        Payment\Gateway::GATEWAY_KEYS             => ['received_status' => $status]
                    ]);
            }

            $gatewayEntity = $this->repo->findByRefundId($input['refund']['id']);

            if ($gatewayEntity !== null)
            {
                $gatewayEntity->setStatus($status);

                $this->repo->saveOrFail($gatewayEntity);
            }
            else
            {
                $this->createGatewayRefundEntity($attributes, $input, $action);
            }

            return $scroogeResponse->setSuccess(true)
                                   ->setGatewayVerifyResponse($content['data']['_raw'])
                                   ->setGatewayKeys($content['data'])
                                   ->toArray();
        }
        else if($rmsg === self::AUTHENTICATION_FAILED)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_AUTHENTICATION_FAILED)
                                   ->setGatewayVerifyResponse($content['data']['_raw'])
                                   ->setGatewayKeys($content['data'])
                                   ->toArray();
        }
        else
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->setGatewayVerifyResponse($content['data']['_raw'])
                                   ->setGatewayKeys($content['data'])
                                   ->toArray();
        }
    }

    protected function verifyRefundApi(array $input)
    {
        $scroogeResponse = new Base\ScroogeResponse();

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
                    $action = Action::REVERSE;
                }
                else if ($refundReply[0]['@attributes'][F::NAME] === 'ics_credit')
                {
                    $status = Status::REFUNDED;
                    $action = Action::REFUND;
                }
                else
                {
                    throw new Exception\LogicException(
                        'Unexpected status',
                        ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
                        [
                            Payment\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($refundReply),
                            Payment\Gateway::GATEWAY_KEYS             =>
                                ['received_status' => $refundReply[0]['@attributes'][F::NAME]]
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

                    $this->createGatewayRefundEntity($attributes, $input, $action);
                }

                return $scroogeResponse->setSuccess(true)
                    ->setGatewayVerifyResponse($content)
                    ->setGatewayKeys($this->getGatewayVerifyData($refundReply[0]))
                    ->toArray();
            }
        }

        return $scroogeResponse->setSuccess(false)
            ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
            ->setGatewayVerifyResponse($content)
            ->setGatewayKeys($this->getGatewayVerifyData($content))
            ->toArray();
    }

    protected function getRefundAttributesFromMozartVerify(array $request)
    {
        return [
            E::REF           => $request['gateway_reference_id1'],
            E::REASON_CODE   => $request['reason_code'],
            E::RECEIVED      => $request['received'],
            E::STATUS        => $request['status'],
        ];
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

    protected function fetchRefundGatewayReplyFromContent($content)
    {
        return $this->fetchGatewayReplyFromContent($content, ['ics_credit', 'ics_auth_reversal']);
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
                if (empty($request[F::APPLICATION_REPLIES][F::APPLICATION_REPLY]) === true)
                {
                    continue;
                }

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

    /**
     * Sets dummy billing info as AVS is not
     * supported in India
     */
    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];

        $paymentRepo = $this->repo->repo->payment;

        $payment = $paymentRepo->findOrFail($paymentId);

        $amount    = $input['payment']['amount'] ?? $payment->getAmount();
        $currency  = $input['payment']['currency'] ?? $payment->getCurrency();
        $acquirer  = $input['terminal']['gateway_acquirer'] ?? $payment->terminal->getGatewayAcquirer();

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

    protected function createGatewayRefundEntity($attributes, $input, $action)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId    = $input['payment']['id'];
        $refundId     = $input['refund']['id'];
        $refundAmount = $input['refund']['amount'];
        $currency     = $input['refund']['currency'];
        $acquirer     = $input['terminal']['gateway_acquirer'];

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setRefundId($refundId);

        $gatewayPayment->setAmount($refundAmount);

        $gatewayPayment->setCurrency($currency);

        $gatewayPayment->setAction($action);

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
        try
        {
            $xml = simplexml_load_string(trim($data));

            return json_decode(json_encode($xml), true);
        }
        catch (\ErrorException $e)
        {
            // We know that if gateway returns HTML message, it always
            // because of server failure at their end with message
            if (str_contains($data, '<!DOCTYPE HTML') === true)
            {
                throw new Exception\GatewayTimeoutException('An error has occurred. Please try again.' .
                    'If you continue to receive an error, please contact Customer Support.');
            }

            // Otherwise it a new error we need to debug manually
            throw new Exception\GatewayRequestException($e->getMessage());
        }
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

    protected function checkErrorsAndThrowException(array $response, $code = null, $desc = null, $action = null)
    {
        $reasonCode = $response[F::REASON_CODE];

        $code = $code ?: ResponseCode::getMappedCode($reasonCode);
        $desc = $desc ?: ResponseCode::getDescription($reasonCode);

        throw new Exception\GatewayErrorException(
            $code,
            $reasonCode,
            $desc,
            [
                Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
            ],
            null,
            $action);
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
}
