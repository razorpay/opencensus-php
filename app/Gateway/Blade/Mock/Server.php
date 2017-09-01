<?php

namespace RZP\Gateway\Blade\Mock;

use RZP\Gateway\Base;
use Lib\Formatters\Xml;

class Server extends Base\Mock\Server
{
    public function acs(array $input)
    {
        $this->validateAuthenticateInput($input);

        // TODO: vaidate PaReq

        $paResContent = $this->getPaResContent($input);

        $this->content($paResContent, 'pares');

        $response = [
            'MD'      => $input['MD'],
            'PaRes'   => $this->getPaResXml($paResContent),
            'TermUrl' => $input['TermUrl']
        ];

        $this->content($response, 'acs');

        return $response;
    }

    private function getPaResContent($input)
    {
        // Implement
    }

    private function getPaResXml($content)
    {
        $xml = Xml::create('ThreeDSecure', $content);

        // TODO: Sign XML here

        return $xml;
    }

    public function authorize($input)
    {
        $input = $this->xmlToArray($input);

        // $this->validateAuthInput($input);
        $VERes = $this->getVERes($input);

        $this->switchAuthorizeCases($input, $VERes);

        return $this->makeXmlResponse($VERes);
    }

    protected function getVERes(array $input)
    {
        $responseClass = new Response\Vereq($this->route);

        $content = $input;

        $cardNo = $input['Message']['VEReq']['pan'];

        $paymentId = $input['Message']['@attributes']['id'];

        unset($content['Message']['VEReq']);

        switch($cardNo)
        {
            case CardNumber::VALID_ENROLL_NUMBER:
                $content['Message']['VERes'] = $responseClass->enrolledValidResponse($paymentId, $cardNo);
            case CardNumber::VALID_NOT_ENROLL_NUMBER:
                $content['Message']['VERes'] =  $responseClass->notEnrolledValidResponse($paymentId, $cardNo);
            case CardNumber::INVALID_MEESGAE:
                $content['Message']['@attributes']['id'] = 'RANDOM';
                $content['Message']['VERes'] = $responseClass->differentMessageResponse($paymentId, $cardNo);
            case CardNumber::BLANK_MEESGAE:
                $content['Message']['VERes'] = $responseClass->blankMessageResponse($paymentId, $cardNo);
            case CardNumber::INVALID_VERSION:
                $content['Message']['VERes'] = $responseClass->invalidVersionFormat($paymentId, $cardNo);
        }

        return $content;
    }

    protected function switchAuthorizeCases($input, &$response)
    {

    }

    protected function makeXmlResponse($content)
    {
        $xml = Xml::create('ThreeDSecure', $content);

        $response = parent::makeResponse($xml);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }

    protected function xmlToArray($xml)
    {
        $res = null;

        try
        {
            $res = simplexml_load_string($xml);

            return json_decode(json_encode($res), true);
        }
        catch (\Exception $e)
        {
            assertTrue('XML decode failed');
        }
    }
}
