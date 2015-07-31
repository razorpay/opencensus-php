<?php

namespace Gateway\Mobikwik;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'mobikwik';

    protected $sortRequestContent = false;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = array(
            'email'         => $input['payment']['email'],
            'amount'        => $input['payment']['amount'] / 100,
            'cell'          => $input['payment']['contact'],
            'orderid'       => $input['payment']['id'],
            'merchantname'  => $input['terminal']['gateway_terminal_id'],
            'mid'           => $input['termianl']['gateway_merchant_id'],
            'redirecturl'   => $input['callbackUrl'],
        );

        if ($this->mode === Mode::TEST)
        {
            $this->addTerminalDetailsInTest($content);
        }

        $content['checksum'] = $this->getPaymentHash($content);

        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => $content,
        );

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifySecureHash($input['gateway']);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $content['mid'] = $input['terminal']['gateway_merchant_id'];
        $content['orderid'] = $input['payment']['id'];
        $content['ver'] = 2;
        $content['checksum'] = $this->getHashForVerifyRequest(
                                        $content['mid'], $content['orderid']);

        $content = http_build_query($content);

        $request = array(
            'url'     => $this->getUrl(),
            'method'  => 'post',
            'content' => $content);

        $response = $this->sendGatewayRequest($request);

        // $recievedChecksum = validateChecksumMobikwik(
        //                                             $outputXmlObject->statuscode,
        //                                             $outputXmlObject->orderid,
        //                                             $outputXmlObject->refid,
        //                                             $outputXmlObject->amount,
        //                                             $outputXmlObject->statusmessage,
        //                                             $outputXmlObject->ordertype,
        //                                             $WorkingKey);

        $content = simplexml_load_string($response->body);

        $this->verifySecureHashForQueryRequest($content);

        // if(($OrderId == $outputXmlObject->orderid) && ($outputXmlObject->amount == $Amount) && ($outputXmlObject->checksum == $recievedChecksum)){

        //         //error_log("entered in verifcation final box");
        //         $return['statuscode'] = $outputXmlObject->statuscode;
        //         $return['orderid']  = $outputXmlObject->orderid;
        //         $return['refid']        = $outputXmlObject->refid;
        //         $return['amount']       = $outputXmlObject->amount;
        //         $return['statusmessage']    = $outputXmlObject->statusmessage;
        //         $return['ordertype']        = $outputXmlObject->ordertype;
        //         $return['checksum']     = $outputXmlObject->checksum;
        //         $return['flag'] = true;
        //     }
        //     //error_log("sending return = " . print_r($return));
        //     return $return;
    }

    public function refund(array $input)
    {
        parent::refund($input);
    }

    protected function addTerminalDetailsInTest(array & $content)
    {
        $content = array(
            'merchantname'  => 'TestMerchant',
            'mid'           => 'MBK9002',
        );
    }


    protected function getPaymentHash($content)
    {
        $fieldsInOrder = array(
            'cell',
            'email',
            'amount',
            'orderid',
            'redirecturl',
            'mid');

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($content);
    }

    protected function verifySecureHash($content)
    {
        $fieldsInOrder = array(
            'statuscode',
            'orderid',
            'amount',
            'statusmessage',
            'mid',
        );

        $hash = $content['checksum'];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generatedHash = $this->getHashOfArray($content, $fieldsInOrder);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                                                'Failed checksum verification');
        }
    }

    protected function verifySecureHashForQueryRequest($content)
    {
        $str = "'{" . $content['statuscode'] . "}'" .
               "'{" . $content['orderid'] . "}'" .
               "'{" . $content['refid'] . "}'" .
               "'{" . $content['amount'] . "}'" .
               "'{" . $content['statusmessage'] . "}'" .
               "'{" . $content['ordertype'] . "}'";

        $generatedHash = $this->getHashOfString($str);

        $hash = $content['checksum'];

        if ($generatedHash !== $hash)
        {
            throw new Exception\GatewayErrorException(
                          Error\ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED);
        }


    }

    protected function getHashForVerifyRequest($mid, $orderId)
    {
        $str = "'{$mid}''{$orderId}'";

        return $this->getHashOfString($str);
    }

    protected function getStringToHash($content, $glue = '')
    {
        return "'" . parent::getStringToHash($content, "''") . "'";
    }

    protected function getHashOfArray($content)
    {
        $str = "'" . $this->getStringToHash($orderedData, "''") . "'";

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return strtoupper(hash_hmac('sha256', $str, $secret, false));
    }
}

