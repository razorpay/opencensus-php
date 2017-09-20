<?php

namespace RZP\Gateway\Blade\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
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
            'PaRes'   => base64_encode($this->getPaResXml($paResContent, $input)),
            'TermUrl' => $input['TermUrl']
        ];

        $this->content($response, 'acs');

        return $response;
    }

    private function getPaResContent($input)
    {
        // Implement
    }

    private function getPaResXml($content, $input)
    {
        $encodedRes = base64_decode($input['PaReq']);

        $pares = zlib_decode($encodedRes);

        $paresArray = $this->xmlToArray($pares);

        $id = $input['MD'];

        $xid = base64_encode(str_pad($id, 20, '0', STR_PAD_LEFT));

        $paRes = $this->getPaRes($paresArray);

        $paResXml = Xml::create('ThreeDSecure', $paRes);

        return $paResXml;
    }

    public function authorize($input)
    {
        $input = $this->xmlToArray($input);

        // $this->validateAuthInput($input);
        $VERes = $this->getVERes($input);

        return $this->makeXmlResponse($VERes);
    }

    protected function getPaRes(array $content)
    {
        $responseClass = new Response\Pareq($this->route);

        $acctId = $content['Message']['PAReq']['CH']['acctID'];

        $cardNo = CardNumber::getCardNumberFromAccId($acctId);

        switch($cardNo)
        {
            case CardNumber::VALID_ENROLL_NUMBER:
                $content['Message']['PARes'] = $responseClass->enrolledValidResponse($content);
                break;
        }

        unset($content['Message']['PAReq']);

        return $content;
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

                break;
            case CardNumber::VALID_NOT_ENROLL_NUMBER:
                $content['Message']['VERes'] =  $responseClass->notEnrolledValidResponse($paymentId, $cardNo);

                break;
            case CardNumber::INVALID_MEESAGE:
                $content['Message']['@attributes']['id'] = 'RANDOM';
                $content['Message']['VERes'] = $responseClass->differentMessageResponse($paymentId, $cardNo);

                break;
            case CardNumber::BLANK_MEESAGE:
                $content['Message']['VERes'] = $responseClass->blankMessageResponse($paymentId, $cardNo);

                break;
            case CardNumber::INVALID_VERSION:
                $content['Message']['VERes'] = $responseClass->invalidVersionFormat($paymentId, $cardNo);

                break;
        }

        return $content;
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
