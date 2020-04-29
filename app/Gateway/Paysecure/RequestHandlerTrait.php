<?php

namespace RZP\Gateway\Paysecure;

use SoapVar;
use SoapFault;
use SoapHeader;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Constants\Timezone;
use RZP\Constants\IndianStates;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;

trait RequestHandlerTrait
{
    //-------------- Check BIN2 request ------------------------------------
    protected function checkBin2()
    {
        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED,
            $this->input);

        $requestArray = $this->getCheckBin2RequestArray();

        $command = Command::CHECKBIN2;

        $response = $this->sendRequest($command, $requestArray);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED,
            $this->input,
            null,
            [
                'enrolled' => ($response[Fields::STATUS] === StatusCode::SUCCESS) ? 'Y' : 'F',
            ]);

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
            Entity::FLOW      => 'iframe',
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

        $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        if (empty($this->app['request']->header('Accept')) === false)
        {
            $accept = substr($this->app['request']->header('Accept'), 0, 256);
        }

        $userAgent = substr($this->app['request']->header('User-Agent'), 0, 512);
        $userAgent = $this->input['payment_analytics']['user_agent'] ?? $userAgent;
        $userAgent = str_replace("\t", " ", $userAgent);

        $ip = $this->app['request']->ip();

        $extraParameters = [
            Fields::BROWSER_USERAGENT => $userAgent,
            Fields::IP_ADDRESS        => $this->input['payment_analytics']['ip'] ?? $ip,
            Fields::HTTP_ACCEPT       => $accept,
        ];

        $requestArray = array_merge($requestArray, $extraParameters);

        $contents = $this->getRequestContents($requestArray);

        $command = Command::INITIATE_2;

        $response = $this->sendRequest($command, $contents);

        return [$gatewayPayment, $response];
    }

    /**
     * @return array
     */
    protected function getInitiateRequestArray(): array
    {
        $card = $this->input['card'];

        $paymentDate = Carbon::createFromTimestamp($this->input['payment']['created_at'], Timezone::IST);

        $time = $paymentDate->format('His');

        $date = $paymentDate->format('md');

        $systemTraceAuditNumber = $this->generateStan();

        // In UAT they want us to pass 6012
        $mcc = (($this->mode === Mode::TEST) ? '6012' : ($this->input['merchant']['category']));

        $mcc = Mcc::getMappedMcc($mcc);

        $rrn = $this->generateRrn($systemTraceAuditNumber);

        $messageType = 'SMS';

        if ((isset($this->input['card']['message_type']) === true) and
            ($this->input['card']['message_type'] !== null))
        {
            $messageType = $this->input['card']['message_type'];
        }
        else
        {
            $this->trace->critical(
                TraceCode::IIN_MESSAGE_TYPE_MISSING,
                [
                    'iin'        => $this->input['card']['iin'],
                    'card_id'    => $this->input['card']['id'],
                    'payment_id' => $this->input['payment']['id'],
                    'message'    => 'Message type missing for IIN. Defaulted to SMS.',
                ]);
        }

        $terminalCity = $terminalState = $postalCode = $telephone = null;

        if ($this->input['payment'][Payment\Entity::CREATED_AT] > \RZP\Gateway\Hitachi\Gateway::PAYSECURE_MID_SWITCH_TIME)
        {
            $terminalCity = substr($this->input['merchant_detail'][Merchant\Detail\Entity::BUSINESS_OPERATION_CITY], 0, 13);

            if (strlen($this->input['merchant_detail'][Merchant\Detail\Entity::BUSINESS_OPERATION_STATE]) === 2)
            {
                $terminalState = strtoupper($this->input['merchant_detail'][Merchant\Detail\Entity::BUSINESS_OPERATION_STATE]);
            }
            else
            {
                $terminalState = IndianStates::getStateCode(strtoupper($this->input['merchant_detail'][Merchant\Detail\Entity::BUSINESS_OPERATION_STATE]));
            }

            $postalCode = substr($this->input['merchant_detail'][Merchant\Detail\Entity::BUSINESS_OPERATION_PIN], 0, 9);

            $telephone = substr($this->input['merchant_detail'][Merchant\Detail\Entity::CONTACT_MOBILE], -10, 10);
        }

        $ownerName = $this->getDynamicMerchantName($this->input['merchant'], 22);

        $requestArray = [
            Fields::CARD_NO                           => $card['number'],
            Fields::CARD_EXP_DATE                     => sprintf("%02d", $card['expiry_month']) . sprintf("%04d", $card['expiry_year']),
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
            Fields::TERMINAL_OWNER_NAME               => $ownerName,
            Fields::TERMINAL_CITY                     => $terminalCity ?: 'Bangalore',
            Fields::TERMINAL_STATE_CODE               => $terminalState ?:'KA',
            Fields::TERMINAL_COUNTRY_CODE             => 'IN',
            Fields::MERCHANT_POSTAL_CODE              => $postalCode ?: '560030',
            Fields::MERCHANT_TELEPHONE                => $telephone ?: '9999999999',
            Fields::ORDER_ID                          => $this->input['payment']['id'],
        ];

        return [$rrn, $requestArray];
    }

    // Since we're the acquirer, we can pass our own internal merchant id
    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            if ($this->input['payment'][Payment\Entity::CREATED_AT] <= \RZP\Gateway\Hitachi\Gateway::PAYSECURE_MID_SWITCH_TIME)
            {
                return '38RR00000000001';
            }

            return $this->input['terminal'][Terminal\Entity::GATEWAY_MERCHANT_ID];
        }

        return $this->app['config']->get('gateway.hitachi.test_merchant_id');
    }

    // Since we're the acquirer, we can pass our own internal terminal id here.
    // But for settling the amount, we need to make a request to Hitachi, who
    // does not allow our internal mids/tids to be routed to them. Hence, we use
    // Hitachi's mid and tid when sending the requests to PaySecure
    protected function getTerminalId()
    {
        if ($this->mode === Mode::LIVE)
        {
            if ($this->input['payment'][Payment\Entity::CREATED_AT] <= \RZP\Gateway\Hitachi\Gateway::PAYSECURE_MID_SWITCH_TIME)
            {
                return '38R00001';
            }

            return $this->input['terminal'][Terminal\Entity::GATEWAY_TERMINAL_ID];
        }

        return $this->app['config']->get('gateway.hitachi.test_terminal_id');
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
        $this->wasGatewayHit = true;

        $this->traceGatewayPaymentRequest(
            [
                'command'    => $command,
                'parameters' => $params,
                'gateway'    => $this->gateway,
                'url'        => $this->getUrl(),
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

        $startTime = microtime(true);

        try
        {
            $response = $soapClient->__soapCall('CallPaySecure', array('parameters' => $requestBody));
        }
        catch (SoapFault $sf)
        {
            error_clear_last();

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

                $ex = new Exception\GatewayTimeoutException($sf->getMessage(), $sf);

                if ($command !== Command::AUTHORIZE)
                {
                    $ex->markSafeRetryTrue();
                }
                throw $ex;
            }
            else
            {
                $ex = new Exception\GatewayRequestException($sf->getMessage(), $sf);

                if ($command !== Command::AUTHORIZE)
                {
                    $ex->markSafeRetryTrue();
                }

                throw $ex;
            }
        }
        finally
        {
            $completed = microtime(true);

            try
            {
                $metricsDriver = app('trace')->metricsDriver(\RZP\Gateway\Base\Metric::DOGSTATSD_DRIVER);

                /**
                 * @var $metricsDriver \Razorpay\Metrics\Drivers\Driver
                 */
                $metricsDriver->histogram(\RZP\Gateway\Base\Metric::GATEWAY_REQUEST_TIME,
                    ($completed - $startTime) * 1000,
                    [
                        'gateway' => 'paysecure',
                        'action'  => $command ?? 'none',
                    ]);
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::GATEWAY_METRIC_DIMENSION_PUSH_FAILED,
                    [
                        'gateway' => 'paysecure',
                        'action'  => $command ?? 'none',
                    ]);
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

    // Used to generate the system trace audit number
    // This needs to be a unique value for all the transactions happening in an hour.
    // We use the redis INCR function which acts as a counter and set it's expiry to the next day
    //
    // Assumptions:
    // 1. No two redis pipelines would be initiated at the exact same moment
    // 2. There would not be more than 999999 PaySecure payments happening within a day
    protected function generateStan()
    {
        $timestampToExpire = Carbon::tomorrow(Timezone::IST)->getTimestamp();

        $redis = Redis::connection()->client();

        list($currentValue, $ttl) = $redis->pipeline(
            function ($pipe)
            {
                $pipe->incr(self::GATEWAY_PAYSECURE_STAN);
                $pipe->ttl(self::GATEWAY_PAYSECURE_STAN);
            });

        // If within a day, the counter crosses the 999999 limit, this falls back to start from 0
        //
        // Since we are migrating to card payment service we have shared the counter space between api and cps.
        // 0-499999 will be used by api and 500000-999999 will used by cps to avoid collision
        $currentValue = $currentValue % 500000;

        if ($ttl === -1)
        {
            $redis->expireat(self::GATEWAY_PAYSECURE_STAN, $timestampToExpire);
        }

        return sprintf('%06d', $currentValue);
    }
}
