<?php

/**
 * This file implements the interactions with HDFC gateway
 * via the api of FSF gateway (which HDFC uses) and which
 * we actually interact with.
 *
 * The payment flow for a purchase/auth payment
 * in few simple words goes like this:
 * 1. We send an enroll request for a card
 * 2. For certain cards (probably cc) we get a 'NOT ENROLLED' response back
 *    2.1. For these cards, we send auth request and complete the payment.
 * 3. For certain cards (probably dc) we get an 'ENROLLED' response back
 *    3.1. For these cards, we send a request to acquiring bank (hdfc)
 *         ACS where the customer enters card fields etc. and the bank
 *         redirects to a url provided by us.
 *    3.2. From the redirected url, we send auth request and
 *         complete the payment.
 *
 * Note: Refer to HDFC FSF Payment Gateway Integration
 *       Version 4.0 pdf document
 *
 */

namespace RZP\Gateway\Hdfc;

use App;
use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Gateway\Hdfc\Payment;
use RZP\Models\Payment\AuthType;
use RZP\Models\Payment\RecurringType;
use RZP\Models\Payment as PaymentModel;
use RZP\Gateway\Base\Action as BaseAction;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Terminal\Capability as TerminalCapability;

class Gateway extends Base\Gateway
{
    use Payment\Enroll;
    use Payment\Authorize;
    use Payment\Support;
    use Payment\Inquiry;
    use Base\CardCacheTrait;

    const CACHE_KEY = 'hdfc_fss_%s_card_details';
    const CARD_CACHE_TTL = 20;

    protected $secureCacheDriver;

    protected $gateway = 'hdfc';

    /**
     * App payment id
     * @var string
     */
    protected $id;

    /**
     * Curent Hdfc Payment Model
     * @var Hdfc\Entity
     */
    protected $model = null;

    /**
     * If during the payment flow, we detect an
     * error, or the payment fails for any reason,
     * then this variable is set to true.
     * @var boolean
     */
    protected $error = false;

    /**
     * The gateway terminal on which to make
     * the request
     * @var array
     */
    protected $terminal;

    const TIMEOUT = 60;

    const VERIFY_TIMEOUT = 60;

    protected $secondDebitRecurringFlag = null;

    /**
     * Parameters required to construct request
     * for enrolling a card
     * @var array
     */
    protected $enrollRequest = [
        'url' => Hdfc\Urls::ENROLL_URL,
        'type' => 'enroll',
        'fields' => [
            'trackid', 'member', 'card', 'expmonth', 'expyear', 'cvv2',
            'amt', 'action', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5'
            ],
        'xml' => '',
        'headers' => ['Content-Type' => 'text/xml'],
        'data' => [],
        'options' => [
            'timeout' => 10,
        ]];

    /**
     * Response received after sending enroll card request
     * @var array
     */
    protected $enrollResponse = [
       'fields' => [
            'result', 'eci', 'paymentid', 'trackid', 'PAReq', 'url', 'error_text'
            ],
        'fieldsEnrolled' => [
            'result', 'url', 'PAReq', 'paymentid', 'trackid', 'udf1', 'udf2',
            'udf3', 'udf4', 'udf5'
            ],
        'fieldsNotEnrolled' => [
            'result', 'PAReq', 'paymentid', 'trackid', 'udf1', 'udf2', 'udf3',
            'udf4', 'udf5'
            ],
        'type' => 'enroll',
        'xml' => '',
        'data' => array(),
        'error' => null
    ];

    /**
     * The assoc array is used to construct auth
     * request for debit cards
     * @var array
     */
    protected $authEnrolledRequest = [
        'url'       => Hdfc\Urls::AUTH_ENROLLED_URL,
        'type'      => 'auth_enrolled',
        'fields'    => ['paymentid', 'PaRes'],
        'headers'   => ['Content-Type:text/xml'],
        'xml'       => '',
        'data'      => []
    ];

