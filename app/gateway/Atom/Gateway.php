<?php

namespace Gateway\Atom;

use Carbon\Carbon;
use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Trace\Trace;
use Trace\TraceCode;
use Gateway\BaseGateway;
use Gateway\Atom;

class Gateway extends BaseGateway
{
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

        $request = $this->createPaymentRequestArray($input);

        // Send first request.
        $response = [];
        $response = $this->runRequestResponseFlow($request, $response);

        $data = $this->processPaymentInitiationResponse($response, $input);

        $url = $this->createAtomRedirectUrl($data);
        // \Log::info($url);

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
        // \Log::info(json_encode($input, JSON_PRETTY_PRINT));

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

        return $this->getCallbackResponse($atom);
    }

    public function refund(array $input)
    {
        parent::refund($input);
    }

    public function verify(array $input)
    {
        $createdAt = $input['payment']['created_at'];

        $tdate = Carbon::createFromTimestamp($createdAt, 'Asia/Kolkata')->format('Y-m-d');

        $fields = array(
            'merchantid'        => $input['terminal']['gateway_merchant_id'],
            'merchanttxnid'     => $input['payment']['public_id'],
            'amt'               => $input['payment']['amount'] / 100,
            'tdate'             => $tdate);

        $request['url'] = Urls::VERIFY_URL;
        $request['content'] = $fields;
        $request['action'] = 'verify';
        $request['method'] = 'get';

        $response = [];
        $response = $this->runRequestResponseFlow($request, $response);

        // Convert xml body to array of fields
        $data = $this->verifiedXmlToArray($response['response']->body);

        $values = [];

        $atomStatus = ($data['VERIFIED'] === 'SUCCESS');

        $id = $input['payment']['id'];
        $payment = (new Atom\Repository)->find($id);

        $status = (bool) $payment['success'];

        $res = ['match' => true];

        if (($status !== $atomStatus) or
            ($data['BID'] !== $payment['bank_payment_id']))
        {
            $res['match'] = false;
            $res['status'] = ['rzp' => $status, 'gateway' => $atomStatus];
            $res['gateway_data'] = $data;
            $res['payment'] = $payment->toArray();
            $res['payment_id'] = $input['payment']['id'];
            $res['gateway'] = $input['payment']['gateway'];
        }

        if ($res['match'] === false)
        {
            throw new Exception\PaymentVerificationException($res);
        }

        return $res;
    }

    protected function processPaymentInitiationResponse($response, $input)
    {
        // Convert xml body to array of fields
        $data = $this->xmlToArray($response['response']->body);

        $method = $input['payment']['method'];

        $ttype = Transaction::getType($method);

        // Fields returned from first request
        $fields = array(
            'ttype'         => $ttype,
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

        $exception = null;

        if ($atomFCode === 'Ok')
        {
            $atom->setSuccess(true);
        }
        else
        {
            $atom->setSuccess(false);
            $atom->saveOrFail();

            if ($atomFCode === 'F')
            {
                $exception = new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
            }
            else
            {
                throw new Exception\LogicException(
                    'Atom f_code returned in callback has unrecognized value. Atom f_code: ' . $atomFCode);
            }
        }

        $data = array(
            'bank_payment_id'       => $input['bank_txn'],
            'bank_name'             => $input['bank_name']);

        if (isset($input['desc']))
        {
            $data['gateway_result_description'] = $input['desc'];
        }

        if (isset($input['discriminator']))
        {
            $data['method'] = $input['discriminator'];
        }

        $atom->fill($data);

        $atom->saveOrFail();

        if ($exception !== null)
        {
            throw $exception;
        }
    }

    protected function getCallbackResponse($atom)
    {
        $method = $atom->method;

        $data = array();

        if ($method === Method::NETBANKING)
        {
            $data['method'] = 'netbanking';
        }
        else if ($method === Method::DEBITCARD)
        {
            $data['method'] = 'card';
            $data['card']['type'] = 'debit';
        }
        else if ($method === Method::CREDITCARD)
        {
            $data['method'] = 'card';
            $data['card']['type'] = 'credit';
        }
        else
        {
            // @todo: trace here
        }

        return $data;
    }

    protected function createAtomRedirectUrl($data)
    {
        // Cannot use http_build_query php function because
        // params contain '%' sign which gets messed up by that function
        $queryStr = $this->buildGetQueryString($data);

        $url = Urls::getDomain($this->mode).Urls::PAYMENT_URL.'?'.$queryStr;

        // This is the url to which the customer is redirected.
        // Here, on atom's provided url, the bank choice is auto-submitted
        // and bank login page comes. When customer logins and bank txn is complete,
        // it's redirected to atom's site and then redirected back to our callbackUrl
        // we provided earlier via 'ru' field.

        return $url;
    }

    protected function createAtomEntity($input, $data)
    {
        $bankCode = null;

        if (isset($this->request['content']['bankid']))
        {
            $bankCode = $this->request['content']['bankid'];
        }

        $method = $this->getAtomPaymentMethod($input);

        $attributes = array(
            'id'        => $input['payment']['id'],
            'token'     => $data['token'],
            'method'    => $method,
            'bank_code' => $bankCode,
            'gateway_payment_id' => $data['tempTxnId']);

        $atom = new Atom\Entity($attributes);

        $atom->saveOrFail();
    }

    public function postRequest($request)
    {
        $this->setTerminalInRequest($request);

        $str = $this->buildGetQueryString($request['content']);
        $request['url'] .= '?'.$str;
        $request['content'] = [];
        // echo $request['url'];die();

        $this->response = $this->sendGatewayRequest($request);

        return $this->response;
    }

    protected function createPaymentRequestArray($input)
    {
        $time = date('d/m/Y h:m:s');
        // Replace space with '%20'
        $time = str_replace(' ', '%20', $time);

        $method = $input['payment']['method'];

        $ttype = Transaction::getType($method);

        $content = array(
            'ttype'         =>  $ttype,
            'amt'           =>  $input['payment']['amount'] / 100,
            'txncurr'       =>  'INR',
            'txnscamt'      =>  '0',
            'clientcode'    =>  urlencode(base64_encode('123')),
            'txnid'         =>  $input['payment']['public_id'],
            'date'          =>  $time,
            'custacc'       =>  '123456789012',
        );

        if ($method === 'netbanking')
        {
            $content['bankid'] = $this->getBankId($input);
        }

        if ($method === 'card')
        {
            $content['mdd'] = $this->getMddField($input);
        }

        $content['ru'] = $input['callbackUrl'];

        $request['content'] = $content;
        $request['url'] = Urls::PAYMENT_URL;
        $request['action'] = 'authorize';

        return $request;
    }

    protected function getMddField($input)
    {
        $mdd = 'channelid=int';
        $mdd .= '|carddata=' . Card::encryptCardData($input['card']);
        $mdd .= '|cardhname=' . $input['card']['name'];
        $mdd .= '|cardtype=' . 'DC';

        return $mdd;
    }

    protected function getBankId($input)
    {
        $ifsc = $input['payment']['bank'];

        $atomBankCode = Bank::getAtomBankCode($ifsc);

        if ($this->mode === Mode::TEST)
        {
            $atomBankCode = '2001';
        }

        return $atomBankCode;
    }

    protected function getAtomPaymentMethod($input)
    {
        $method = $input['payment']['method'];

        if ($method === 'netbanking')
            return Method::NETBANKING;
        else if ($method === 'card')
            return null;
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
        $productId = $terminal['gateway_terminal_id'];

        // For TEST mode, replace any random terminal given with
        // atom test terminal
        if ($this->mode === MODE::TEST)
        {
            list($login, $pwd, $productId) = $this->getCredentials();
        }

        if ($request['action'] === 'authorize')
        {
            $request['content']['login'] = $login;
            $request['content']['pass'] = $pwd;
            $request['content']['prodid'] = $productId;
        }

        if ($request['action'] === 'verify')
        {
            $request['content']['merchantid'] = $login;
        }
   }

    protected function getCredentials()
    {
        return array(
            Config::TEST_LOGIN,
            Config::TEST_PASSWORD,
            Config::TEST_PRODUCT_ID);
    }

    protected function runRequestResponseFlow(array &$request, array &$response)
    {
        $request['options']['timeout'] = 30;
        $domain = ($this->mode === MODE::LIVE) ? Urls::LIVE_DOMAIN : Urls::TEST_DOMAIN;
        $request['url'] = $domain . $request['url'];

        $this->request = $request;
        $this->response = $response;

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

    protected function verifiedXmlToArray($data)
    {
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($data), $xmlValues);
        xml_parser_free($parser);

        return $xmlValues[0]['attributes'];
    }

    protected function buildGetQueryString($data)
    {
        $str = '';

        foreach ($data as $key => $value)
        {
            $str .= '&'.$key.'='.$value;
        }

        $str = substr($str, 1);

        return $str;
    }
}
