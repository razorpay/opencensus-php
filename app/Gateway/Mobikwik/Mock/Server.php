<?php

namespace RZP\Gateway\Mobikwik\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mobikwik;
use RZP\Gateway\Wallet\Base\Otp;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'statuscode'    => '0',
            'orderid'       => $input['orderid'],
            'amount'        => $input['amount'],
            'statusmessage' => 'Transaction completed Successfully',
            'mid'           => $input['mid'],
            'refid'         => '12345'
        );

        $this->content($content);

        /**
         * This is a temporary fix as Mobikwik is not using refid while calculating checksum for callback response.
         */

        $checksumContent = $content;

        unset($checksumContent['refid']);

        $content['checksum'] = $this->generateHash($checksumContent);

        $url = $input['redirecturl'] . '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        $inputArray = [];

        parse_str($input, $inputArray);
        $input = $inputArray;
        $id = $input['orderid'];

        $payment = (new Payment\Repository)->findOrFailPublic($id);

        $amount = (string) number_format($payment['amount'] / 100, 2, '.', '');

        $content = array(
            'statuscode'    => '0',
            'orderid'       => $input['orderid'],
            'refid'         => '12345',
            'amount'        => $amount,
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

        assertTrue ($input['action'] === 'existingusercheck');

        $content = array(
            'messagecode'       => '500',
            'status'            => 'SUCCESS',
            'statuscode'        => '0',
            'statusdescription' => 'User Exists',
            'emailaddress'      => 'random@gmail.com',
            'range'             => '100-500',
            'nonzeroflag'       => 'y'
        );

        $responseContent = $this->generateXMLResponse($content);

        return $this->makeResponse($responseContent);
    }

    public function otpGenerate($input)
    {
        $this->request($input);

        // verify checksum.
        $content = array(
            'messagecode'       => '504',
            'status'            => 'SUCCESS',
            'statuscode'        => '0',
            'statusdescription' => 'Message Sent to xxxxxx784',
            'emailaddress'      => 'random@gmail.com',
            'range'             => '100-500',
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

        if ($input['otp'] === Otp::USER_DOES_NOT_EXIST)
        {
            $content = array(
                'status'            => 'FAILURE',
                'statuscode'        => '159',
                'statusdescription' => Mobikwik\ResponseCode::getResponseMessage('159'),
                );
        }

        if ($input['otp'] === Otp::INSUFFICIENT_BALANCE)
        {
            $content = array(
                'status'            => 'FAILURE',
                'statuscode'        => '33',
                'statusdescription' => Mobikwik\ResponseCode::getResponseMessage('33')
            );
        }

        if ($input['otp'] === Otp::INCORRECT)
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

    public function createUser($input)
    {
        $content = array(
            'messagecode'       => '502',
            'status'            => 'SUCCESS',
            'statuscode'        => '0',
            'statusdescription' => 'User Created',
            'checksum'          => '750014952183964866a5e4a2a59f9d632e4b500130611507fba5e39325df5f65',
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