    protected $authEnrolledResponse = [
        'fields'    => [
            'result', 'auth', 'ref', 'avr', 'postdate', 'paymentid', 'tranid',
            'trackid', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'error_text', 'authRespCode'
            ],
        'type'      => 'auth_enrolled',
        'xml'       => '',
        'data'      => [],
        'error'     => null
    ];

    /**
     * The assoc array is used to constructing
     * auth request for enrolled card cases
     * @var array
     */
    protected $authNotEnrolledRequest = [
        'url' => Hdfc\Urls::AUTH_NOT_ENROLLED_URL,
        'type' => 'auth_not_enrolled',
        'fields' => [
            'trackid', 'member', 'card', 'expmonth', 'expyear', 'cvv2', 'action',
            'zip', 'addr', 'amt', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5'
            ],
        'headers' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => []
        ];

    /**
     * Response received after sending authNotEnrolledRequest
     * @var array
     */
    protected $authNotEnrolledResponse = [
        'fields' => [
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid',
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'amt', 'error_text', 'authRespCode'
            ],
        'type' => 'auth_not_enrolled',
        'xml' => '',
        'data' => [],
        'error' => null
    ];

    protected $authSecondRecurringRequest = [
        'url' => Hdfc\Urls::AUTH_NOT_ENROLLED_URL,
        'type' => 'auth_second_recurring',
        'fields' => [
            'trackid', 'member', 'card', 'expmonth', 'expyear', 'action',
                'amt', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5'
        ],
        'headers' => ['Content-Type:text/xml'],
        'xml' => '',
        'data' => []
    ];

    /**
     * Response received after sending authSecondRecurringRequest
     * @var array
     */
    protected $authSecondRecurringResponse = [
        'fields' => [
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid',
             'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'amt',
            ],
        'type' => 'auth_second_recurring',
        'xml' => '',
        'data' => [],
        'error' => null
    ];

    protected $preAuthorizeRequest = [
        'url' => Hdfc\Urls::PRE_AUTH_URL,
        'type' => 'pre_authorization',
        'fields' => [
            'id', 'password', 'action', 'amt', 'currencycode', 'trackid', 'card', 'expmonth',
            'expyear', 'cvv2', 'type', 'member', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5',
        ],
        'headers' => ['Content-Type:text/xml'],
        'xml' => '',
        'data' => []
    ];

    /**
     * Response received after sending preAuthorizeRequest
     * @var array
     */
    protected $preAuthorizeResponse = [
        'fields' => [
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid',
             'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'amt',
            ],
        'type' => 'pre_authorization',
        'xml' => '',
        'data' => [],
        'error' => null
    ];

    protected $debitPinAuthenticationRequest = [
        'url' => Hdfc\Urls::SUPPORT_PAYMENT_URL,
        'type' => 'debit_pin_authentication',
        'fields' => [
            'id', 'password', 'action', 'amt', 'currencycode', 'trackid', 'card', 'expmonth',
            'expyear', 'type', 'member', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5',
        ],
        'headers' => ['Content-Type:text/xml'],
        'xml' => '',
        'data' => []
    ];

    protected $debitPinAuthenticationResponse = [
        'fields' => [
            'paymentId', 'paymenturl', 'result',
        ],
        'type' => 'debit_pin_authentication',
        'xml' => '',
        'data' => [],
        'error' => null
    ];

    protected $debitPinAuthorizationResponse = [
        'fields' => [
            'paymentid', 'result', 'auth', 'amt', 'ref', 'postdate', 'trackid', 'tranid',
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'authRespCode', 'ErrorText', 'ErrorNo',
            'error_service_tag', 'error_code_tag',
        ],
        'type'  => 'debit_pin_authorization',
        'xml'   => '',
        'data'  => [],
        'error' => null
    ];

