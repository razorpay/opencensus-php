<?php

namespace Gateway\Mobikwik\Mock;

use Carbon\Carbon;
use Gateway\Mobikwik;
use Gateway\Base;
use Gateway\Base\Action;
use Models\Payment;

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
        $payment = (new Payment\Repository)->findOrFailPublic($id);

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

    public function checkUser($input)
    {
        // verify checksum.

        assert ($input['action'] === 'existingusercheck');

        $content = array(
            'messagecode'       => '500',
            'status'            => 'SUCCESS',
            'statuscode'        => '0',
            'statusdescription' => 'User Exists',
            'emailaddress'      => 'random@gmail.com',
            'range'             => '100-500',
            'statuscode'        => '0',
            'statusdescription' => 'User exists',
            'nonzeroflag'       => 'y'
        );

        $responseContent = $this->generateXMLResponse($content);

        return $this->makeResponse($responseContent);
    }

    public function otpGenerate($input)
    {
        // verify checksum.

        $content = array(
            'messagecode'       => '504',
            'status'            => 'SUCCESS',
            'statuscode'        => '0',
            'statusdescription' => 'Message Sent to xxxxxx784',
            'emailaddress'      => 'random@gmail.com',
            'range'             => '100-500',
            'statuscode'        => '0',
            'statusdescription' => 'User exists',
            'nonzeroflag'       => 'y',
            'checksum'          => 'a44e07b54a5df145d722407617318c2f8a7d6fefd2ab1df9b4766b768741b6ad',
        );

        $responseContent = $this->generateXMLResponse($content);

        return $this->makeResponse($responseContent);
    }

    public function otpSubmit($input)
    {
        $content = array(
            'messagecode'       => '503',
            'status'            => 'SUCCESS',
            'statuscode'        => '0',
            'statusdescription' => 'Amount Debited',
            'debitedamount'     => $input['amount'],
            'balanceamount'     => random_integer(4),
            'checksum'          => '0e897831293479380e7cb6b77d60ecec0c75f8ccb',
        );

        // OTP 131313 is for insufficient balance
        if ($input['otp'] === '131313')
        {
            $content = array(
                'status'            => 'FAILURE',
                'statuscode'        => '33',
                'statusdescription' => Mobikwik\ResponseCode::getResponseMessage('33')
            );
        }

        // OTP 121212 is for incorrect OTP
        if ($input['otp'] === '121212')
        {
            $content = array(
                'status'            => 'FAILURE',
                'statuscode'        => '164',
                'statusdescription' => Mobikwik\ResponseCode::getResponseMessage('164')
            );
        }

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
