<?php

namespace RZP\Gateway\Paysecure;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Isg\Field;
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
    //-------------- Initiate request ----------------------------------------
    protected function initiate()
    {
        $requestArray = $this->getInitiateRequestArray();

        $contents = $this->getRequestContents($requestArray);

        $command = Constants::COMMAND_INITIATE;

        $response = $this->sendRequest($command, $contents);

        return $response;
    }

    protected function initiate2()
    {
        $requestArray = $this->getInitiateRequestArray();

        //todo: Check what value to pass in http_accept
        $extraParameters = [
            Fields::BROWSER_USERAGENT => $this->input['paymentAnalytics']['user_agent'],
            Fields::IP_ADDRESS        => $this->input['paymentAnalytics']['ip'],
            Fields::HTTP_ACCEPT       => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];

        $requestArray = array_merge($requestArray, $extraParameters);

        $contents = $this->getRequestContents($requestArray);

        $command = Constants::COMMAND_INITIATE_2;

        $response = $this->sendRequest($command, $contents);

        return $response;
    }

    protected function getInitiateRequestArray(): array
    {
        $card = $this->input['card'];

        $paymentDate = Carbon::createFromTimestamp($this->input['payment']['created_at'], Timezone::IST);

        $time = $paymentDate->format('His');

        $date = $paymentDate->format('md');

        // Random 6 digit number
        $systemTraceAuditNumber = sprintf('%06d', mt_rand(1, 999999));

        $requestArray = [
            Fields::CARD_NO                           => $card['number'],
            Fields::CARD_EXP_DATE                     => $card['expiry_month'] . $card['expiry_year'],
            Fields::LANGUAGE_CODE                     => 'en',
            Fields::AUTH_AMOUNT                       => $this->input['payment']['amount'],
            Fields::CURRENCY_CODE                     => '356',
            Fields::CVD2                              => $card['cvv'],
            // todo: fetch this correctly from card BIN
            Fields::TRANSACTION_TYPE_INDICATOR        => 'SMS',
            Fields::TID                               => $this->config['terminal_id'],
            Fields::STAN                              => $systemTraceAuditNumber,
            Fields::TRAN_TIME                         => $time,
            Fields::TRAN_DATE                         => $date,
            Fields::MCC                               => $this->input['merchant']['category'],
            Fields::ACQUIRER_INSTITUTION_COUNTRY_CODE => '356',
            Fields::RETRIEVAL_REF_NUMBER              => $this->generateRrn($systemTraceAuditNumber),
            // todo: Confirm this
            Fields::CARD_ACCEPTOR_ID                  => $this->config['merchant_id'],
            Fields::TERMINAL_OWNER_NAME               => $this->input['merchant']->getBillingLabel() ?? 'Razorpay',
            Fields::TERMINAL_CITY                     => 'Bangalore',
            Fields::TERMINAL_STATE_CODE               => 'KA',
            Fields::TERMINAL_COUNTRY_CODE             => 'IN',
            Fields::MERCHANT_POSTAL_CODE              => '560030',
            Fields::MERCHANT_TELEPHONE                => '9999999999',
            Fields::ORDER_ID                          => $this->input['payment']['id'],
            Fields::CUSTOM1                           => $this->input['callbackUrl'],
            Fields::CUSTOM2                           => $this->input['payment']['id'],
        ];

        return $requestArray;
    }

    protected function generateRrn($stan)
    {
        $dt = Carbon::now('Asia/Kolkata');

        $jd = str_pad($dt->format('z') + 1, 3, 0, STR_PAD_LEFT);

        return substr($dt->format('y'), -1) . $jd . $dt->format('H') . $stan;
    }

    //-------------- Initiate request end ------------------------------------
    //-------------- Authorize request related functions ---------------------
    protected function authorizeTransaction($gatewayPayment)
    {
        $requestArray = [
            Fields::TRAN_ID       => $gatewayPayment[ Entity::GATEWAY_TRANSACTION_ID ],
            Fields::AUTH_AMOUNT   => $this->input['payment']['amount'],
            Fields::CURRENCY_CODE => '356',
        ];

        $contents = $this->getRequestContents($requestArray);

        $command = Constants::COMMAND_AUTHORIZE;

        $response = $this->sendRequest($command, $contents);

        return $response;
    }
    //-------------- Authorize request end -----------------------------------
    //---------------- Soap Request related functions ------------------------
    protected function sendRequest($command, $params)
    {
        $requestBody    = $this->getRequestBody($params, $command);

        try
        {
            // TODO: [OPTIONAL] Set trace and exceptions to false before pushing to production
            $soapClientOptions = [
                'trace'               => true,
                'exceptions'          => true,
                'connection_timeout'   => 30,
            ];

            $request = [
                'wsdl' => $this->wsdlDetails['wsdl_file'],
                'options' => $soapClientOptions
            ];

            $soapClient = $this->getSoapClientObject($request);

            $response = $soapClient->CallPaySecure($requestBody);

            // todo: Remove this
            // $this->printLastSoapXml($soapClient);
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
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        echo '<pre>' . htmlentities($dom->saveXML()) . '</pre>';
        die;
    }
    //---------------- REMOVE THIS LATER END -------------------------
}
