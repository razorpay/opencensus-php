<?php

namespace Gateway\Atom;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Trace\Trace;
use Trace\TraceCode;
use Gateway\BaseGateway;
use Gateway\Atom;

class Gateway extends BaseGateway
{
    protected $url = 'http://203.114.240.183/paynetz/epi/fts';

    protected $paymentRequest = array(
        'type' => 'payment',
        'fields' => array('ttype', 'prodid', 'amt', 'txncurr', 'txnscamt',
                          'clientcode', 'txnid', 'ru', 'date', 'custacc'),
        'xml' => '',
        'data' => array()
        );

    protected $paymentResponse = array(
        'type' => 'payment',
        'fields' => array());

    protected $error = false;

    protected $exception;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $url = Urls::ATOM_TEST_URL;

        $request = $this->createTransactionRequestArray($input);

        $response = array();
        // Send first request.
        $response = $this->runRequestResponseFlow($request, $response);

        $data = $this->processPaymentInitiationResponse($response, $input);

        $url = $this->createAtomRedirectUrl($data);

        $data = array('redirectUrl' => $url);

        return $data;
    }

    public function capture(array $input = array())
    {
        parent::capture($input);
    }

    /**
     * We recieve callback from atom after bank net-banking transaction
     * is complete
     * @param  array    $input
     */
    public function callback(array $input)
    {
        // Get payment-id of the transaction
        $paymentId = $input['mer_txn'];

        $payment = $input['payment'];

        // Unset payment entity, because we have to save $input to db
        unset($input['payment']);

        $atom = Atom\Entity::findOrFail($payment['id']);

        // Set the data received from atom on atom payment entity
        $atom->setCallbackData($input);

        $this->validatePaymentIdReceived($paymentId, $payment);

        $this->processPaymentResponse($input, $atom);
    }

    protected function processPaymentInitiationResponse($response, $input)
    {
        // Convert xml body to array of fields
        $data = $this->xmlToArray($response['response']->body);

        // Fields returned from first request
        $fields = array(
            'ttype'         => 'NBFundTransfer',
            'tempTxnId'     => $data['tempTxnId'],
            'token'         => $data['token'],
            'txnStage'      => '1');

        // Save those fields with payment id
        $this->createAtomEntity($input, $data);

        return $fields;
    }

    protected function processPaymentResponse($input, $atom)
    {
        // Check if the transaction succeded or failed.
        $atomFCode = (isset($input['f_code'])) ? $input['f_code'] : '';

        $error = false;
        $exception = null;

        if ($atomFCode === 'Ok')
        {
            $atom->setSuccess(true);
        }
        else if ($atomFCode === 'F')
        {
            $atom->setSuccess(false);
            $error = true;
            $exception = new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
        else
        {
            $atom->setSuccess(false);
            $atom->saveOrFail();
            $this->error = true;
            throw new Exception\LogicException(
                'Atom f_code returned in callback has unrecognized value. Atom f_code: ' . $atomFCode);
        }

        $atom->setBankTransactionId($input['bank_txn']);
        $atom->setBankName($input['bank_name']);
        $atom->saveOrFail();

        if ($exception !== null)
        {
            throw $exception;
        }
    }

    protected function createAtomRedirectUrl($data)
    {
        // Cannot use http_build_query php function because
        // params contain '%' sign which gets messed up by that function
        $queryStr = $this->buildGetQueryString($data);

        $url = Urls::ATOM_TEST_URL.'?'.$queryStr;

        // This is the url to which the customer is redirected.
        // Here, on atom's provided url, the bank choice is auto-submitted
        // and bank login page comes. When customer logins and bank txn is complete,
        // it's redirected to atom's site and then redirected back to our callbackUrl
        // we provided earlier via 'ru' field.

        return $url;
    }

    protected function createAtomEntity($input, $data)
    {
        $attributes = array(
            'id' => $input['payment']['id'],
            'token' => $data['token'],
            'gateway_payment_id' => $data['tempTxnId']);

        $atom = new Atom\Entity($attributes);
        $atom->saveOrFail();
    }

    public function postRequest($request)
    {
        $this->setTerminalInRequest($request);

        $this->response = $this->sendGatewayRequest($request);

        return $this->response;
    }

    protected function createTransactionRequestArray($input)
    {
        $time = date('d/m/Y h:m:s');
        // Replace space with '%20'
        // $time = str_replace(' ', '%20', $time);

        $request['content'] = array(
            'ttype'         =>  'NBFundTransfer',
            'prodid'        =>  'NSE',
            'amt'           =>  $input['payment']['amount'] / 100,
            'txncurr'       =>  'INR',
            'txnscamt'      =>  '0',
            'clientcode'    =>  urlencode(base64_encode('123')),
            'txnid'         =>  $input['payment']['public_id'],
            'ru'            =>  $input['callbackUrl'],
            'date'          =>  $time,
            'custacc'       =>  '123456789012',
            'bankid'        =>  '2001',
            );

        $request['url'] = Urls::ATOM_TEST_URL;

        return $request;
    }

    /**
     * Validate that public payment id matches the expected
     * @param  string $paymentId
     * @param  array  $payment
     */
    protected function validatePaymentIdReceived($paymentId, $payment)
    {
        if ($paymentId !== $payment['public_id'])
        {
            throw new Exception\LogicException(
                'Payment public id and atom merchant txn id do not match. Payment public_id: ' .
                $payment['public_id'], ' atom merchant txn id: ' . $input['mer_txn']);
        }
    }

    protected function setTerminalInRequest(array & $request)
    {
        $terminal = $this->terminal;

        if ($terminal['gateway'] !== 'atom')
        {
            throw new Exception\InvalidArgumentException(
                'atom gateway: wrong terminal supplied. Gateway: ' . $terminal['gateway']);
        }

        $login = $terminal['gateway_merchant_id'];
        $pwd = $terminal['gateway_terminal_password'];

        // For 'test' mode, replace any random terminal given with
        // atom test terminal
        if ($this->mode === 'test')
        {
            list($login, $pwd) = $this->getCredentials();
        }

        $request['content']['login'] = $login;
        $request['content']['pass'] = $pwd;
    }

    protected function getCredentials()
    {
        return array(Config::TEST_LOGIN, Config::TEST_PASSWORD);
    }

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        $request['options']['timeout'] = 30;

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
            if (\Gateway\Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw $e;
            }
        }

        $response['xml'] = $response['response']->body;

        $this->validateResponseReceived($response);

        return $response;
    }

    /**
     * Validates that a proper response is received from atom gateway first request.
     * Throws an exception otherwise
     * @param  array $response
     */
    protected function validateResponseReceived($response)
    {
        if ($this->checkResponseStatusCode($response) === true)
        {
            return;
        }

        $gatewayErrorDesc = $response['xml'] . ' \n StatusCode: ' . $response['response']->status_code;

        $this->trace->error(
            TraceCode::GATEWAY_UNKNOWN_ERROR,
            ['description' => $gatewayErrorDesc]);

        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
            '',
            $gatewayErrorDesc);
    }

    protected function checkResponseStatusCode(& $response)
    {
        $status_code = (int) $response['response']->status_code;

        return ($status_code === 200);
    }

    protected function writeLog($data)
    {
        $fileName = date('Y-m-d').'.txt';
        $fp = fopen('log/'.$fileName, 'a+');
        $data = date('Y-m-d H:i:s').' - '.$data;
        fwrite($fp,$data);
        fclose($fp);
    }

    protected function xmlToArray($data)
    {
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($data), $xml_values);
        xml_parser_free($parser);

        $returnArray = array();
        $returnArray['url'] = $xml_values[3]['value'];
        $returnArray['tempTxnId'] = $xml_values[5]['value'];
        $returnArray['token'] = $xml_values[6]['value'];

        return $returnArray;
    }

    protected function buildGetQueryString($data)
    {
        $str = '';

        foreach ($data as $key => $value)
        {
            $str .= '&'.$key.'='.$value;
        }

        return $str;
    }
}
