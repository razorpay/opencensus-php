<?php

namespace RZP\Gateway\NpciPaySecure;

use SoapFault;
use SoapHeader;
use SoapVar;
use SoapClient;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;

trait RequestHandlerTrait
{

    //-------------- Check BIN2 request ------------------------------------

    /**
     * @throws Exception\GatewayErrorException
     */
    protected function checkBin2()
    {
        $requestArray = $this->getCheckBin2RequestArray();

        $command = Constants::COMMAND_CHECKBIN2;

        $response = $this->sendRequest($command, $requestArray);

        return $response;
    }

    protected function getCheckBin2RequestArray(): array
    {

        $cardNumber = $this->input['card']['number'];

        $cardBin = substr($cardNumber, 0, 9);

        $body = [
            Fields::CARD_BIN => $cardBin,
        ];

        return $this->getRequestContents($body);
    }
    //-------------- Check BIN2 request end ----------------------------------

    //---------------- Soap Request related functions ------------------------
    protected function sendRequest($command, $params)
    {
        $headers        = $this->getRequestHeaders();

        $requestBody    = $this->getRequestBody($params, $command);

        $request = [
            'headers' => $headers,
            'body'    => $requestBody,
        ];

        try
        {
            // TODO: [OPTIONAL] Set trace and exceptions to false before pushing to production
            $soapClientOptions = [
                'trace'               => true,
                'exceptions'          => true,
                'connection_timeout'   => 30,
            ];

            ini_set("default_socket_timeout", 30);

            $soapClient = new SoapClient($this->wsdlDetails['wsdl_file'], $soapClientOptions);

            $soapClient->__setSoapHeaders($headers);

            $response = $soapClient->CallPaySecure($requestBody);

            // todo: Remove this
            $this->printLastSoapXml($soapClient);
        }
        catch (SoapFault $sf)
        {
            if (Utility::checkSoapTimeout($sf))
            {
                throw new Exception\GatewayTimeoutException($sf->getMessage(), $sf);
            }
            else
            {
                throw $sf;
            }
        }

        $arrayResponse = $this->convertToArray($response);

        $this->app['trace']->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'response' => $arrayResponse
            ]
        );

        return $arrayResponse;
    }

    protected function getRequestHeaders()
    {
        $token = new SoapVar($this->config['token'], XSD_STRING, null, null, Fields::TOKEN, '');
        $version = new SoapVar(Constants::VERSION, XSD_STRING, null, null, Fields::VERSION, '');
        $callerId = new SoapVar($this->config['caller_id'], XSD_STRING, null, null, Fields::CALLER_ID, '');

        $userId = new SoapVar($this->config['userid'], XSD_STRING, null, null, Fields::USER_ID, '');
        $userPassword = new SoapVar($this->config['password'], XSD_STRING, null, null, Fields::USER_PASSWORD, '');

        $userCredentials = new SoapVar(
            [$userId, $userPassword],
            SOAP_ENC_OBJECT,
            null,
            null,
            Fields::USER_CREDENTIALS,
            ''
        );

        $credentials = new SoapVar(
            [$token, $version, $callerId, $userCredentials],
            SOAP_ENC_OBJECT,
            null,
            null,
            Fields::USER_CREDENTIALS,
            ''
        );

        $header = new SoapHeader(
            $this->wsdlDetails['header']['namespace'],
            $this->wsdlDetails['header']['key'],
            $credentials
        );

        return $header;
    }

    protected function getRequestBody($params, $command)
    {
        $xmlArray = [];

        $strXML = XmlSerializer::getXmlStringFromArray($params);

        $xmlArray['strCommand'] = $command;

        $xmlArray['strXML'] = $strXML;

        return $xmlArray;
    }

    protected function getRequestContents(array $data)
    {
        $merchantCreds = [
            Fields::PARTNER_ID        => $this->config['partner_id'],
            Fields::MERCHANT_PASSWORD => $this->config['merchant_password'],
        ];

        return array_merge($data, $merchantCreds);
    }

    protected function convertToArray($response)
    {
        $response = $response->CallPaySecureResult;

        $xmlResponse = simplexml_load_string(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $response));

        $xmlResponseArray = XmlSerializer::xmlToArray($xmlResponse);

        return $xmlResponseArray;
    }
    //---------------- Soap Request related functions end --------------------

    //---------------- REMOVE THIS LATER ----------------------------
    protected function printLastSoapXml($soapClient)
    {
        $xml = $soapClient->__getLastRequest();
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        echo "<pre>".htmlentities($dom->saveXML())."</pre>";
        die;
    }
    //---------------- REMOVE THIS LATER END -------------------------
}
