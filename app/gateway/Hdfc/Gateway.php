<?php

/**
 * This file implements the interactions with HDFC gateway
 * via the api of FSF gateway (which HDFC uses) and which
 * we actually interact with.
 *
 * The transaction flow for a purchase/auth txn
 * in few simple words goes like this:
 * 1. We send an enroll request for a card
 * 2. For certain cards (probably cc) we get a 'NOT ENROLLED' response back
 *    2.1. For these cards, we send auth request and complete the txn.
 * 3. For certain cards (probably dc) we get an 'ENROLLED' response back
 *    3.1. For these cards, we send a request to acquiring bank (hdfc)
 *         ACS where the customer enters card fields etc. and the bank
 *         redirects to a url provided by us.
 *    3.2. From the redirected url, we send auth request and
 *         complete the txn.
 *
 * Note: Refer to HDFC FSF Payment Gateway Integration
 *       Version 4.0 pdf document
 *
 */

namespace Gateway\Hdfc;

use Gateway\BaseGateway;
use Gateway\Hdfc;
use EE\Error;
use EE\Exception;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends BaseGateway
{
    use EnrollCardTrait;
    use AuthTransactionTrait;
    use SupportTransactionTrait;

    /**
     * App transaction id
     * @var string
     */
    protected $id;

    /**
     * Curent Hdfc Transaction Model
     * @var Hdfc\Entity
     */
    protected $model = null;

    const INR_CODE = 356;

    /**
     * If during the txn flow, we detect an
     * error, or the txn fails for any reason,
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

    /**
     * The state in which the api is operating
     * that is live/test
     * @var string
     */
    protected $mode;

    /**
     * Fields sent in xml format to enroll
     * @var array
     */
    protected $fields = array(
        'id',
        'password',
        'card',
        'cvv2',
        'expyear',
        'expmonth',
        'action',
        'amt',
        'currencycode',
        'member',
        'trackid',
        'udf1',
        'udf2',
        'udf3',
        'udf4',
        'udf5');

    /**
     * Mapping of keys from rzp to
     * to hdfc gateway for card
     * @var array
     */
    protected $cardKeyMappings = array(
        'name' => 'member',
        'number' => 'card',
        'expiry_month' => 'expmonth',
        'expiry_year' => 'expyear',
        'cvv' => 'cvv2');

    /**
     * Parameters required to construct request
     * for enrolling a card
     * @var array
     */
    protected $enrollRequest = array(
        'url' => Hdfc\Urls::TEST_ENROLL_URL,
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
        'type' => 'enroll',
        'xml' => '',
        'data' => array(),
        'error' => null);

    /**
     * The assoc array is used to constructing
     * auth request for enrolled card cases
     * @var array
     */
    protected $authNotEnrolledRequest = array(
        'url' => Hdfc\Urls::TEST_AUTH_NOT_ENROLLED_URL,
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
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid', 'amt',
            'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'error_text'),
        'type' => 'auth_not_enrolled',
        'xml' => '',
        'data' => array(),
        'error' => null);

    /**
     * The assoc array is used to construct auth
     * request for debit cards
     * @var array
     */
    protected $authEnrolledRequest = array(
        'url' => Hdfc\Urls::TEST_AUTH_ENROLLED_URL,
        'type' => 'auth_enrolled',
        'fields' => array('paymentid', 'MD'),
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());

    protected $authEnrolledResponse = array(
        'fields' => array(
            'paymentid', 'error_text', 'result', 'ref', 'tranid', 'auth', 'avr', 'postdate'),
        'type' => 'auth_enrolled',
        'xml' => '',
        'data' => array(),
        'error' => null);

    /**
     * The assoc array is used to construct
     * request for refunds/captures
     * @var array
     */
    protected $supportTxnRequest = array(
        'url' => Hdfc\Urls::TEST_SUPPORT_TXN_URL,
        'type' => '',
        'fields' => array('action', 'amt', 'member', 'transid', 'trackid'),
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());

    protected $supportTxnResponse = array(
        'fields' => array(
            'result', 'auth', 'ref', 'avr', 'postdate', 'tranid', 'trackid', 'payid', 'amt', 'udf2', 'udf5', 'error_text'),
        'type' => '',
        'xml' => '',
        'data' => array(),
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
        'txn'       => 'required|array');

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

    public static function getCredentials()
    {
        $creds = Hdfc\Config::getCreds();

        return $creds;
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
        $this->enrollCard($input);

        //
        // After enroll is done, auth is to be done
        //
        return $this->decideAuthStepAfterEnroll();
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->supportTxn($input, 'refund');
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $this->supportTxn($input, 'capture');
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
        validate($this->bankAcsResponseRules, $input);

        $this->id = $input['txn']['id'];

        $this->model = $this->repo->findOrFail($input['MD']);

        $trackid = $this->model->getTrackid();

        if ($this->id !== $trackid)
        {
            throw new Exception\LogicException(
                'app txn '. $this->id . ' should be equal to track id . '. $trackid);
        }

        $this->postAuthEnrolledRequest($input);
    }

    /**
     * HDFC gateway does not provide void
     */
    public function void()
    {
        throw new Exception\LogicException(
            'Hdfc gateway does not support voids');
    }

    public function getTransactionId($input)
    {
        return Hdfc\MerchantPaymentReport::getTransactionid($input);
    }

    public function reconcile($input)
    {
        $mpr = new Hdfc\MerchantPaymentReport();

        return $mpr->reconcile($input['input'], $input['ledgerId'], $input['entities']);
    }

// ----------------------Gateway operations end --------------------------------

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        $this->setTerminalInRequest($request);

        // Create xml from the fields
        $request['xml'] = Utility::createXml($request['data']);

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

                $response['xml'] = '';
                Hdfc\ErrorHandler::setTimeoutError($response);

                return;
            }
            else
            {
                throw $e;
            }
        }

        $response['xml'] = $response['response']->body;
