<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;
use RZP\Constants\Timezone;
use RZP\Gateway\Enach\Npci\Netbanking\Crypto;
use RZP\Gateway\Enach\Npci\Netbanking\RequestFields;
use RZP\Gateway\Enach\Npci\Netbanking\ResponseType;
use RZP\Gateway\Enach\Npci\Netbanking\ResponseFields;
use RZP\Gateway\Enach\Npci\Netbanking\ResponseXmlTags;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const ACCEPT_REF_NO = 12345678;

    protected $crypto;

    public function authorize($input)
    {
        $this->validateActionInput($input, 'auth');

        $this->setCryptoAttributes();

        $this->request($input, $this->action);

        $requestXmlString = $input['MandateReqDoc'];

        $this->crypto->verifySignature($requestXmlString, $this->crypto->getEncryptionPublicKey());

        $requestXml = (array) simplexml_load_string(trim($requestXmlString));

        $json = json_encode($requestXml);

        $requestArray = json_decode($json,true);

        $respType = ResponseType::SUCCESS;

        $this->content($respType, 'authorize');

        $content = [];

        $secureData = [];

        if($respType === ResponseType::SUCCESS)
        {
            $secureData = $this->getSecureData();

            $checksum = $this->generateHash($secureData);

            $checksum = $this->crypto->encrypt($checksum);

            $content[ResponseFields::CHECKSUM] = $checksum;
        }

        $responseData = $this->getResponseData($requestArray, $respType, $secureData);

        $responseXml = $this->getResponseOrErrorXml($responseData, $respType);

        if($respType === 'ErrorXMLWithoutCert')
        {
            $signedResponseXml = $responseXml;
        }
        else
        {
            $signedResponseXml = $this->crypto->addSignature($responseXml);
        }

        $callbackUrl = $this->route->getUrl('gateway_emandate_callback_npci_nb');

        $content[ResponseFields::RESPONSE_XML] = $signedResponseXml;

        $content[ResponseFields::RESPONSE_TYPE] = $respType;

        $request = [
            'url'     => $callbackUrl,
            'content' => $content,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        $requestJson = json_decode($input, true);

        $requestDetails = $requestJson[RequestFields::MANDATE_REQ_ID_LIST][0];

        $this->validateActionInput($requestDetails, 'verify');

        $gatewayPayment = $this->repo->enach->findAuthorizedPaymentByPaymentId($requestDetails[RequestFields::MANDATE_ID]);

        // TODO : move to common response fields class
        // TODO: check if response fields are empty string for success
        $content = [
            ResponseFields::TRANSACTION_STATUS => [
                [
                    ResponseXmlTags::MERCHANT_ID        => $requestDetails[RequestFields::MERCHANT_ID],
                    ResponseXmlTags::MANDATE_REQUEST_ID => $requestDetails[RequestFields::MANDATE_ID],
                    ResponseXmlTags::REQ_INIT_DATE      => $requestDetails[RequestFields::REQ_INIT_DATE],
                    ResponseXmlTags::VER_NPCI_REF_ID    => $gatewayPayment->getGatewayReferenceId() ?? Base\Entity::generateUniqueId(),
                    ResponseXmlTags::ACCEPTED           => 'true',
                    ResponseXmlTags::ACCEPT_REF_NO      => self::ACCEPT_REF_NO,
                    ResponseXmlTags::MANDATE_ID         => 'YESB12390812477',
                    'ReasonCode'                        => '000',
                    'ReasonDesc'                        => '',
                    'RejectBy'                          => '',
                    'ErrorCode'                         => '',
                    'ErrorDesc'                         => ''
                ]
            ]
        ];

        $this->content($content, 'verify');

        return $this->makeResponse($content);
    }

    private function getResponseData($requestArray, $respType, $secureData)
    {
        $npciId = Base\Entity::generateUniqueId();

        if($respType === ResponseType::SUCCESS)
        {
            $data = [
                'GrpHdr'      => [
                    'MsgId'          => '000f0f29dc27f00000101b09c5227457f17',
                    'CreDtTm'        => Carbon::now(Timezone::IST)->toIso8601String(),
                ],
                'OrgnlMsgInf' => [
                    'MndtReqId'      => $requestArray['MndtAuthReq']['Mndt']['MndtReqId'],
                    'NPCI_RefMsgId'  => $npciId,
                    'CreDtTm'        => $requestArray['MndtAuthReq']['GrpHdr']['CreDtTm'],
                    'MndtId'         => 'YESB12390812477'
                ],
                'AccptncRslt'  => [
                    'Accptd'         => $this->crypto->encrypt($secureData['Accptd']),
                    'AccptRefNo'     => $this->crypto->encrypt($secureData['AccptRefNo']),
                ],
                'RjctRsn'      => [
                    'ReasonCode'     => $this->crypto->encrypt($secureData['ReasonCode']),
                    'ReasonDesc'     => $this->crypto->encrypt($secureData['ReasonDesc']),
                    'RejectBy'       => $this->crypto->encrypt($secureData['RejectBy']),
                ],
                'IFSC'          => 'HDFC000000000001'
            ];
        }
        else if($respType === 'ErrorXMLWithoutCert')
        {
            $data = [
                'GrpHdr'       => [
                    'MsgId'          => '000f0f29dc27f00000101b09c5227457f17',
                    'CreDtTm'        => Carbon::now(Timezone::IST)->toIso8601String(),
                ],
                'OrigReqInfo'   => [
                    'MndtReqId'      => $requestArray['MndtAuthReq']['Mndt']['MndtReqId'],
                    'NPCI_RefMsgId'  => $npciId,
                    'CreDtTm'        => $requestArray['MndtAuthReq']['GrpHdr']['CreDtTm'],
                ],
                'MndtErrorDtls' => [
                    'ErrorCode'      => 209,
                    'ErrorDesc'      => 'Merchant MsgId is duplicate',
                    'RejectBy'       => 'BANK'
                ]
            ];
        }
        else
        {
            $data = [
                'GrpHdr'       => [
                    'MsgId'          => '000f0f29dc27f00000101b09c5227457f17',
                    'CreDtTm'        => Carbon::now(Timezone::IST)->toIso8601String(),
                ],
                'OrigReqInfo'   => [
                    'MndtReqId'      => $requestArray['MndtAuthReq']['Mndt']['MndtReqId'],
                    'NPCI_RefMsgId'  => $npciId,
                    'CreDtTm'        => $requestArray['MndtAuthReq']['GrpHdr']['CreDtTm'],
                ],
                'MndtErrorDtls' => [
                    'ErrorCode'      => 2022,
                    'ErrorDesc'      => 'Invalid XML Request',
                    'RejectBy'       => 'BANK'
                ]
            ];
        }

        $this->content($data, 'authorize_get_data');

        return $data;
    }

    private function getResponseOrErrorXml($data, $respType)
    {
        if ($respType === ResponseType::SUCCESS)
        {
            return $this->getResponseXml($data);
        }
        else
        {
            return $this->getErrorXml($data);
        }
    }

    private function getResponseXml($data)
    {
        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            .'<Document xmlns="http://npci.org/onmags/schema"/>');

        $mandateroot = $document->addChild('MndtAccptResp');

        $grp = $mandateroot->addChild( 'GrpHdr');

        $this->addChildren($data['GrpHdr'], $grp);

        $grp->addChild('ReqInitPty', 'NPCI');

        $accptd = $mandateroot->addChild( 'UndrlygAccptncDtls');

        $orgmsg = $accptd->addChild('OrgnlMsgInf');

        $this->addChildren($data['OrgnlMsgInf'], $orgmsg);

        $accptrst = $accptd->addChild('AccptncRslt');

        $this->addChildren($data['AccptncRslt'], $accptrst);

        $rejrsn = $accptrst->addChild(  'RjctRsn');

        $this->addChildren($data['RjctRsn'], $rejrsn);

        $dbtr = $accptrst->addChild('DBTR');

        $dbtr->addChild('IFSC' , $data['IFSC']);

        return $document->saveXML();
    }

    private function getErrorXml($data)
    {
        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            .'<Document xmlns="http://npci.org/onmags/schema"/>');

        $mandateroot = $document->addChild('MndtRejResp');

        $grp = $mandateroot->addChild( 'GrpHdr');

        $this->addChildren($data['GrpHdr'], $grp);

        $grp->addChild('ReqInitPty', 'NPCI');

        $info = $mandateroot->addChild( 'OrigReqInfo');

        $this->addChildren($data['OrigReqInfo'], $info);

        $error = $mandateroot->addChild( 'MndtErrorDtls');

        $this->addChildren($data['MndtErrorDtls'], $error);

        return $document->saveXML();
    }

    private function addChildren($data, $xml)
    {
        foreach ($data as $key => $value) {
            $xml->addChild($key,$data[$key]);
        }
    }

    protected function makeJsonResponse(array $content, $statusCode = 200)
    {
        $json = json_encode($content);

        $response = \Response::make($json, $statusCode);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function generateHash($content)
    {
        $hashString = $this->getStringToHash($content);

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA256, $string);
    }

    protected function getSecureData()
    {
        $data = [
            'Accptd'     => 'true',
            'AccptRefNo' => self::ACCEPT_REF_NO,
            'ReasonCode' => '',
            'ReasonDesc' => '',
            'RejectBy'   => '',
        ];

        $this->content($data, 'authorize_get_secure_data');

        return $data;
    }

    protected function setCryptoAttributes()
    {
        $this->crypto = new Crypto();

        $key  = file_get_contents(__DIR__ . '/keys/mock_key.pem');
        $cert = file_get_contents(__DIR__ . '/keys/cert.pem');
        $sign = file_get_contents(__DIR__ . '/keys/mock_cert.pem');

        $this->crypto->setPrivateKey($key);
        $this->crypto->setEncryptionCertificate($cert);
        $this->crypto->setSigningCertificate($sign);
    }
}