    /**
     * The assoc array is used to construct
     * request for refunds/captures
     * @var array
     */
    protected $supportPaymentRequest = [
        'url'       => Hdfc\Urls::SUPPORT_PAYMENT_URL,
        'type'      => '',
        'fields'    => ['action', 'amt', 'member', 'transid', 'trackid', 'udf5'],
        'headers'   => ['Content-Type:text/xml'],
        'xml'       => '',
        'data'      => []];

    protected $supportPaymentResponse = [
        'fields'    => ['result', 'auth', 'ref', 'avr', 'postdate', 'tranid',
                        'trackid', 'payid', 'udf2', 'udf5', 'amt', 'error_text', 'authRespCode'],
        'type'      => '',
        'xml'       => '',
        'data'      => [],
        'error'     => null];

    protected $inquiryRequest = [
        'url'       => Hdfc\Urls::SUPPORT_PAYMENT_URL,
        'fields'    => ['action', 'amt', 'member', 'transid', 'trackid', 'udf5'],
        'type'      => 'inquiry',
        'xml'       => '',
        'data'      => [],
        'error'     => null];

    protected $inquiryResponse = [
        'type' => 'inquiry',
        'fields' => ['result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid', 'amt',
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'authRespCode'],
        'data' => [],
        'xml' => '',
        'error' => null];

    /**
     * The array is used to specify fields that are not to be logged by trace class
     * they are stripped by calling stripSensitive function of this class on the request/response object
     * @var array
     */
    protected $stripFieldsList = [
        'password', 'currencycode', 'id', 'udf1', 'udf2', 'udf3', 'udf4', 'member',
        'card', 'expmonth', 'expyear', 'cvv2', 'PAReq', 'zip', 'addr', 'PaRes', 'number', 'cvv'
    ];

    protected $bankAcsResponseRules = [
        'PaRes'     => 'required',
        'MD'        => 'required|numeric|digits_between:1,19',
        'PaReq'     => 'sometimes'
    ];

    /**
     * Either ENROLLED or NOT_ENROLLED
     * or false for enroll failure.
     * Default is null
     * @var
     */
    protected $enrollStatus = null;

    /**
     * Hdfc data storage repository instance
     * @var Hdfc\Repository
     */
    protected $repo;

    /**
     * Non-fatal exception that will be thrown after internal processing
     * @var Exception
     */
    protected $exception;

    /**
     * Response received from gateway request
     * @var
     */
    protected $response;

    /**
     * Whether the gateway supports authorizing payments.
     * @var boolean
     */
    protected $authorize = true;

    protected $purchase = array(
        Card\Network::MAES,
        Card\Network::RUPAY,
        Card\Network::DICL,
    );

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Hdfc\Repository;
    }

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->secureCacheDriver = $this->getDriver($input);
    }

