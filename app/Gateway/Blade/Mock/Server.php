<?php

namespace RZP\Gateway\Blade\Mock;

use RZP\Gateway\Base;
use Lib\Formatters\Xml;
use RZP\Gateway\Blade\Mock\Xml\Response;

class Server extends Base\Mock\Server
{
    public function acs(array $input)
    {
        $this->validateAuthenticateInput($input);

        $response = [
            F::MD => $input[F::MD],
            F::PA_RES => 'eNpVUttygjAQfc9XMP0AkiAw',
            F::TERM_URL => $input[F::TERM_URL]
        ];

        $this->content($response, 'acs');

        return $response;
    }

    public function authorize($input)
    {
        $input = $this->xmlToArray($input);

        // $this->validateAuthInput($input);
        $VERes = $this->getDefaultVERes($input);

        $this->switchAuthorizeCases($input, $VERes);

        return $this->makeXmlResponse($VERes);
    }

    protected function getDefaultVERes($input)
    {
        $content = $input;

        unset($content['Message']['VEReq']);

        $content['Message']['VERes'] = [
            'version' => '1.0.2',
            'CH'  => [
                'enrolled' => 'Y',
                'acctID'   => CardNumber::getAccId($input['Message']['VEReq']['pan']),
            ],
            'url' => $this->route->getUrl('mock_acs', ['gateway' => 'cybersource']),
            'protocol' => 'ThreeDSecure'
        ];

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

    protected function getResponse(array $input)
    {
        $responseClass = new Response();

        $paymentId = $input['Message']['@attributes']['id'];

        $cardNo = $input['Message']['VEReq']['pan'];

        switch($cardNo)
        {
            case CardNumber::VALID_ENROLL_NUMBER:
                return $responseClass->enrolledValidResponse($paymentId);
            case CardNumber::VALID_NOT_ENROLL_NUMBER:
                return $responseClass->notEnrolledValidResponse($paymentId);
            case CardNumber::INVALID_MEESGAE:
                return $responseClass->differentMessageResponse($paymentId);
            case CardNumber::BLANK_MEESGAE:
                return $responseClass->blankMessageResponse($paymentId);
            case CardNumber::INVALID_VERSION:
                return $responseClass->invalidVersionFormat($paymentId);

        }
    }

    protected function xmlToArray($xml)
    {
        $e = null;
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
