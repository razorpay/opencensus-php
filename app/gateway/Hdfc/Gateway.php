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

namespace Gateway\Hdfc;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\Base;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

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

    const INR_CODE = 356;

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

    const TIMEOUT = 30;

    /**
     * Parameters required to construct request
     * for enrolling a card
     * @var array
     */
    protected $enrollRequest = array(
        'url' => Hdfc\Urls::ENROLL_URL,
        'type' => 'enroll',
        'fields' => array('trackid', 'member', 'card', 'expmonth', 'expyear', 'cvv2',
                          'amt', 'action', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5'),
        'xml' => '',
        'header' => array('Content-Type'=>'text/xml'),
        'data' => array());

    /**
     * Response received after sending enroll card request
     * @var array
     */
    protected $enrollResponse = array(
        'fields' => array(
                    'result', 'eci', 'paymentid', 'trackid', 'PAReq', 'url', 'error_text'),
        'fieldsEnrolled' => array('result', 'url', 'PAReq', 'paymentid', 'trackid',
                                  'udf1', 'udf2', 'udf3', 'udf4', 'udf5'),
        'fieldsNotEnrolled' => array('result', 'PAReq', 'paymentid', 'trackid',
                                     'udf1', 'udf2', 'udf3', 'udf4', 'udf5'),
        'type' => 'enroll',
        'xml' => '',
        'data' => array(),
        'error' => null);

    /**
     * The assoc array is used to construct auth
     * request for debit cards
     * @var array
     */
    protected $authEnrolledRequest = array(
        'url' => Hdfc\Urls::AUTH_ENROLLED_URL,
        'type' => 'auth_enrolled',
        'fields' => array('paymentid', 'PaRes'),
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());

    protected $authEnrolledResponse = array(
        'fields' => array(
            'result', 'auth', 'ref', 'avr', 'postdate', 'paymentid', 'tranid', 'trackid',
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'error_text'),
        'type' => 'auth_enrolled',
        'xml' => '',
        'data' => array(),
        'error' => null);

    /**
     * The assoc array is used to constructing
     * auth request for enrolled card cases
     * @var array
     */
    protected $authNotEnrolledRequest = array(
        'url' => Hdfc\Urls::AUTH_NOT_ENROLLED_URL,
        'type' => 'auth_not_enrolled',
        'fields' => array('trackid', 'member', 'card', 'expmonth', 'expyear', 'cvv2', 'action',
                          'zip', 'addr', 'amt', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5'),
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());

    /**
     * Response received after sending authNotEnrolledRequest
     * @var array
     */
    protected $authNotEnrolledResponse = array(
        'fields' =>  array(
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid',
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'amt', 'error_text'),
        'type' => 'auth_not_enrolled',
        'xml' => '',
        'data' => array(),
        'error' => null);

    /**
     * The assoc array is used to construct
     * request for refunds/captures
     * @var array
     */
    protected $supportPaymentRequest = array(
        'url' => Hdfc\Urls::SUPPORT_PAYMENT_URL,
        'type' => '',
        'fields' => array('action', 'amt', 'member', 'transid', 'trackid'),
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());

    protected $supportPaymentResponse = array(
        'fields' => array(
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid',
            'udf2', 'udf5', 'amt', 'error_text'),
        'type' => '',
        'xml' => '',
        'data' => array(),
        'error' => null);

    protected $inquiryRequest = array(
        'url' => Hdfc\Urls::SUPPORT_PAYMENT_URL,
        'fields' => array('action', 'transid'),
        'type' => 'inquiry',
        'xml' => '',
        'data' => array(),
        'error' => null);

    protected $inquiryResponse = array(
        'type' => 'inquiry',
        'fields' => array('result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid', 'amt',
                    'udf1', 'udf2', 'udf3', 'udf4', 'udf5'),
        'data' => array(),
        'xml' => '',
        'error' => null);

    /**
     * The array is used to specify fields that are not to be logged by trace class
     * they are stripped by calling stripSensitive function of this class on the request/response object
     * @var array
     */
    protected $stripFieldsList = array(
        'password', 'amt', 'currencycode', 'id', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'member',
        'card', 'expmonth', 'expyear', 'cvv2', 'PAReq', 'zip', 'addr', 'PaRes', 'number', 'cvv'
    );

    protected $bankAcsResponseRules = array(
        'PaRes'     => 'required',
        'MD'        => 'required|numeric|digits_between:1,19',
        'PaReq'     => 'sometimes');

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

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Hdfc\Repository();
    }

// ---------------------------Gateway operations -------------------------------

    /**
     * Does card auth
     *
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        //
        // Enroll card
        //
        $status = $this->enrollCard($input);

        //
        // After enroll is done, auth is to be done
        //
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
     */
    public function callback(array $input)
    {
        parent::callback($input);

        validate($this->bankAcsResponseRules, $input['gateway'], false);

        $this->id = $input['payment']['id'];

        $this->model = $this->repo->findByGatewayTransactionIdOrFail(
            $input['gateway']['MD']);

        $paymentId = $this->model->getPaymentId();

        if ($this->id !== $paymentId)
        {
            throw new Exception\LogicException(
                'app payment '. $this->id . ' should be equal to payment id . '. $paymentId);
        }

        $this->postAuthEnrolledRequest($input);
    }

    public function verify(array $input)
    {
        $data = $this->inquire($input);

        return $data;
    }

    /**
     * HDFC gateway does not provide void
     */
    public function void()
    {
        throw new Exception\LogicException(
            'Hdfc gateway does not support voids');
    }

    public function getPaymentOrRefundId($input)
    {
        return Hdfc\Mpr\Reconciler::getPaymentOrRefundId($input);
    }

    public function reconcile($input)
    {
        return (new Hdfc\Mpr\Reconciler)->reconcile(
            $input['input'],
            $input['transactionId'],
            $input['entities']);
    }

    public function generateMpr($input)
    {
        return (new Hdfc\Mpr\Generator)->generateMpr($input);
    }

    public function mprFileExists()
    {
        return (new Hdfc\Mpr\Generator)->mprFileExists();
    }

    public function deleteMprFileIfExists()
    {
        return (new Hdfc\Mpr\Generator)->deleteMprFileIfExists();
    }

// ----------------------Gateway operations end --------------------------------

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
        catch(\Requests_Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (Utility::checkTimeout($e))
            {
                $this->error = true;

                $response['content'] = '';
                Hdfc\ErrorHandler::setTimeoutError($response);

                return;
            }
            else
            {
                throw $e;
            }
        }

        $response['xml'] = $response['response']->body;

        $this->repo->saveXml($this->id, $response['xml'], $response['type']);

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

    protected function checkResponseStatusCode(& $response)
    {
        $status_code = (int) $response['response']->status_code;

        if ($status_code >= 500)
        {
            Hdfc\ErrorHandler::setGatewayWrongStatusCode($response, $status_code);

            $this->error = true;
        }
    }

    protected function checkResponseContentType(& $response)
    {
        $contentType = $response['response']->headers['content-type'];

        if (strpos($contentType, 'application/xml') === false)
        {
            Hdfc\ErrorHandler::setGatewayWrongContentType($response, $contentType);

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
        $request['options'] = $this->getRequestOptions();

        $this->response = $this->sendGatewayRequest($request);

        $this->processResponse($this->response);

        return $this->response;
    }

    protected function getRequestOptions()
    {
        $options['verify'] = false;
        $options['timeout'] = $this->getTimeout();

        return $options;
    }

    protected function getTimeout()
    {
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

    protected function throwGatewayTimeoutException($code)
    {
        $msg = null;
        $e = null;

        if ($this->exception !== null)
        {
            $e = $this->exception;
            $msg = $e->getMessage();
        }

        $exception = new Exception\GatewayTimeoutException($e->getMessage(), $e);

        $desc = Hdfc\ErrorCode::$errorMessages[$code];

        $exception->setGatewayErrorCodeAndDesc(
            $code,
            $desc);

        throw $exception;
    }

    protected function throwException($error)
    {
        $gatewayErrorCode = $error['code'];

        if (($gatewayErrorCode === Hdfc\ErrorCode::RP00003) or
            ($gatewayErrorCode === Hdfc\ErrorCode::RP00004))
        {
            $this->throwGatewayTimeoutException($gatewayErrorCode);
        }

        $gatewayErrorDesc = $error['text'];

        if (Hdfc\ErrorHandler::isValidErrorCode($gatewayErrorCode))
        {
            $apiErrorCode = Hdfc\ErrorHandler::getMappedError($gatewayErrorCode);

            /**
             * For error codes returned by gateway, the error messages are in a format
             * which we don't parse. So get the standard messages for those from here.
             */
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
            case Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_UDF:
            case Error\ErrorCode::GATEWAY_ERROR_PAYMENT_DENIED_NEGATIVE_BIN:
            case Error\ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT:
                $exception = new Exception\GatewayErrorException(
                                $apiErrorCode,
                                $gatewayErrorCode,
                                $gatewayErrorDesc);

                break;
            default:

                $exception = new Exception\GatewayErrorException(
                                $apiErrorCode,
                                $gatewayErrorCode,
                                $gatewayErrorDesc);

                break;
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

}