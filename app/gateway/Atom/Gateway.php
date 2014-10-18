<?php

namespace Gateway\Atom;

use Carbon\Carbon;
use EE\Exception;
use Gateway\BaseGateway;
use Models\Card;

class Gateway extends BaseGateway
{
    protected $url = 'http://203.114.240.183/paynetz/epi/fts';

    var $Login='160';
    var $Password='Test@123';
    var $MerchantName='ATOM';
    var $TxnCurr='INR';
    var $TxnScAmt='0';

    protected $paymentRequest = array(
        'type' => 'payment',
        'fields' => array('ttype', 'prodid', 'amt', 'txncurr', 'txnscamt',
                          'clientcode', 'txnid', 'ru', 'date', 'custacc'),
        'xml' => '',
        'data' => array()
        );

    protected $paymentResponse = array(
        'type' => 'payment',
        'fields' => array())

    protected $error = false;

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

        $time = date('d/m/Y h:m:s');
        // Replace space with '%20'
        $time = str_replace(' ', '%20', $time);

        $url = Urls::ATOM_TEST_URL;

        $request['data'] = array(
            'login'         =>  $input['terminal']['gateway_merchant_id'],
            'pass'          =>  $input['terminal']['gateway_terminal_password'],
            'ttype'         =>  'NBFundTransfer',
            'prodid'        =>  'NSE',
            'amt'           =>  $input['txn']['amount'] / 100,
            'txncurr'       =>  'INR',
            'txnscamt'      =>  '0',
            'clientcode'    =>  urlencode(base64_encode('123')),
            'txnid'         =>  $input['txn']['id'],
            'ru'            =>  'ur',
            'date'          =>  $time,
            'custacc'       =>  '123456789012',
            );

        $request['url'] = Urls::ATOM_TEST_URL;

        $response = $this->postRequest($request);

        $data = $this->xmltoarray($response);

        $url = $data['url'];
        $fields = array(
            'ttype'         => 'NBFundTransfer',
            'tempTxnId'     => $data['tempTxnId'],
            'token'         => $data['token'],
            'txnStage'      => '1');

        $queryStr = http_build_query($fields);

        $url = Urls::ATOM_TEST_URL.'?'.$queryStr;

        return $url;
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

    protected function xmltoarray($data)
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
}

$processPayment = new ProcessPayment();
$processPayment->requestMerchant();
?>
    }



}