//sd($response);
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
            throw new \InvalidArgumentException(
                'hdfc gateway: wrong terminal supplied. Gateway: ' . $terminal['gateway']);
        }

        $id = $terminal['gateway_terminal_id'];
        $pwd = $terminal['gateway_terminal_password'];

        // For 'test' mode, replace any random terminal given with
        // hdfc test terminal
        if ($this->mode === 'test')
        {
            list($id, $pwd) = $this->getCredentials();
        }

        $request['data']['id'] = $id;
        $request['data']['password'] = $pwd;
    }

    protected function checkResponseErrorCode($response)
    {
        //
        // This step is very crucial for deciding future steps in
        // transaction flow.
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

        return $this->response;
    }

    protected function sendGatewayRequest($request)
    {
        return Requests::post(
                    $request['url'],
                    $request['header'],
                    $request['xml'],
                    $request['options']);
    }

    protected function getRequestOptions()
    {
        $options['verify'] = false;
        $options['timeout'] = $this->getTimeout();

        return $options;
    }

    protected function getTimeout()
    {
        return Hdfc\Config::TIMEOUT;
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

    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
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

        /**
         * For error codes returned by gateway, the error messages are in a format
         * which we don't parse. So get the standard messages for those from here.
         */
        if (strpos($gatewayErrorCode, 'RP') === false)
        {
            $gatewayErrorDesc = Hdfc\ErrorHandler::getErrorMessage($gatewayErrorCode);
        }


        $apiErrorCode = Hdfc\ErrorHandler::getMappedError($gatewayErrorCode);

        $exception = null;

        switch ($apiErrorCode)
        {
            case Error\ErrorCode::CARD_ERROR_INVALID_BRAND:
            case Error\ErrorCode::CARD_ERROR_INVALID_NAME:
            case Error\ErrorCode::CARD_ERROR_INVALID_NUMBER:
            case Error\ErrorCode::CARD_ERROR_INVALID_EXPIRY_DATE:
            case Error\ErrorCode::CARD_ERROR_CARD_DECLINED:
                $exception = new Exception\CardErrorException($apiErrorCode);

                $exception->setGatewayErrorCodeAndDesc(
                    $gatewayErrorCode,
                    $gatewayErrorDesc);

                break;

            case Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_UDF:
            case Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_DENIED_NEGATIVE_BIN:
            case Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_AMOUNT:
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

}