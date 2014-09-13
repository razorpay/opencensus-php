<?php

namespace Gateway\Atom;

use Carbon\Carbon;
use EE\Exception;
use Gateway\BaseGateway;
use Models\Card;

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

        $datenow = date("d/m/Y h:m:s");
        $modifiedDate = str_replace(" ", "%20", $datenow);
        $url = Urls::ATOM_TEST_URL;

        $request['content'] = array(
            'login'         =>  Config::TEST_LOGIN,
            'pass'          =>  Config::TEST_PASSWORD,
            'ttype'         =>  'NBFundTransfer',
            'prodid'        =>  'NSE',
            'amt'           =>  $input['txn']['amount'] / 100,
            'txncurr'       =>  'INR',
            'txnscamt'      =>  '0',
            'clientcode'    =>  urlencode(base64_encode('123')),
            'txnid'         =>  $input['txn']['id'],
            'ru'            =>  'ur',
            'date'          =>  $modifiedDate,
            'custacc'       =>  '123456789012',
            );

        $request['url'] = Urls::ATOM_TEST_URL;

        $response = $this->postRequest($request);

        $xmlObjArray     = $this->xmltoarray($returnData);

        $url = $xmlObjArray['url'];
        $postFields  = "";
        $postFields .= "&ttype=".$_POST['TType'];
        $postFields .= "&tempTxnId=".$xmlObjArray['tempTxnId'];
        $postFields .= "&token=".$xmlObjArray['token'];
        $postFields .= "&txnStage=1";
        $url = $payment->url."?".$postFields;
        $this->writeLog($url."\n");
        header("Location: ".$url);
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

    function writeLog($data)
    {
        $fileName = date("Y-m-d").".txt";
        $fp = fopen("log/".$fileName, 'a+');
        $data = date("Y-m-d H:i:s")." - ".$data;
        fwrite($fp,$data);
        fclose($fp);
    }

    function xmltoarray($data){
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
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