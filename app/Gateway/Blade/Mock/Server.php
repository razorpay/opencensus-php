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
        $id = $input['MD'];
        $xid = base64_encode(str_pad($id, 20, '0', STR_PAD_LEFT));

        $this->repo = new Payment\Repository;

        $payment = $this->repo->findByPublicId('pay_' . $id);

        $created = $payment->getCreatedAt();

        $created = Carbon::createFromTimestamp($created, 'Asia/Kolkata')->format('Ymd H:m:s');

        // TODO make it dynamic and from array
        // TODO: Sign XML here

        return '<ThreeDSecure><Message id="pay_'.$id.'"><PARes id="a630671711"><version>1.0.2</version><Merchant><acqBIN>11111111111</acqBIN><merID>12AB,cd/34-EF  -g,5/H-67</merID></Merchant><Purchase><xid>'. $xid.'</xid><date>' . $created . '</date><purchAmount>50000</purchAmount><currency>356</currency><exponent>2</exponent></Purchase><pan>4532249047240</pan><TX><time>20160814 06:52:22</time><status>Y</status><cavv>AAABBJg0VhI0VniQEjRWAAAAAAA=</cavv><eci>03</eci><cavvAlgorithm>2</cavvAlgorithm></TX></PARes><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#"><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod><Reference URI="#a630671711"><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod><DigestValue>iRI4FJUn4qkZIP+x66PKO7DQQ7o=</DigestValue></Reference></SignedInfo><KeyInfo><X509Data></X509Data></KeyInfo></Signature></Message></ThreeDSecure>';
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

                break;
            case CardNumber::VALID_NOT_ENROLL_NUMBER:
                $content['Message']['VERes'] =  $responseClass->notEnrolledValidResponse($paymentId, $cardNo);

                break;
            case CardNumber::INVALID_MEESGAE:
                $content['Message']['@attributes']['id'] = 'RANDOM';
                $content['Message']['VERes'] = $responseClass->differentMessageResponse($paymentId, $cardNo);

                break;
            case CardNumber::BLANK_MEESGAE:
                $content['Message']['VERes'] = $responseClass->blankMessageResponse($paymentId, $cardNo);

                break;
            case CardNumber::INVALID_VERSION:
                $content['Message']['VERes'] = $responseClass->invalidVersionFormat($paymentId, $cardNo);

                break;
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
