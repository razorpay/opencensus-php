<?php

namespace Gateway\Atom;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\BaseGateway;
use Gateway\Atom;
use Models\Card;

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
    public function capture(array $input)
    {
        parent::authorize($input);

        $time = date('d/m/Y h:m:s');
        // Replace space with '%20'
        // $time = str_replace(' ', '%20', $time);

        $url = Urls::ATOM_TEST_URL;

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

        $response = $this->postRequest($request);

        $data = $this->xmlToArray($response->body);

        $url = $data['url'];
        $fields = array(
            'ttype'         => 'NBFundTransfer',
            'tempTxnId'     => $data['tempTxnId'],
            'token'         => $data['token'],
            'txnStage'      => '1');

        $this->createAtomEntity($input, $data);

        // Cannot use http_build_query php function because
        // params contain '%' sign which gets messed up by that function
        $queryStr = $this->buildGetQueryString($fields);

        $url = Urls::ATOM_TEST_URL.'?'.$queryStr;

        $data = array('redirectUrl' => $url);

        return $data;
    }

    public function callback(array $input)
    {
        $paymentId = $input['mer_txn'];

        $payment = $input['payment'];
        unset($input['payment']);

        $atom = Atom\Entity::findOrFail($payment['id']);
        $atom->setCallbackData($input);

        if ($paymentId !== $payment['public_id'])
        {
            throw new Exception\LogicException(
                'Payment public id and atom merchant txn id do not match. Payment public_id: ' .
                $payment['public_id'], ' atom merchant txn id: ' . $input['mer_txn']);
        }

        $atomFCode = (isset($input['f_code'])) ? $input['f_code'] : '';
        if ($atomFCode === 'Ok')
        {
            $atom->setSuccess(true);
        }
        else if ($atomFCode === 'F')
        {
            $atom->setSuccess(false);
            $this->error = true;
            $this->exception = new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED);
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

        if ($this->error)
        {
            throw $this->exception;
        }
    }

    public function createAtomEntity($input, $data)
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
            if (Utility::checkTimeout($e))
            {
                $this->error = true;

                $response['content'] = '';
                // @todo: set error
                return;
            }
            else
            {
                throw $e;
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