// ---------------------------Gateway operations -------------------------------

    /**
     * Does card auth
     *
     * @param  array  $input
     * @return mixed
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            return $this->authorizeRecurring($input);
        }

        if ($input['payment']['auth_type'] === AuthType::PIN)
        {
            return $this->authorizeDebitPin($input);
        }

        if ($input['terminal']->getCapability() === TerminalCapability::AUTHORIZE)
        {
            $authenticationGateway = $this->decideAuthenticationGateway($input);

            $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

            if ($authResponse !== null)
            {
                $this->persistCardDetailsTemporarily($input);

                return $authResponse;
            }

            return $this->decideAuthStepAfterEnroll(Payment\Result::NOT_ENROLLED);
        }

        $status = $this->enrollCard($input);

        return $this->decideAuthStepAfterEnroll($status);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        return $this->supportPayment($input, 'refund');
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $this->retryHandler(
            [$this, 'supportPayment'],
            [$input, 'capture'],
            [$this, 'shouldRetry'],
            [$this, 'getMaxRetryCount']);
    }

    protected function shouldRetry($e)
    {
        $baseCheck = parent::shouldRetry($e);

        // These are HDFC internal database/cache errors. We usually receive these when hdfc is unable
        // to process next request (capture/refund) immediately after authorizing the payment. Adding
        // them here ensures that there's some delay and second request succeeds. If we keep getting
        // these errors after retrying, we may have to add some time delay here
        $errorCodes =[
            ErrorCode::CM00030,
            ErrorCode::CM90000,
            ErrorCode::CM90001,
            ErrorCode::CM90002,
            ErrorCode::CM90003,
            ErrorCode::CM90004,
            ErrorCode::CM90005,
            ErrorCode::CM900000,
        ];

        if ($e instanceof Exception\BaseException)
        {
            $hdfcSpecficCheck = in_array($e->getError()->getGatewayErrorCode(), $errorCodes, true);

            return $baseCheck or $hdfcSpecficCheck;
        }

        return $baseCheck;
    }

    /**
     * After card enroll and bank ACS form submission,
     * bank redirects to us with 'MD' field and PaRes.
     * Next step is auth.
     *
     * @param  array  $input
     *
     * @return array
     * @throws Exception\LogicException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $network = $input['card']['network'];

        if ($input['payment']['auth_type'] === AuthType::PIN)
        {
            $context['data'] = $input['gateway'];

            $context['data'] = HDFC\Utility::unsetFields($context['data'],$this->stripFieldsList);

            $this->trace->info(
                TraceCode::GATEWAY_DEBIT_PIN_CALLBACK,
                [
                    'content'     => $context['data'],
                    'gateway'     => $this->gateway,
                    'payment_id'  => $input['payment']['id'],
                    'terminal_id' => $input['terminal']['id'],
                ]);

            $authResponse['data'] = $input['gateway'];
            $authResponse['error'] = [];

            $gatewayPaymentId = $authResponse['data']['paymentid'];

            $this->model = $this->repo->findByGatewayPaymentIdOrFail($gatewayPaymentId);

            $this->verifyDebitPinAuthResponse($authResponse);

            $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
            $actualAmount = number_format($input['gateway']['amt'], 2, '.', '');

            $this->assertAmount($expectedAmount, $actualAmount);

            $this->verifyCallback($input);
        }

        else if ($network === Card\NetworkName::RUPAY)
        {
            $this->trace->info(
                TraceCode::GATEWAY_RUPAY_CALLBACK,
                $input['gateway']);

            $authResponse['data'] = $input['gateway'];
            $authResponse['error'] = [];

            if (empty($authResponse['data']) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The gateway input is empty. This is unexpected.',
                    null,
                    ['network' => $network]);
            }

            $gatewayPaymentId = $authResponse['data']['paymentid'];

            $this->model = $this->repo->findByGatewayPaymentIdOrFail($gatewayPaymentId);

            $this->verifyAuthResponse($authResponse);

            $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
            $actualAmount = number_format($input['gateway']['amt'], 2, '.', '');

            $this->assertAmount($expectedAmount, $actualAmount);

            $this->verifyCallback($input);
        }
        else if ($input['terminal']->getCapability() === TerminalCapability::AUTHORIZE)
        {
            $mpiEntity = $this->app['repo']
                              ->mpi
                              ->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

            $authenticationGateway = $mpiEntity->getGateway() ?: \RZP\Models\Payment\Gateway::MPI_BLADE;

            $input['authentication'] = $this->callAuthenticationGateway($input, $authenticationGateway);

            $this->setCardNumberAndCvv($input);

            $this->postPreAuthRequest($input);
        }
        else
        {
            $this->validateCallbackGatewayFields($input, $network);

            $this->validateParesAndPersistEci($input);

            $this->app['diag']->trackGatewayPaymentEvent(
                EventCode::PAYMENT_AUTHENTICATION_PROCESSED,
                $input);

            $this->id = $input['payment']['id'];

            $this->model = $this->repo->findByGatewayPaymentIdOrFail(
                $input['gateway']['MD']);

            $paymentId = $this->model->getPaymentId();

            if ($this->id !== $paymentId)
            {
                throw new Exception\LogicException(
                    'app payment '. $this->id . ' should be equal to payment id . '. $paymentId);
            }

            $this->postAuthEnrolledRequest($input);
        }

        $acquirerData = $this->getAcquirerData($input, $this->model);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback(array $input)
    {
        parent::verify($input);

        // TODO: remove these after gateway manager driver are fixed

        $this->inquiryRequest['data'] = [];
        $this->inquiryRequest['xml'] = '';
        $this->inquiryRequest['error'] = null;

        $this->inquiryResponse['data'] = [];
        $this->inquiryResponse['xml'] = '';
        $this->inquiryResponse['error'] = null;

        $this->error = false;

        $verify = new Base\Verify($this->gateway, $input);

        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify);

        if (($gatewayPayment === null) and
            ($this->shouldReturnIfPaymentNullInVerifyFlow($verify)))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'payment_id' => $verify->input['payment']['id'],
                    'message'    => 'payment id not found in the gateway database',
                    'gateway'    => $this->gateway
                ]
            );

            return null;
        }

        $this->sendPaymentVerifyRequest($verify);

        $this->verifyPayment($verify);

        if ($verify->gatewaySuccess !== true)
        {
            throw new Exception\LogicException(
                'Data tampering found.');
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        // TODO: remove these after gateway manager driver are fixed

        $this->inquiryRequest['data'] = [];
        $this->inquiryRequest['xml'] = '';
        $this->inquiryRequest['error'] = null;

        $this->inquiryResponse['data'] = [];
        $this->inquiryResponse['xml'] = '';
        $this->inquiryResponse['error'] = null;

        $this->error = false;

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * HDFC gateway does not provide void
     */
    public function void(array $input)
    {
        throw new Exception\LogicException(
            'Hdfc gateway does not support voids');
    }

    public function verifyInternalRefund(array $input)
    {
        $isRefundRequired = $this->isRefundRequired($input);

        if ($isRefundRequired)
        {
            $this->refund($input);

            // Verified and refund performed
            return false;
        }
        else
        {
            // Verified to not require any refund
            return true;
        }
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
        $refundEntityRefundAmount = (int) ($refundEntity->getAmount() * 100);

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input' => $input,
                'refund_payment_id' => $refundEntityPaymentId,
                'gateway_refund_amount' => $refundEntityRefundAmount
            ]);

        if (($refundEntityPaymentId !== $paymentId) or
            ($refundEntityRefundAmount !== $refundAmount))
        {
            return false;
        }

        return true;
    }

    public function verifyCapture(array $input)
    {
        $paymentId = $input['payment']['id'];

        $gatewayCaptured = $this->isCapturedSuccessfully($paymentId);

        $this->trace->info(
            TraceCode::GATEWAY_HDFC_CAPTURED,
            [
                'payment_id' => $input['payment']['id'],
                'captured'   => $gatewayCaptured
            ]);

        return $gatewayCaptured;
    }

