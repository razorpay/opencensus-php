<?php

namespace RZP\Gateway\Paysecure;

use SoapVar;
use SoapFault;
use SoapHeader;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;

trait RequestHandlerTrait
{

    //-------------- Check BIN2 request ------------------------------------
    protected function checkBin2()
    {
        $requestArray = $this->getCheckBin2RequestArray();

        $command = Command::CHECKBIN2;

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
        list($rrn, $requestArray) = $this->getInitiateRequestArray();

        $content = [
            Entity::RRN       => $rrn,
            Entity::TRAN_DATE => $requestArray[Fields::TRAN_DATE],
            Entity::TRAN_TIME => $requestArray[Fields::TRAN_TIME],
        ];

        $gatewayPayment = $this->createGatewayPaymentEntity($content, 'iframe');

        $contents = $this->getRequestContents($requestArray);

        $command = Command::INITIATE;

        $response = $this->sendRequest($command, $contents);

        return [$gatewayPayment, $response];
    }

    protected function initiate2()
    {
        list($rrn, $requestArray) = $this->getInitiateRequestArray();

        $content = [
            Entity::RRN       => $rrn,
            Entity::FLOW      => 'redirect',
            Entity::TRAN_DATE => $requestArray[Fields::TRAN_DATE],
            Entity::TRAN_TIME => $requestArray[Fields::TRAN_TIME],
        ];

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

        $extraParameters = [
            Fields::BROWSER_USERAGENT => $this->input['payment_analytics']['user_agent'],
            Fields::IP_ADDRESS        => $this->input['payment_analytics']['ip'],
            Fields::HTTP_ACCEPT       => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];

        $requestArray = array_merge($requestArray, $extraParameters);

        $contents = $this->getRequestContents($requestArray);

        $command = Command::INITIATE_2;

        $response = $this->sendRequest($command, $contents);

        return [$gatewayPayment, $response];
    }

    /**
     * @return array
     * @throws Exception\LogicException
     */
    protected function getInitiateRequestArray(): array
    {
        $card = $this->input['card'];

        $paymentDate = Carbon::createFromTimestamp($this->input['payment']['created_at'], Timezone::IST);

        $time = $paymentDate->format('His');

        $date = $paymentDate->format('md');

        // Random 6 digit number
        $systemTraceAuditNumber = sprintf('%06d', mt_rand(1, 999999));

        // In UAT they want us to pass 6012
        $mcc = (($this->mode === Mode::TEST) ? '6012' : ($this->input['merchant']['category']));

        $rrn = $this->generateRrn($systemTraceAuditNumber);

        if ((isset($this->input['iin']) === true) and
            (isset($this->input['iin']['message_type']) === true) and
            (empty($this->input['iin']['message_type']) === false))
        {
            $messageType = $this->input['iin']['message_type'];
        }
        else
        {
            throw new Exception\LogicException(
                "Message type missing for IIN",
                null,
                [
                    'payment_id' => $this->input['payment']['id'],
                    'gateway'    => $this->gateway,
                    'iin'        => $this->input['card']['iin'],
                ]
            );
        }


        $requestArray = [
            Fields::CARD_NO                           => $card['number'],
            Fields::CARD_EXP_DATE                     => $card['expiry_month'] . $card['expiry_year'],
            Fields::LANGUAGE_CODE                     => 'en',
            Fields::AUTH_AMOUNT                       => $this->input['payment']['amount'],
            Fields::CURRENCY_CODE                     => Currency::ISO_NUMERIC_CODES[$this->input['payment']['currency']],
            Fields::CVD2                              => $card['cvv'],
            Fields::TRANSACTION_TYPE_INDICATOR        => $messageType,
            Fields::TID                               => $this->getTerminalId(),
            Fields::STAN                              => $systemTraceAuditNumber,
            Fields::TRAN_TIME                         => $time,
            Fields::TRAN_DATE                         => $date,
            Fields::MCC                               => $mcc,
            Fields::ACQUIRER_INSTITUTION_COUNTRY_CODE => Currency::ISO_NUMERIC_CODES[$this->input['payment']['currency']],
            Fields::RETRIEVAL_REF_NUMBER              => $rrn,
            Fields::CARD_ACCEPTOR_ID                  => $this->getMerchantId(),
            Fields::TERMINAL_OWNER_NAME               => $this->input['merchant']->getBillingLabel() ?? 'Razorpay',
            Fields::TERMINAL_CITY                     => $this->input['merchant_detail']->getBusinessRegisteredCity() ?? 'Bangalore',
            Fields::TERMINAL_STATE_CODE               => $this->input['merchant_detail']->getBusinessRegisteredState() ?? 'KA',
            Fields::TERMINAL_COUNTRY_CODE             => 'IN',
            Fields::MERCHANT_POSTAL_CODE              => $this->input['merchant_detail']->getBusinessRegisteredPin() ?? '560030',
            Fields::MERCHANT_TELEPHONE                => $this->input['merchant_detail']->getContactMobile() ?? '9999999999',
            Fields::ORDER_ID                          => $this->input['payment']['id'],
        ];

        return [$rrn, $requestArray];
    }

