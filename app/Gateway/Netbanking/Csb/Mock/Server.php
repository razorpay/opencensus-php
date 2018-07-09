<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Csb\Status;
use RZP\Gateway\Netbanking\Csb\RequestFields;
use RZP\Gateway\Netbanking\Csb\ResponseFields;

class Server extends Base\Mock\Server
{
    const BANK_ID   = 'CSB';

    protected $gatewayInstance = null;

    public function authorize($input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->verifyChecksum($request);

        $this->validateAuthorizeInput($request);

        $response = $this->getAuthorizeResponse($request);

        return $request[RequestFields::RETURN_URL] . '?' . http_build_query($response);
    }

    public function verify($input)
    {
        parent::verify($input);

        $request = $this->getVerifyRequest($input);

        $this->verifyChecksum($request);

        $this->validateActionInput($request, $this->action);

        $response = $this->getVerifyResponse();

        return $this->makeResponse($response);
    }

    /**
     * Using the gatewayInstance like a singleton object
     *
     * @param null $bankingType
     * @return mixed|null
     */
    protected function getGatewayInstance($bankingType = null)
    {
        if ($this->gatewayInstance === null)
        {
            $this->gatewayInstance = parent::getGatewayInstance($bankingType);
        }

        return $this->gatewayInstance;
    }

    protected function getAuthorizeResponse(array $request)
    {
        $date = Carbon::now(Timezone::IST)->format('d-M-Y H:i:s A');

        $narration = $request[RequestFields::PAYEE_ID] . ' ' . $request[RequestFields::BANK_REF_NUM];

        $content = [
            ResponseFields::PAYEE_ID     => $request[RequestFields::PAYEE_ID],
            ResponseFields::BANK_REF_NUM => $request[RequestFields::BANK_REF_NUM],
            ResponseFields::AMOUNT       => $request[RequestFields::AMOUNT],
            ResponseFields::MODE         => $request[RequestFields::MODE],
            ResponseFields::NARRATION    => $narration,
            ResponseFields::DATE_TIME    => $date,
            ResponseFields::TRAN_REF_NUM => 9999999999,
            ResponseFields::STATUS       => Status::SUCCESS,
            ResponseFields::BANKID       => self::BANK_ID,
            ResponseFields::CHNPGCODE    => $request[RequestFields::CHNPGCODE]
        ];

        $this->content($content, $this->action);

        return $content;
    }

    protected function getVerifyResponse()
    {
        $xmlRoot = "<Xml />";

        $response = [
            ResponseFields::VERIFICATION => Status::SUCCESS
        ];

        $this->content($response, $this->action);

        if (is_array($response) === false)
        {
            //
            // For the test case testPaymentVerifyHtmlResponse, we return response as html string
            //
            return $response;
        }

        //
        // Simple XML Element takes the values of the associate array
        // as the XML elements. Therefore, we need to flip the array
        // to ensure that the keys are selected instead.
        //
        $gatewayParam = array_flip($response);

        $gatewayParamXml = new \SimpleXMLElement($xmlRoot);

        //
        // Recursively walks through the array and adds each entry in $gatewayParam
        // into $gatewayParamXml as an XML child of the origin XML root.
        //
        array_walk_recursive($gatewayParam, [$gatewayParamXml, 'addChild']);

        return trim(explode('?>', $gatewayParamXml->asXML())[1]);
    }

    protected function getVerifyRequest(array $input)
    {
        $data = $input[RequestFields::POST_DATA];

        $base64DecodedRequestString = base64_decode($data);

        $requestArray = explode('|', $base64DecodedRequestString);

        return array_combine($this->getVerifyRequestFields(), $requestArray);
    }

    protected function getAuthorizeRequest(array $input)
    {
        $data = $input[RequestFields::POST_DATA];

        unset($input[RequestFields::POST_DATA]);

        $base64DecodedRequestString = base64_decode($data);

        $requestArray = explode('|', $base64DecodedRequestString);

        $decryptedResponseArray = array_combine($this->getAuthorizeRequestFields(), $requestArray);

        return array_merge($input, $decryptedResponseArray);
    }

    protected function verifyChecksum(array $request)
    {
        $checkSum = $request[RequestFields::CHECKSUM];

        unset($request[RequestFields::CHECKSUM]);

        $generatedCheckSum = $this->getChecksum($request);

        $this->compareHashes($checkSum, $generatedCheckSum);
    }

    protected function getChecksum(array $request)
    {
        $content = array_values($request);

        return $this->getGatewayInstance()->generateHash($content);
    }

    protected function getAuthorizeRequestFields()
    {
        return [
            RequestFields::CHNPGSYN,
            RequestFields::CHNPGCODE,
            RequestFields::PAYEE_ID,
            RequestFields::BANK_REF_NUM,
            RequestFields::AMOUNT,
            RequestFields::RETURN_URL,
            RequestFields::MODE,
            RequestFields::CHECKSUM
        ];
    }

    protected function getVerifyRequestFields()
    {
        return [
            RequestFields::CHNPGSYN,
            RequestFields::CHNPGCODE,
            RequestFields::PAYEE_ID,
            RequestFields::BANK_REF_NUM,
            RequestFields::AMOUNT,
            RequestFields::RETURN_URL,
            RequestFields::TRAN_REF_NUM,
            RequestFields::MODE,
            RequestFields::CHECKSUM
        ];
    }
}
