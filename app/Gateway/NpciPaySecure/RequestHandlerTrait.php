<?php

namespace RZP\Gateway\NpciPaySecure;

use SoapFault;
use SoapHeader;
use SoapClient;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;

trait RequestHandlerTrait
{
    //-------------- Check BIN2 request ------------------------------------
    protected function checkBin2()
    {
        $requestArray = $this->getCheckBin2RequestArray();

        $command = Constants::COMMAND_CHECKBIN2;

        $response = $this->sendRequest($requestArray, $command);
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
    protected function sendRequest($params, $command)
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

            $soapCallOptions = [
                'uri' => Url::DOMAIN
            ];

            $response = $soapClient->__soapCall(
                $this->wsdlDetails['body']['key'],
                array($request),
                $soapCallOptions);
        }
        catch (SoapFault $sf)
        {
            $this->handleGatewayFailure($sf->getCode(), $sf->getMessage());

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
        $headerBody = [
            Fields::TOKEN            => $this->config['token'],
            Fields::VERSION          => Constants::VERSION,
            Fields::CALLER_ID        => $this->config['caller_id'],
            Fields::USER_CREDENTIALS =>
                [
                    Fields::USER_ID       => $this->config['userid'],
                    Fields::USER_PASSWORD => $this->config['password'],
                ],
        ];

        $header = new SoapHeader($this->wsdlDetails['header']['namespace'],
            $this->wsdlDetails['header']['key'],
            $headerBody);

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
}
