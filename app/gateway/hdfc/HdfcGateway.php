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
use Trace\GatewayTrace;
use Trace\TraceEvent;
 
class HdfcGateway extends BaseGateway
{
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
    protected $card_key_mappings = array(
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
     * @param [type] $response [description]
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
        'error' => array());
 
    /**
     * The assoc array is used to constructing
     * auth request for credit cards
     * @var array
     */
    protected $authNotEnrolledRequest = array(
        'url' => HdfcGatewayUrls::TEST_AUTH_NOT_ENROLLED_URL,
        'type' => 'auth_not_enrolled',
        'header' => array('Content-Type:text/xml'),
        'xml' => '',
        'data' => array());
 
    protected $authNotEnrolledResponse = array(
        'fields' =>  array(
                        'result', 'amt', 'trackid', 'ref', 'tranid', 'auth', 'avr', 'postdate'),
        'type' => 'auth_not_enrolled',
        'xml' => '',
        'data' => array(),
        'error' => array());
 
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
        'error' => array());
 
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
        'error' => array());

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
 
    protected $status;
 
    public function __construct()
    {
        parent::__construct();
        $this->callbackUrl=\URL::to('transactions/callback');
    }

    public static function getCredentials()
    {
        $creds = HdfcGatewayConfig::getCreds(); 
        return $creds;
    }
 
    public function process($input)
    {
        $this->id = $input['txn']['id'];
 
        $this->enrollCard($input);

        $er = $this->enrollResponse;
 
        if (isset($er['error']) !== null)
        {
            if ($er['data']['enroll_result'] === HdfcGatewayResult::ENROLLED)
            {
                $this->status = HdfcGatewayResult::ENROLLED;
                return $this->postPaymentRequestToBankACS();
            }
            else if ($er['data']['enroll_result'] === HdfcGatewayResult::NOT_ENROLLED)
            {
                $this->status = HdfcGatewayResult::NOT_ENROLLED;
                return $this->postAuthNotEnrolledRequestToBank();
            }
            else
            {
                $error = HdfcGatewayErrorHandler::parseErrorInString($this->enrollResponse['data']['result']);
                if ($error === false)
                    $error = HdfcGatewayErrorHandler::unknownError();
                
                //Logging
                $log_content = array('message' => "Enrollment Error", 'error' => $error) + $this->stripSensitive($this->enrollResponse);
                $this->trace->error(TraceEvent::GATEWAY_ENROLL_ERROR, $log_content);

                return array('failed', $error);
            }
        }
        else
        {
            $error = HdfcGatewayErrorHandler::translateError($er['error']['code']);
 
            return array('failed', $error);
        }
    }
 
    public function bankAcsCallback($input)
    {
        $invalid_keys = array_diff_key($input, $this->bankAcsResponseRules);
 
        if (count($invalid_keys) !== 0)
        {
            throw new Exceptions\InvalidKeysException('Gateway Exception: '.$invalid_keys);
        }
 
        $validation = \Validator::make($input, $this->bankAcsResponseRules);
 
        if ($validation->fails()) 
        {
            throw new InvalidArgumentException('Gateway Exception: Invalid Arguements '.$validation->messages()->all());
        }
 
        $this->model = HdfcGatewayDal::findOrFail($input['MD']);
        $this->id = $this->model->getTrackid();
        $this->authEnrolledRequest['data']['paymentid'] = $input['MD'];
        $this->authEnrolledRequest['data']['PaRes'] = $input['PaRes'];

        $this->authEnrolledRequest();

        $error = $processed = false;

        if ($this->error)
            $error = HdfcGatewayErrorHandler::parseErrorInString($this->authEnrolledResponse['error']['text']);
        else
            $processed = true;

        return array($processed, $this->id, $error);
    }
 
    public function authEnrolledRequest()
    {
        if ((int) $this->model->enroll_result !== HdfcGatewayResult::ENROLLED)
        {
            throw new InvalidArgumentException('Gateway Exception: Result not valid');
        }
        else if ($this->model->status !== 'VERES Received')
        {
            throw new InvalidArgumentException('Gateway Exception: Status not valid');
        }
 
        $data = &$this->authEnrolledRequest['data'];
 
        list($data['id'], $data['password']) = $this->getCredentials();
        
        //Logging
        $log_content = $this->stripSensitive(
            $this->authEnrolledRequest) + array('message'=>'Auth request sent for enrolled card');
        
        $this->trace->debug(TraceEvent::GATEWAY_ENROLLED_AUTH_REQUEST, $log_content);

        $this->runRequestResponseFlow(
            $this->authEnrolledRequest,
            $this->authEnrolledResponse);

        $this->persistAfterDCAuth();
    }
 
    /**
     * Sends request for enrolling the card
     * with hdfc gateway
     * 
     * @param  array $input 
     * Should contain 'txn' and 'card' arrays
     * 
     */
    protected function enrollCard($input)
    {
        // Fields to be sent to HDFC gateway for card-enroll
        $this->createEnrollRequestFields($input);

        //Logging
        $log_content = $this->stripSensitive($this->enrollRequest) + array('message'=>'Enrollment request sent');
        $this->trace->debug(TraceEvent::GATEWAY_ENROLL_REQUEST, $log_content);

        $this->runRequestResponseFlow(
            $this->enrollRequest,
            $this->enrollResponse);

        $this->parseEnrollResponseEci();
 
        if (($this->error) or
            (HdfcGatewayResult::isEnrollSuccess($this->enrollResponse) === false))
        {
            $error = HdfcGatewayErrorHandler::translateEnrollError($this->enrollResponse);
            
            //Logging
            $log_content = $this->stripSensitive($this->enrollResponse) + array('message'=>'Enrollment request failed') + array('error'=> $error);
            $this->trace->error(TraceEvent::GATEWAY_ENROLL_ERROR, $log_content);

            return array(false, $error);
        }
 
        $this->validateEnrollResponse();

        $this->persistAfterEnroll();
    }
 
    protected function postAuthNotEnrolledRequestToBank()
    {
        // Only need to add zip and addr fields
        // since other fields have already been added during enroll
        $data = $this->enrollRequest['data'];
 
        $data['zip'] = "";
 
        $data['addr'] = "";

        $this->authNotEnrolledRequest['data'] = $data;
        
        //Logging
        $log_content = $this->stripSensitive($this->authNotEnrolledRequest) + array('message'=>'Not Enrolled request sent');
        $this->trace->debug(TraceEvent::GATEWAY_NOT_ENROLLED_REQUEST, $log_content);

        $this->runRequestResponseFlow(
            $this->authNotEnrolledRequest,
            $this->authNotEnrolledResponse);


        $this->model->persistAfterCCAuth(
                        $this->authNotEnrolledResponse['data']);

        $notEnrollResponse = $this->authNotEnrolledResponse;

        $notEnrollResponse['data']['processed'] = ($notEnrollResponse['data']['result'] == 'APPROVED') ? 1 : 0;
        
        if($notEnrollResponse['data']['processed'])
        {
            //Logging
            $log_content = $this->stripSensitive($notEnrollResponse) + array('message'=>'Not Enrolled request successful');
            $this->trace->info(TraceEvent::GATEWAY_NOT_ENROLLED_RESPONSE, $log_content);
        }
        else
        {
            //Logging
            $log_content = $this->stripSensitive($notEnrollResponse) + array('message'=>'Not Enrolled request failed');
            $this->trace->error(TraceEvent::GATEWAY_NOT_ENROLLED_FAILED, $log_content);
        }
        return array('not enrolled',
                     array(
                        'data' => $notEnrollResponse['data']
                        ));
    }
 
    /**
     * Generates a form and auto-submits it on load
     * with the fields received in response
     * from enrolling the card
     */
    protected function postPaymentRequestToBankACS()
    {
        $enrollResponse = $this->enrollResponse;

        return array('enrolled', 
                     array(
                        'data' => $enrollResponse['data'], 
                        'callbackUrl' => $this->callbackUrl));
    }
 
    /**
     * Collect all fields to be sent for
     * enrolling the card
     * 
     * @param  array $input 
     * Contains the 'txn' and 'card' details
     */
    protected function createEnrollRequestFields($input)
    {
        $txn = $input['txn'];
 
        $card = $input['card'];
 
        $data = &$this->enrollRequest['data'];
 
        // Collect creds
        list($data['id'], $data['password']) = static::getCredentials();
 
        $data['trackid'] = $txn['id'];
         
        // Convert amount from integer to decimal
        $data['amt'] = $txn['amount']/100;
 
        // Collect udf fields
        $data['udf1'] = 'junk';
 
        $data['udf2'] = $txn['udf']['email'];
 
        $data['udf3'] = $txn['udf']['contact'];
         
        $data['udf4'] = 'junk';
 
        $data['udf5'] = 'junk';
 
        // Collect fields related to the card
        $this->mapKeys($card, $this->card_key_mappings, $data);
 
        //
        // Write currency code manually.
        // Later change it to something better
        // when we support multiple currencies
        // 
        $data['currencycode'] = self::INR_CODE;
        
        $data['action'] = HdfcGatewayAction::AUTH;
        /*if ($txn['process'] === 0)
        {
            $data['action'] = HdfcGatewayAction::AUTH;
        }
        else if ($txn['processed'] === 1)
        {
            $data['action'] = HdfcGatewayAction::HOLD;
        }
        else
        {
            throw new InvalidArgumentException('process should be 0 or 1');
        }*/
    }
 
    protected function validateEnrollResponse()
    {
        $trackid = $this->enrollResponse['data']['trackid'];
 
        if ($trackid !== $this->id)
        {
            throw new InvalidArgumentException('Gateway Exception: Track id do not match');
        }
    }

    protected function validateRefundResponse()
    {
        $trackid = $this->enrollResponse['data']['trackid'];

        if ($trackid !== $this->enrollRequest['data']['trackid'])
        {
            throw new InvalidArgumentException('Gateway Exception: Track id do not match');
        }
    }
 
    protected function persistAfterEnroll()
    {
        if (isset($this->enrollResponse['error']['code']))
        {
            $this->model = HdfcGatewayDal::persistAfterEnrollError(
                            $this->id,
                            $this->enrollResponse['error']);

            //Logging
            $log_content = $this->stripSensitive($this->enrollResponse) + array('message'=>'Enrollment request failed');
            $this->trace->error(TraceEvent::GATEWAY_ENROLL_ERROR, $log_content);
        }
        else
        {
            $this->model = HdfcGatewayDal::persistAfterEnroll(
                    $this->enrollRequest['data'],
                    $this->enrollResponse['data']);

            //Logging
            $log_content = $this->stripSensitive($this->enrollResponse) + array('message'=>'Enrollment response');
            $this->trace->info(TraceEvent::GATEWAY_ENROLL_RESPONSE, $log_content);
        }
    }

    protected function persistAfterDCAuth()
    {
        if (isset($this->authEnrolledResponse['error']['code']))
        {
            $this->model->persistAfterDCAuthError($this->authEnrolledResponse['error']);

            //Logging
            $log_content = $this->stripSensitive($this->authEnrolledResponse) + array('message'=>'Auth error for enrolled card');
            $this->trace->error(TraceEvent::GATEWAY_ENROLLED_AUTH_ERROR, $log_content);
            
            return false;
        }
        else
        {
            $this->model->persistAfterDCAuth($this->authEnrolledResponse['data']);

            //Logging
            $log_content = $this->stripSensitive($this->authEnrolledResponse) + array('message'=>'Auth response for enrolled card');
            $this->trace->info(TraceEvent::GATEWAY_ENROLLED_AUTH_RESPONSE, $log_content);

            return true;
        }
    }

    protected function persistAfterSupportTxn($type = 'capture')
    {
        if (isset($this->supportTxnResponse['error']['code']))
        {
            $this->model = HdfcGatewayDal::persistAfterSupportTxnError(
                            $this->id,
                            $this->supportTxnRequest['data']['transid'],
                            $this->supportTxnResponse['error'],
                            $type);
            
            //Logging
            $log_content = $this->stripSensitive($this->supportTxnResponse) + array('message'=>'Support Request Failed');
            $this->trace->error(TraceEvent::GATEWAY_SUPPORT_ERROR, $log_content);

            return false;
        }
        else
        {
            $this->model = HdfcGatewayDal::persistAfterSupportTxn(
                    $this->supportTxnRequest['data'],
                    $this->supportTxnResponse['data']);

            //Logging
            $log_content = $this->stripSensitive($this->supportTxnResponse) + array('message'=>'Support Request Successful');
            $this->trace->info(TraceEvent::GATEWAY_SUPPORT_RESPONSE, $log_content);

            return true;
        }
    }
 
    /**
     * Get the fields from xml response 
     * of the enrolling crad
     *
     */
    protected function parseEnrollResponseEci()
    {
        $eci = &$this->enrollResponse['data']['eci'];
        $eci = (($eci === null) or ($eci === '')) ? '7' : $eci;
 
        $result = &$this->enrollResponse['data']['result'];
         
        $this->enrollResponse['data']['enroll_result'] = HdfcGatewayResult::resultCode($result);
    }
 
    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        HdfcGatewayUtility::runRequestResponseFlow($request, $response);
    
        HdfcGatewayResponseXmlDal::saveXml($this->id, $response['xml'], $response['type']);
 
        if (isset($response['error']['code']))
        {
            $this->error = true;
        }
    }

    public function refund($input)
    {
        $this->model = HdfcGatewayDal::retrieve($input['txn']['id']);

        $this->id = $input['txn']['id'];

        $this->supportTxnRequest['type'] = 'refund';
        $this->supportTxnResponse['type'] = 'refund';

        // Fields to be sent to HDFC gateway for refund
        $this->createSupportTxnRequestFields($input, HdfcGatewayAction::REFUND);

        //Logging
        $log_content = $this->stripSensitive($this->supportTxnRequest) + array('message'=>'Support Request Sent');
        $this->trace->debug(TraceEvent::GATEWAY_SUPPORT_REQUEST, $log_content);

        $this->runRequestResponseFlow(
            $this->supportTxnRequest,
            $this->supportTxnResponse);

        $error=array();
        if($this->supportTxnResponse['error'])
        {
            $error = HdfcGatewayErrorHandler::parseErrorInString($this->supportTxnResponse['error']['result']);
            if ($error === false)
                    $error = HdfcGatewayErrorHandler::unknownError();
        }
        
        $status = $this->persistAfterSupportTxn('refund');

        return array($status, $error);
    }

    public function capture($input)
    {
        $this->model = HdfcGatewayDal::retrieve($input['txn']['id']);

        $this->id = $input['txn']['id'];

        $this->supportTxnRequest['type'] = 'capture';
        $this->supportTxnResponse['type'] = 'capture';

        $this->createSupportTxnRequestFields($input, HdfcGatewayAction::CAPTURE);

        //Logging
        $log_content = $this->stripSensitive($this->supportTxnRequest) + array('message'=>'Support Request Sent');
        $this->trace->debug(TraceEvent::GATEWAY_SUPPORT_REQUEST, $log_content);

        $this->runRequestResponseFlow(
            $this->supportTxnRequest,
            $this->supportTxnResponse);

        $error=array();
        if($this->supportTxnResponse['error'])
        {
            $error = HdfcGatewayErrorHandler::parseErrorInString($this->supportTxnResponse['error']['result']);
            if ($error === false)
                    $error = HdfcGatewayErrorHandler::unknownError();
        }
        
        $status = $this->persistAfterSupportTxn('capture');

        return array($status, $error);
    }

    /**
     * Collect all fields to be sent for
     * transaction refund/capture
     * 
     * @param  array $input 
     * Contains the 'txn' details
     */
    protected function createSupportTxnRequestFields($input, $action)
    {
        $txn = $input['txn'];
        $card = $input['txn']['card'];

        $data = &$this->supportTxnRequest['data'];

        // Collect credentials
        list($data['ID'], $data['password']) = static::getCredentials();

        $data['action'] = $action;

        // Convert amount from integer to decimal
        $data['amt'] = $txn['amount']/100;

        $data['currencycode'] = self::INR_CODE;

        $data['member'] = $card['name'];

        $data['transid'] = $this->model->transactionid;

        $data['trackid'] = $this->id;

        // Set udf fields
        $data['udf1'] = $data['udf2'] = $data['udf3'] = $data['udf4'] = $data['udf5'] = '';
    }

    public function void()
    {
        ;
    }

    /**
     * Stips sensitive data before calling trace class to avoid sensitive data from logging
     */
    private function stripSensitive($content)
    {   
        if(isset($content['data']))
        {
            $data = $content['data'];
            $data = array_diff_key($data, array_flip($this->stripFieldsList));
            $content = array('data' => $data) + $content;
        }   
    
        return $content;
    }
}