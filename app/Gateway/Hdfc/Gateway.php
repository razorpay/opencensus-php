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

use RZP\Base\JitValidator;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Models\Card;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action as BaseAction;
use App;

class Gateway extends Base\Gateway
{
    use Payment\Enroll;
    use Payment\Authorize;
    use Payment\Support;
    use Payment\Inquiry;

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
            'timeout' => 5
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
            'trackid', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'error_text'
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
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'amt', 'error_text'
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
                        'trackid', 'payid', 'udf2', 'udf5', 'amt', 'error_text'],
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
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5'],
        'data' => [],
        'xml' => '',
        'error' => null];

    /**
     * The array is used to specify fields that are not to be logged by trace class
     * they are stripped by calling stripSensitive function of this class on the request/response object
     * @var array
     */
    protected $stripFieldsList = [
        'password', 'currencycode', 'id', 'udf1', 'udf2', 'udf3', 'udf4',
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
     * @var Gateway\Hdfc\Repository
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

        $status = $this->enrollCard($input);

        return $this->decideAuthStepAfterEnroll($status);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->supportPayment($input, 'refund');
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $this->supportPayment($input, 'capture');
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

        if ($network === Card\NetworkName::RUPAY)
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

            return $this->getCallbackResponseData($input);
        }

        $this->validateCallbackGatewayFields($input, $network);

        $this->validateParesStatusIfApplicable($input);

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

        $acquirerData = $this->getAcquirerData($this->model);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function getAcquirerData($gatewayPayment)
    {
        return [
            'acquirer' => [
                PaymentEntity::APPROVAL_CODE => $gatewayPayment->getAuthCode(),
                PaymentEntity::REFERENCE1    => $gatewayPayment->getRef()
            ]
        ];
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

    public function manualGatewayRefund(array $input)
    {
        $canManualRefund = $this->canForceRefund($input);

        if ($canManualRefund)
        {
            $this->refund($input);

            // Successfully refunded on the gateway
            return true;
        }
        else
        {
            // Did not refund on the gateway side
            return false;
        }
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
                Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        $this->setTerminalInRequest($request);

        // Create xml from the fields
        $request['content'] = Utility::createXml($request['data']);

        $domain = ($this->mode === Mode::LIVE) ? Urls::LIVE_DOMAIN : Urls::TEST_DOMAIN;
        $request['url'] = $domain . $request['url'];

        $this->requestVar = $request;

        try
        {
            // send the request and get response
            $response['response'] = $this->postRequest($request);
        }
        catch (Exception\GatewayRequestException $e)
        {
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

        if ($this->error === false)
        {
            $this->checkResponseContentType($response);
        }

        if ($this->error === false)
        {
            Utility::parseResponseXml($response);

            $this->checkResponseErrorCode($response);
        }
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
                $contentType);

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

    protected function throwException($error, $safeRetry = false)
    {
        // Mark error as false now to remove the stale state for future function calls.
        // @todo: refactor and remove this completely.
        $this->error = false;

        $gatewayErrorCode = $error['code'];
        $gatewayErrorDesc = $error['text'];

        if (Hdfc\ErrorHandler::isValidErrorCode($gatewayErrorCode))
        {
            $apiErrorCode = Hdfc\ErrorHandler::getMappedError($gatewayErrorCode);

            //
            // For error codes returned by gateway, the error messages are in a format
            // which we don't parse. So get the standard messages for those from here.
            //
            $gatewayErrorDesc = Hdfc\ErrorHandler::getErrorMessage($gatewayErrorCode);
        }
        else
        {
            $apiErrorCode = Error\ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;

            $this->trace->error(
                TraceCode::GATEWAY_UNKNOWN_ERROR,
                [
                    'action' => $this->action,
                    'gateway_error_code' => $gatewayErrorCode,
                    'gateway_error_description' => $gatewayErrorDesc,
                    'gateway' => $this->gateway,
                    'time' => time()
                ]);
        }

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

        $exception->setGatewayErrorCodeAndDesc($gatewayErrorCode, $gatewayErrorDesc);

        if (($safeRetry === true) and
            ($exception instanceof Exception\GatewayRequestException))
        {
            $exception->markSafeRetryTrue();
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

    protected function validateParesStatusIfApplicable(array $input)
    {
        if (isset($input['gateway']['PaRes']) === false)
        {
            return;
        }

        try
        {
            $PaRes = $input['gateway']['PaRes'];

            $PaRes = base64_decode($PaRes);
            $PaRes = gzinflate(substr($PaRes, 2));

            $PaResObject = simplexml_load_string($PaRes);
            $PaRes = json_decode(json_encode($PaResObject), true);
        }
        catch (\Throwable $e)
        {
            // Trace and ignore the exeption
            $this->trace->traceException($e);

            return;
        }

        // We are doing this only for N right now as Y, A and U
        // depends on the processor
        if ((isset($PaRes['Message']['PARes']['TX']['status']) === true) and
            ($PaRes['Message']['PARes']['TX']['status'] === 'N'))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED);
        }
    }
}