// ----------------------Gateway operations end --------------------------------

    protected function validateCallbackGatewayFields($input, $network)
    {
        if ($network === 'RuPay')
        {
            return;
        }

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
                TraceCode::GATEWAY_HDFC_CALLBACK_EMPTY,
                [
                    'gateway_input' => $input['gateway'],
                    'payment_id' => $input['payment']['id']
                ]);

            throw new Exception\GatewayErrorException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED, null, null, [], null, Base\Action::AUTHENTICATE);
        }
    }

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        $this->setTerminalInRequest($request);

        // Create xml from the fields
        $request['content'] = Utility::createXml($request['data']);

        $domain = $this->getDomainUrl();

        $request['url'] = $domain . $request['url'];

        $this->requestVar = $request;

        try
        {
            // send the request and get response
            $response['response'] = $this->postRequest($request);
        }
        catch (Exception\GatewayRequestException $e)
        {
            $this->trace->traceException($e);

            // For verify we should throw exception as is.
            if ($this->action === BaseAction::VERIFY)
            {
                throw $e;
            }

            $this->error = true;

            $response['content'] = '';

            $curlErrorMessage = strtolower($e->getData()['message']);

            if ($e instanceof Exception\GatewayTimeoutException)
            {
                Hdfc\ErrorHandler::setTimeoutError($response, $curlErrorMessage);
            }
            else
            {
                Hdfc\ErrorHandler::setRequestError($response, $curlErrorMessage);
            }

            return;
        }

        $response['xml'] = $response['response']->body;

        $this->checkResponseStatusCode($response);

        //
        // Content type is not returned correctly in the
        // iPay integration
        //
        // if ($this->error === false)
        // {
        //     $this->checkResponseContentType($response);
        // }

        if ($this->error === false)
        {
            Utility::parseResponseXml($response);

            $this->checkResponseErrorCode($response);
        }
    }

    protected function getDomainUrl()
    {
        $payment = $this->input['payment'];

        // HDFC FSS Migration
        $migrationTimeStampSoft         = 1579091400; // 15 Jan, 2020. 18:00:00
        $migrationTimeStampHard         = 1579242600; // 17 Jan, 2020. 12:00:00
        $paymentCreationTimeStamp       = $payment['created_at'];
        $currentTimeStamp               = Carbon::now(Timezone::IST)->getTimestamp();

        if (($paymentCreationTimeStamp > $migrationTimeStampSoft) or
            ($currentTimeStamp > $migrationTimeStampHard))
        {
            return ($this->isLiveMode() === true) ? Urls::LIVE_DOMAIN_V3 : Urls::TEST_DOMAIN_V3;
        }

        return ($this->isLiveMode() === true) ? Urls::LIVE_DOMAIN_V2 : Urls::TEST_DOMAIN_V2;
    }

    protected function checkForServiceUnavailability($response)
    {
        $body = $response['response']->body;

        return (strpos($body, 'Service Unavailable') !== false);
    }

    protected function checkResponseStatusCode(& $response)
    {
        $statusCode = (int) $response['response']->status_code;

        if ($statusCode >= 500)
        {
            if ($this->checkForServiceUnavailability($response) === true)
            {
                Hdfc\ErrorHandler::setTimeoutError($response);
            }
            else
            {
                Hdfc\ErrorHandler::setGatewayWrongStatusCode($response, $statusCode);
            }

            $this->error = true;
        }
    }

    protected function checkResponseContentType(& $response)
    {
        $contentType = $response['response']->headers['content-type'];

        if (strpos($contentType, 'application/xml') === false)
        {
            Hdfc\ErrorHandler::setGatewayWrongContentType($response, $contentType);

            $this->trace->info(
                TraceCode::GATEWAY_VERIFY_INVALID_HEADER,
                ['response' => $response['response']['headers']]);

            $this->error = true;
        }
    }

    protected function setTerminalInRequest(array & $request)
    {
        $terminal = $this->terminal;

        if ($terminal['gateway'] !== 'hdfc')
        {
            throw new Exception\InvalidArgumentException(
                'hdfc gateway: wrong terminal supplied. Gateway: ' . $terminal['gateway']);
        }

        $request['data']['id'] = $terminal['gateway_terminal_id'];
        $request['data']['password'] = $terminal['gateway_terminal_password'];

        // For TEST mode, replace any random terminal given with
        // hdfc test terminal
        if ($this->mode === Mode::TEST)
        {
            $request['data']['id'] = $this->config['test_terminal_id'];
            $request['data']['password'] = $this->config['test_terminal_pwd'];
        }

        $payment = $this->input['payment'];

        if(($this->mode === Mode::TEST) and
            ((isset($payment['auth_type']) === true and $payment['auth_type'] === AuthType::PIN)))
        {
            $request['data']['id'] = $this->config['test_debit_pin_terminal_id'];
            $request['data']['password'] = $this->config['test_debit_pin_terminal_password'];
        }
    }

    protected function checkResponseErrorCode($response)
    {
        //
        // This step is very crucial for deciding future steps in
        // payment flow.
        //
        // For any operation, whether enroll, auth or support,
        // the success or failure at different stages is decided on the basis of
        // $this->error variable.
        // Be careful before making any change around here.
        //
        if (isset($response['error']['code']))
        {
            $this->error = true;
        }
    }

    public function postRequest($request)
    {
        $request['options'] = $this->getRequestOptions($request);

        $this->response = $this->sendGatewayRequest($request);

        $this->processResponse($this->response);

        return $this->response;
    }

    protected function getRequestOptions($request)
    {
        $options['verify'] = false;
        $options['timeout'] = $request['options']['timeout'] ?? $this->getTimeout();

        return $options;
    }

    protected function getTimeout()
    {
        // Increasing timeout for verify Request
        if ($this->action === BaseAction::VERIFY)
        {
            return static::VERIFY_TIMEOUT;
        }

        return static::TIMEOUT;
    }

    protected function getModel($id)
    {
        $this->model = $this->repo->retrieve($id);

        $this->id = $id;
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    public function createGatewayEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    /**
     * Strips sensitive data before calling trace class to
     * prevent sensitive data from being traced
     */
    protected function trace($level, $message, array $context)
    {
        if (isset($context['data']))
        {
            //
            // If 'data' field is present, then make sure that
            // no field defined in 'stripFieldsList' are present
            // in data. If so, then unset them. This is to
            // ensure extraneous or sensitive fields aren't traced.
            //
            $context['data'] = Hdfc\Utility::unsetFields(
                $context['data'],
                $this->stripFieldsList);
        }

        $this->trace->addRecord($level, $message, $context);
    }

    // -------------------------Exceptions -----------------------------------------

    protected function throwException($error, $safeRetry = false, $verifyAction = null)
    {
        // Mark error as false now to remove the stale state for future function calls.
        // @todo: refactor and remove this completely.
        $this->error = false;

        $gatewayErrorCode = $error['code'];

        // We would like to use authRespCode for gateway error code over rzp defined error code from result
        // error_code_tag and authRespCode will never occur together. Hence this would not override that
        if ((isset($error['authRespCode']) === true) and ($error['authRespCode'] !== ''))
        {
            $gatewayErrorCode = $error['authRespCode'];
        }

        $apiErrorCode = Hdfc\ErrorCodes\ErrorCodes::getInternalErrorCode($error);

        $gatewayErrorDesc = Hdfc\ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($error);

        $exception = null;

        switch ($apiErrorCode)
        {
            case Error\ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT:
                $exception = new Exception\GatewayTimeoutException('');
                break;

            case Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE:
                // TODO: This is a hack. Fix this in a better way.
                if ($this->action === Base\Action::AUTHORIZE)
                {
                    $exception = new Exception\GatewayRequestException;
                }
                else
                {
                    $exception = new Exception\GatewayErrorException($apiErrorCode);
                }
                break;

            default:

                $exception = new Exception\GatewayErrorException($apiErrorCode);

                break;
        }

        $exception->setAction($verifyAction);

        $exception->setGatewayErrorCodeAndDesc($gatewayErrorCode, $gatewayErrorDesc);

        if (($safeRetry === true) and
            ($exception instanceof Exception\GatewayRequestException))
        {
            $exception->markSafeRetryTrue();
        }

        if ($this->supportPaymentResponse['type'] === 'refund')
        {
            $exception->setData([
                PaymentModel\Gateway::GATEWAY_RESPONSE => json_encode($this->supportPaymentResponse['xml']),
                PaymentModel\Gateway::GATEWAY_KEYS     => $this->getGatewayData($this->supportPaymentResponse['data'])
            ]);
        }

        throw $exception;
    }

// -------------------------Exceptions Ends ------------------------------------

    protected function processResponse($response)
    {
        $body = $this->response->body;

        $ix = strpos($body, '<pan>');

        if ($ix !== false)
        {
            $eix = strrpos($body, '</pan>') + 6;
            $body = substr($body, 0, $ix) . substr($body, $eix);

            $this->response->body = $body;
            $this->response->raw = null;
        }
    }

    protected function validateParesAndPersistEci(array $input)
    {
        if (isset($input['gateway']['PaRes']) === false)
        {
            return;
        }

        try
        {
            $PaRes = $input['gateway']['PaRes'];
            $PaRes = base64_decode($PaRes);
            $PaRes = gzinflate(substr($PaRes));
            $PaResObject = simplexml_load_string($PaRes);
            $PaRes = json_decode(json_encode($PaResObject), true);
        }
        catch (\Throwable $e)
        {
            // Trace and ignore the exeption
            $this->trace->traceException($e);

            return;
        }

        $this->persistEci($PaRes);

        $this->checkForErrorInPares($PaRes, $input);

        $this->checkValidParesStatus($PaRes);
    }

    protected function setDebitSecondRecurringPayment(array $input)
    {
        $payment = $input['payment'];

        $this->secondDebitRecurringFlag = false;

        // For second recurring payment, recurring type has to be auto
        if ((isset($payment[PaymentEntity::RECURRING_TYPE]) === true) and
            ($payment[PaymentEntity::RECURRING_TYPE] === RecurringType::AUTO) and
            ($payment['method'] === 'card') and
            ($input['card']['type'] === Card\Type::DEBIT))
        {
            $this->secondDebitRecurringFlag = true;
        }
    }

    protected function checkForErrorInPares(array $PaRes, array $input)
    {
        if (empty($PaRes['Message']['Error']['errorCode']) === false)
        {
            $code = $PaRes['Message']['Error']['errorCode'];

            $desc = $PaRes['Message']['Error']['errorMessage'] ?? '';

            throw new Exception\GatewayErrorException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                $code,
                $desc,
                [
                    'issuer' => $input['card']['issuer'],
                    'iin'    => $input['card']['iin']
                ],
                null,
                Base\Action::AUTHENTICATE
            );
        }
    }

    protected function checkValidParesStatus(array $PaRes)
    {
        // We are doing this only for N right now as Y, A and U
        // depends on the processor
        if ((isset($PaRes['Message']['PARes']['TX']['status']) === true) and
            ($PaRes['Message']['PARes']['TX']['status'] === 'N'))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_AUTHENTICATION_STATUS_FAILED,
                null,
                null,
                [
                    'txn_data'     => $PaRes['Message']['PARes']['TX'],
                    'pares_status' => $PaRes['Message']['PARes']['TX']['status'] ?? 'N',
                ],
                null,
                Base\Action::AUTHENTICATE);
        }
    }

    protected function persistEci(array $PaRes)
    {
        if (isset($PaRes['Message']['PARes']['TX']['eci']) === true)
        {
            $this->authEnrolledResponse['data']['eci'] = $PaRes['Message']['PARes']['TX']['eci'];
        }
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                Fields::REF            => $refundFields[Fields::REF] ?? null,
                Fields::AVR            => $refundFields[Fields::AVR] ?? null,
                Fields::AUTH           => $refundFields[Fields::AUTH] ?? null,
                Fields::PAYID          => $refundFields[Fields::PAYID] ?? null,
                Fields::RESULT         => $refundFields[Fields::RESULT] ?? null,
                Fields::TRANID         => $refundFields[Fields::TRANID] ?? null,
                Fields::POSTDATE       => $refundFields[Fields::POSTDATE] ?? null,
                Fields::AUTH_RESP_CODE => $refundFields[Fields::AUTH_RESP_CODE] ?? null,
            ];
        }
        return [];
    }
}
