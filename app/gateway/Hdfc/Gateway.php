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
use EE\Exception;
use Trace\TraceCode;
use Trace\Trace;
use EE\Error;

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
        'xml' => '',
        'header' => array('Content-Type'=>'text/xml'),
        'data' => array());

    /**
     * Response received after sending enroll card request
     * @var array
     */
    protected $enrollResponse = array(
        'fields' => array(
                    'error_text', 'eci', 'result', 'url', 'PAReq', 'paymentid', 'trackid'),
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
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());

    /**
     * The assoc array is used to construct auth
     * request for not enrolled card cases
     * @var array
     */
    protected $authNotEnrolledResponse = array(
        'fields' =>  array(
                        'result', 'amt', 'trackid', 'ref', 'tranid', 'auth', 'avr', 'postdate'),
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
        'header' => array('Content-Type:text/xml'),
        'type' => '',
        'xml' => '',
        'data' => array());

    protected $supportTxnResponse = array(
        'fields' => array(
            'error_text', 'trackid', 'tranid', 'result', 'auth', 'amt', 'ref', 'postdate', 'avr'),
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

    /**
     * For ENROLLED card cases, we submit a form to bank ACS
     * which redirects back to this url (on our server) after
     * the customer enter's the requisite details
     *
     * @var string
     */
    protected $callbackUrl;

    protected $bankAcsResponseRules = array(
        'PaRes' => 'required',
        'MD'    => 'required|numeric|digits_between:1,19',
        'txn'   => 'required');

    /**
     * Either ENROLLED or NOT_ENROLLED
     * or false for enroll failure.
     * Default is null
     * @var
     */
    protected $enrollStatus = null;

    protected $repo = null;

    public function __construct()
    {
        parent::__construct();

        $this->callbackUrl = \URL::to(\Constants\URL::TXN_CALLBACK_URL);

        $this->repo = new Hdfc\Repository();
    }

    public static function getCredentials()
    {
        $creds = Hdfc\Config::getCreds();

        return $creds;
    }

    /**
     * Does card auth
     *
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::auth($input);

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
     * HDFC gateway does not provide void
     * @return void
     */
    public function void()
    {
        ;
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

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        Hdfc\Utility::runRequestResponseFlow($request, $response);

        $this->repo->saveXml($this->id, $response['xml'], $response['type']);

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

    protected function throwException($gatewayErrorCode)
    {
        $gatewayErrorDesc = Hdfc\ErrorHandler::getErrorMessage($gatewayErrorCode);

        $appErrorCode = Hdfc\ErrorHandler::getMappedError($gatewayErrorCode);

        $exception = null;

        switch ($appErrorCode)
        {
            case Error\ErrorCode::CARD_ERROR_INVALID_BRAND:
            case Error\ErrorCode::CARD_ERROR_INVALID_NAME:
            case Error\ErrorCode::CARD_ERROR_INVALID_NUMBER:
            case Error\ErrorCode::CARD_ERROR_INVALID_EXPIRY_DATE:
            case Error\ErrorCode::CARD_ERROR_CARD_DECLINED:
                $exception = new Exception\CardErrorException($appErrorCode);

                $exception->setGatewayErrorCodeAndDesc(
                    $gatewayErrorCode,
                    $gatewayErrorDesc);

                break;

            case Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_UDF:
            case Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_DENIED_NEGATIVE_BIN:
            case Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_INVALID_AMOUNT:
                break;

            default:
                $exception = new Exception\GatewayErrorException(
                                $appErrorCode,
                                $gatewayErrorCode,
                                $gatewayErrorDesc);

                break;
        }

        throw $exception;
    }
}