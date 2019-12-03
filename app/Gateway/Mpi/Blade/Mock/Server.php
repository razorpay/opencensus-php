<?php

namespace RZP\Gateway\Mpi\Blade\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use Lib\Formatters\Xml;
use RZP\Gateway\FirstData\Mock\Constants;

class Server extends Base\Mock\Server
{
    public function acs(array $input)
    {
        $this->validateAuthenticateInput($input);

        // TODO: vaidate PaReq
        $paResContent = $this->getPaResContent($input);

        $response = [
            'MD'      => $input['MD'],
            'PaRes'   => base64_encode($this->getPaResXml($paResContent, $input)),
            'TermUrl' => $input['TermUrl']
        ];

        return $response;
    }

    public function callback($input)
    {
        $content = $this->acs($input);

        $response = http_build_query($content);

        return $this->makeResponse($response);
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

        $pares = gzcompress($paResXml);

        return $pares;
    }

    public function authorize($input)
    {
        $input = $this->xmlToArray($input);

        // $this->validateAuthInput($input);
        $VERes = $this->getVERes($input);

        $this->content($VERes,'authenticate');

        return $this->makeXmlResponse($VERes);
    }

    protected function getPaRes(array $content)
    {
        $responseClass = new Response\Pareq($this->route);

        $acctId = $content['Message']['PAReq']['CH']['acctID'];

        $cardNo = CardNumber::getCardNumberFromAccId($acctId);

        switch ($cardNo)
        {
            case CardNumber::VALID_ENROLL_NUMBER:
                $content['Message']['PARes'] = $responseClass->enrolledValidResponse($content);
                break;
            case CardNumber::INTERNATIONAL_VISA_ENROLLED:
                $content['Message']['PARes'] = $responseClass->enrolledValidVisaResponse($content);
                break;
            case CardNumber::INTERNATIONAL_VISA:
                $content['Message']['PARes'] = $responseClass->internationalVisaResponse($content);
                break;
            case CardNumber::INTERNATIONAL_MASTER:
            case CardNumber::INTERNATIONAL_MAESTRO:
                $content['Message']['PARes'] = $responseClass->internationalMasterResponse($content);
                break;
            case CardNumber::INVALID_ECI:
                $content['Message']['PARes'] = $responseClass->invalidEci($content);
                break;
            case CardNumber::INVALID_PARES:
                $content['Message']['PARes'] = $responseClass->paresWithErrorCode();
                break;
            default:
                $content['Message']['PARes'] = $responseClass->enrolledValidResponse($content);
                break;
        }
        unset($content['Message']['PAReq']);

        $content['Message']['Signature'] = $this->getSignature();

        $this->content($content, 'acs');

        return $content;
    }

    public function getSignature()
    {
        $signatureXml = file_get_contents(__DIR__. '/' . 'Signature.xml');

        return $this->xmlToArray($signatureXml);
    }

    protected function getVERes(array $input)
    {
        $responseClass = new Response\Vereq($this->route);

        $content = $input;

        $VeReq = $input['Message']['VEReq'];
        $cardNo = $VeReq['pan'];

        $paymentId = $input['Message']['@attributes']['id'];

        unset($content['Message']['VEReq']);

        switch ($cardNo)
        {
            case CardNumber::INTERNATIONAL_VISA:
            case CardNumber::VALID_ENROLL_NUMBER:
            case CardNumber::INTERNATIONAL_MASTER:
            case CardNumber::INTERNATIONAL_VISA_ENROLLED:
            case CardNumber::INTERNATIONAL_MAESTRO:
            case CardNumber::INVALID_ECI:
            case CardNumber::INVALID_PARES:
                $content['Message']['VERes'] = $responseClass->enrolledValidResponse($paymentId, $cardNo);

                if ((isset($VeReq['Extension']['@attributes']['id']) === true) and
                    ($VeReq['Extension']['@attributes']['id'] === 'visa.3ds.india_ivr'))
                {
                    $content['Message']['VERes']['Extension'] = [
                        '@attributes' => [
                            'id' => 'visa.3ds.india_ivr',
                            'critical' => 'false'
                        ],
                        'npc356authdata' => [
                            'attribute' => [
                                '@attributes' => [
                                    'label'  => 'OTP',
                                    'length' => '6',
                                    'name'   => 'OTP2',
                                    'prompt' => 'Please enter OTP sent by your bank to your mobile',
                                    'type'   => 'N',
                                ],
                            ],
                        ],
                        'npc356authstatusmessage' => 'OTP has been sent to your mobile',
                        'npc356authdataencrypt' => [
                            '@attributes' => [
                                'mandatory' => 'FALSE'
                            ]
                        ],
                        'npc356authdataencrypttype' => '',
                        'npc356authdataencryptkeyvalue' => '',
                        'npc356itpstatus' => '',
                    ];
                }

                break;
            case CardNumber::VALID_NOT_ENROLL_NUMBER:
            case CardNumber::VALID_VISA_NOT_ENROLLED:
            case CardNumber::INTERNATIONAL_VISA_NE:
            case Constants::INTERNATIONAL_CARD:
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
            case CardNumber::UNKNOWN_ENROLLED:
                $content['Message']['VERes'] = $responseClass->unknownEnrolledResponse($paymentId);

                break;
            default:
                $content['Message']['VERes'] = $responseClass->enrolledValidResponse($paymentId, $cardNo);

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
