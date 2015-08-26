<?php

namespace Gateway\Mobikwik\Mock;

use Carbon\Carbon;
use Gateway\Mobikwik;
use Gateway\Base;
use Gateway\Base\Action;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $method = $this->getAuthMethod($input);

        $content = array(
            'statuscode'    => '0',
            'orderid'       => $input['orderid'],
            'amount'        => $input['amount'],
            'statusmessage' => 'Transaction completed Successfully',
            'mid'           => $input['mid'],
            'refid'         => '12345'
        );


        $content['checksum'] = $this->generateHash($content);
//        sd($content);

        $url = $input['redirecturl'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        $inputArray = [];

        parse_str($input, $inputArray);
        $input = $inputArray;
        $id = $input['orderid'];
//        $merchantId = $input['mid'];
        $payment = (new Mobikwik\Repository)->findByPaymentIdAndAction(
            $id, Action::AUTHORIZE);
        $content = array(
            'statuscode'    => '0',
            'orderid'       => $input['orderid'],
            'refid'         => '12345',
            'amount'        => $payment['amount'],
            'statusmessage' => 'success',
            'ordertype'     => 'payment'
        );

        $content['checksum'] = $this->generateHash($content);

        $responseContent = $this->generateXMLResponse($content);

        return $this->makeResponse($responseContent);
    }

    public function refund($input)
    {
        $inputArray = [];

        parse_str($input, $inputArray);
        $input = $inputArray;

        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $content = array(
            'txid'          => $input['txid'],
            'statuscode'    => '0',
            'status'        => 'success',
            'refid'         => '12345',
            'statusmessage' => 'Some message'
        );

        $responseContent = $this->generateXMLResponse($content);

        return $this->makeResponse($responseContent);
    }

    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }


    protected function getAuthMethod($input)
    {
        $method = 'wallet';

        return $method;
    }

    protected function generateXMLResponse($content)
    {
        $content = array_flip($content);
        $xml = new \SimpleXMLElement('<wallet/>');
        array_walk_recursive($content, array($xml, 'addChild'));
        return ($xml->asXML());
    }

}
