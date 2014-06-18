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

namespace Gateway\HdfcGateway;

use Gateway\BaseGateway;
use Exceptions\DbQueryException;
use Exceptions\InvalidArgumentException;
use Trace\TraceEvent;
use Trace\Trace;

class HdfcGateway extends BaseGateway
{
    use HdfcGatewayEnrollCard;

    use HdfcGatewayAuth;

    use HdfcGatewaySupportTxn;

    /**
     * Rzp transaction id
     * @var string
     */
    protected $id;

    protected $model;

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
     * Tranportal username for hdfc gateway
     * @var string
     */
    protected $username = "";

    /**
     * Tranportal password for hdfc gateway
     * @var string
     */
    protected $password = "";

    /**
     * Parameters required to construct request
     * for enrolling a card
     * @var array
     */
    protected $enrollRequest = array(
        'url' => HdfcGatewayUrls::TEST_ENROLL_URL,
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
        'url' => HdfcGatewayUrls::TEST_AUTH_NOT_ENROLLED_URL,
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
        'url' => HdfcGatewayUrls::TEST_AUTH_ENROLLED_URL,
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
        'url' => HdfcGatewayUrls::TEST_SUPPORT_TXN_URL,
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
        'card', 'expmonth', 'expyear', 'cvv2', 'PAReq', 'zip', 'addr', 'PaRes'
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
        'MD' => 'required|numeric|digits_between:1,19');

    /**
     * Either ENROLLED or NOT_ENROLLED
     * or false for enroll failure.
     * Default is null
     * @var
     */
    protected $enrollStatus = null;

    protected $status;

    public function __construct()
    {
        parent::__construct();

        $this->callbackUrl = \URL::to(\Constants\URL::TXN_CALLBACK_URL);
    }

    public static function getCredentials()
    {
        $creds = HdfcGatewayConfig::getCreds();

        return $creds;
    }

    /**
     * [process description]
     * @param  array  $input
     * @return array
     * The return array consists of two vars,
     * 'status' and 'error'
     */
    public function process(array $input)
    {
        // Enroll card
        if ($this->enrollCard($input))
        {
            return $this->auth();
        }
        else
        {
            // $error = HdfcGatewayErrorHandler::translateError($er['error']['code']);
            $er = $this->enrollResponse;

            return array('failed', $er['error']);
        }
    }

    public function refund(array $input)
    {
        return $this->supportTxn($input, 'refund');
    }

    public function capture(array $input)
    {
        return $this->supportTxn($input, 'capture');
    }

    public function void()
    {
        ;
    }

    /**
     * After enroll is done, auth is required
     *
     * @return void
     */
    protected function auth()
    {
        return $this->decideAuthStepAfterEnroll();
    }

    /**
     * After card enroll and bank ACS form submission,
     * bank redirects to us with 'MD' field and PaRes.
     * Next step is auth.
     *
     * @param  array  $input [description]
     *
     * @return array         [description]
     */
    public function bankAcsCallback(array $input)
    {
        validate($this->bankAcsResponseRules, $input);

        $this->model = HdfcGatewayDal::findOrFail2($input['MD']);

        $this->id = $this->model->getTrackid();

        $this->authEnrolledRequest['data']['paymentid'] = $input['MD'];

        $this->authEnrolledRequest['data']['PaRes'] = $input['PaRes'];

        $this->postAuthEnrolledRequest();

        $error = $processed = false;

        if ($this->error)
            $error = HdfcGatewayErrorHandler::parseErrorInString($this->authEnrolledResponse['error']['text']);
        else
            $processed = true;

        return array($processed, $this->id, $error);
    }

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        HdfcGatewayUtility::runRequestResponseFlow($request, $response);

        HdfcGatewayResponseXmlDal::saveXml($this->id, $response['xml'], $response['type']);

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
        $this->model = HdfcGatewayDal::retrieve($id);

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
            // If 'data' field is present, then we make sure that
            // no field defined in 'stripFieldsList' are present
            // in data. If so, then unset them. This is to
            // ensure extraneous or sensitive fields aren't traced.
            //
            $context['data'] = HdfcGatewayUtility::unsetFields(
                                $context['data'],
                                $this->stripFieldsList);
        }

        $this->trace->addRecord($level, $message, $context);
    }
}