    // Since we're the acquirer, we can pass our own internal merchant id
    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->input['merchant']['id'];
        }
        return $this->config['merchant_id'];
    }

    // Since we're the acquirer, we can pass our own internal terminal id
    protected function getTerminalId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->input['terminal']['id'];
        }
        return $this->config['terminal_id'];
    }

    protected function generateRrn($stan)
    {
        $dt = Carbon::now(Timezone::IST);

        $jd = str_pad($dt->format('z') + 1, 3, 0, STR_PAD_LEFT);

        return substr($dt->format('y'), -1) . $jd . $dt->format('H') . $stan;
    }

    //-------------- Initiate request end ------------------------------------
    //-------------- Authorize request related functions ---------------------
    protected function authorizeTransaction($gatewayPayment)
    {
        $this->gatewayPayment = $gatewayPayment;

        $requestArray = [
            Fields::TRAN_ID       => $gatewayPayment[Entity::GATEWAY_TRANSACTION_ID],
            Fields::AUTH_AMOUNT   => $this->input['payment']['amount'],
            Fields::CURRENCY_CODE => Currency::ISO_NUMERIC_CODES[$this->input['payment']['currency']],
        ];

        $contents = $this->getRequestContents($requestArray);

        $command = Command::AUTHORIZE;

        $response = $this->sendRequest($command, $contents);

        return $response;
    }
    //-------------- Authorize request end -----------------------------------
    //------------------Verify request ---------------------------------------
    protected function transactionStatus($gatewayPayment)
    {
        $requestArray = [Fields::TRAN_ID => $gatewayPayment[Entity::GATEWAY_TRANSACTION_ID]];

        $contents = $this->getRequestContents($requestArray);

        $command = Command::TRANSACTION_STATUS;

        $response = $this->sendRequest($command, $contents);

        return $response;
    }
    //------------------Verify request end -----------------------------------
    //---------------- Soap Request related functions ------------------------
    /**
     * @param $command
     * @param $params
     * @return array
     * @throws Exception\GatewayTimeoutException
     * @throws SoapFault
     */
    protected function sendRequest($command, $params)
    {
        $this->traceGatewayPaymentRequest(
            [
                'command'    => $command,
                'parameters' => $params,
                'gateway'    => $this->gateway,
            ],
            $this->input
        );

        $requestBody    = $this->getRequestBody($params, $command);

        // Set default timeout to 30 seconds
        $timeout = 30;

        switch ($command)
        {
            case Command::CHECKBIN2:
            case Command::TRANSACTION_STATUS:
                $timeout = 10;
                break;
            case Command::INITIATE:
            case Command::INITIATE_2:
                $timeout = 20;
                break;
            case Command::AUTHORIZE:
                $timeout = 35;
        }

        ini_set('default_socket_timeout', $timeout);

        $soapClientOptions = [
            'trace'               => true,
            'exceptions'          => true,
            'connection_timeout'  => $timeout,
        ];

        $request = [
            'wsdl' => $this->wsdlDetails['wsdl_file'],
            'options' => $soapClientOptions
        ];

        $soapClient = $this->getSoapClientObject($request);

        try
        {
            $response = $soapClient->__soapCall('CallPaySecure', array('parameters' => $requestBody));
        }
        catch (SoapFault $sf)
        {
            if (Utility::checkSoapTimeout($sf))
            {
                // If Soap request times out on auth request, we need to verify using transaction status and
                // mark the payment accordingly
                if (($command === Command::AUTHORIZE) and
                    ($this->gatewayPayment !== null))
                {
                    $response = $this->transactionStatus($this->gatewayPayment);

                    if (($response[Fields::STATUS] === StatusCode::SUCCESS) and
                        (isset($response[Fields::HISTORY][Fields::TRANSACTION]) === true) and
                        ($response[Fields::HISTORY][Fields::TRANSACTION][Fields::STATUS] === StatusCode::TRANSACTION_STATUS_AUTHORIZED)
                    )
                    {
                        $responseArray = $response;

                        unset($responseArray[Fields::HISTORY]);

                        $responseArray[Fields::APPRCODE] = $response[Fields::HISTORY][Fields::TRANSACTION][Fields::APPRCODE];

                        return $responseArray;
                    }
                }

                throw new Exception\GatewayTimeoutException($sf->getMessage(), $sf);
            }
            else
            {
                throw $sf;
            }
        }

        ini_restore('default_socket_timeout');

        $arrayResponse = $this->convertToArray($response);

        $this->app['trace']->info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'response'   => $arrayResponse,
                'payment_id' => $this->input['payment']['id'],
                'gateway'    => $this->gateway,
                'command'    => $command,
            ]
        );

        return $arrayResponse;
    }

    protected function getRequestHeaders()
    {
        $tokenId = $this->config['token'];

        $token = new SoapVar($tokenId, XSD_STRING, null, null, Fields::TOKEN, '');
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

        // Since they do not escape the '&' characters in redirect URL, we're forced
        // to manually replace them with the escaped character, so that xml can be loaded.
        $response = str_replace('&', '&amp;', $response);

        $xmlResponse = simplexml_load_string(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $response));

        $xmlResponseArray = XmlSerializer::xmlToArray($xmlResponse);

        return $xmlResponseArray;
    }
    //---------------- Soap Request related functions end --------------------
}
