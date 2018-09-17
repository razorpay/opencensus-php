<?php

namespace RZP\Gateway\Enach\Rbl\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function authorize($input)
    {
        $requestXml = (array) simplexml_load_string(trim($input['MandateReqDoc']));

        $json = json_encode($requestXml);

        $requestArray = json_decode($json,true);

        $responseData = $this->getResponseData($requestArray);

        $responseXml = $this->getResponseXml($responseData);

        $secureData = $this->getSecureData($responseData);

        $checksum = $this->generateHash($secureData);

        $callbackUrl = $this->route->getUrl('gateway_emandate_callback_npci_nb');

        $content = [
            'CheckSumVal'     => $checksum,
            'MandateRespDoc'  => $responseXml,
            'RespType'        => 'RespXML',
        ];

        $request = [
            'url'     => $callbackUrl,
            'content' => $content,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    private function getResponseData($requestArray)
    {
        return [
            'GrpHdr' => [
                    'MsgId' => '000f0f29dc27f00000101b09c5227457f17',
                    'CreDtTm' => Carbon::now()->toIso8601String(),
                    ],
            'OrgnlMsgInf' => [
                    'MndtReqId' => $requestArray['MndtAuthReq']['Mndt']['MndtReqId'],
                    'NPCI_RefMsgId' => $requestArray['MndtAuthReq']['GrpHdr']['MsgId'],
                    'CreDtTm' => $requestArray['MndtAuthReq']['GrpHdr']['CreDtTm'],
                    ],
            'AccptncRslt' => [
                    'Accptd' => 'true',
                    'AccptRefNo' => 22132232,
                    ],
            'RjctRsn' => [
                    'ReasonCode' => '',
                    'ReasonDesc' => '',
                    'RejectBy' => '',
                    ],
            'IFSC' => 'HDFC000000000001'
        ];
    }

    private function getResponseXml($data)
    {
        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            .'<Document xmlns="http://npci.org/onmags/schema"/>');

        $mandateroot = $document->addChild('MndtAccptResp');

        $grp = $mandateroot->addChild( 'GrpHdr');

        $this->addChildren($data['GrpHdr'], $grp);

        $grp->addChild('ReqInitPty');

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

    private function getChecksum($responsedata)
    {

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

    private function encrypt($data)
    {
        return $data;
    }

    protected function getSecureData($data)
    {
        return [
            $data['AccptncRslt']['Accptd'],
            $data['AccptncRslt']['AccptRefNo'],
            $data['RjctRsn']['ReasonCode'],
            $data['RjctRsn']['ReasonDesc'],
            $data['RjctRsn']['RejectBy'],
        ];
    }
}