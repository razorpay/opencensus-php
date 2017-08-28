<?php

namespace RZP\Gateway\Blade;

use Cache;
use Carbon\Carbon;
use GuzzleHttp;
use DOMDocument;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Lib\Formatters\Xml;
use RZP\Base\JitValidator;
use RZP\Models\Currency\Currency;
use RZP\Exception\ThreeDSecureAuthenticationFailureException;

class Gateway extends Base\Gateway
{
    const VERSION = '1.0.2';

    protected $gateway = 'blade';

    /**
     * Authorize the payment
     *
     * @param array $input Input
     *
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $data = null;

        $authenticationStatus = null;

        $resp = $this->threeDSecure($input);

        return $resp;
    }

    protected function threeDSecure(array $input)
    {
        //TODO make card range cache

        // Send card enrollment verification request
        $veres = $this->sendVereq($input);

        // Process verification response
        $enrolled = $this->processVeres($veres);

        //
        // Determine card enrollment status and take next action
        //

        if ($enrolled === Enrolled::Y)
        {
            // Card is enrolled
            // send Pareq
            return $this->sendPareq($input, $veres);
        }
        else if ($enrolled === Enrolled::N)
        {
            // TODO get $eci, no sample resp have eci value
            //$this->validateEci($eci, Card\Network::MC);

            $this->validateVaresForNotEnrolledResponse($veres);

            return null;
        }
        else
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                $enrolled,
                'Invalid enroll response');
        }
    }

    protected function validateVaresForNotEnrolledResponse(\SimpleXMLElement $veres)
    {


    }

    public function callback(array $input)
    {
        parent::callback($input);

        $pares = $input['gateway']['PaRes'];

        $corePares = (array) $this->processPares($pares);

        $txnAttributes = (array) $corePares['TX'];
        $purchaseAttributes = (array) $corePares['Purchase'];

        $gatewayInput = [
            'purchase'    => $purchaseAttributes,
            'transaction' => $txnAttributes
        ];

        $status = $txnAttributes['status'];
        $xid = $purchaseAttributes['xid'];

        $authenticateStatus = ParesStatus::getAuthenticationStatus($status);

        if ($authenticateStatus !== AuthenticationStatus::Y)
        {
            throw new ThreeDSecureAuthenticationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED);
        }

        return null;
    }

    protected function validateEci($eci, $networkCode)
    {
        $eciRaw = $eci ?? '07';

        // NOTE: Make sure PHP return correct int on conversion
        // Example: '012' should be converted to decimal 12 not octal 12
        $eci = (int) $eciRaw;

        switch ($networkCode)
        {
            case Card\Network::VISA:

                if ($eci === 7)
                {
                    $desc = 'ECI value shouldn\'t be 7.';
                }

                break;

            case Card\Network::MC:

                if (($eci === 7) or ($eci === 0))
                {
                    $desc = 'ECI value shouldn\'t be 7 or 0. ECI: ' . $eci;
                }

                break;
        }

        if (isset($desc) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                $eciRaw,
                $desc);
        }
    }

    protected function processPares(string $pares)
    {
        $pares = base64_decode($pares);
        $pares = gzinflate(substr($pares, 2));

        $dom = $this->loadXmlViaDom($pares);

        $adapter = new XmlseclibsAdapter;

        try
        {
            $ret = $adapter->verify($dom);
        }
        catch (\Exception $e)
        {
            $msg = $e->getMessage();

            $this->trace->traceException($e);

            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_XML_SIGNATURE_ERROR;

            switch($msg)
            {
                case ParesResponse::MSG_REF_VALIDATION_FAILED:
                    $this->trace->info(
                        TraceCode::GATEWAY_INVALID_PARES_SIGNATURE_ERROR);

                    $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_XML_SIGNATURE_ERROR;

                    break;
                case ParesResponse::MSG_INVALID_PROPERTY:
                    $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_INVALID_RESPONSE;

                    break;
            }

            throw new ThreeDSecureAuthenticationFailureException($errorCode);
        }

        if ($ret === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_XML_SIGNATURE_ERROR);
        }

        // Convert to an object
        $paresObject = simplexml_load_string($pares);

        // Validate Payer Authentication Response
        $this->validatePARes($paresObject);

        $PARes = $paresObject->Message->PARes;

        return $PARes;
    }

    protected function validatePARes($PAres)
    {
        $PAres = json_decode(json_encode($PAres), true);

        $this->trace->info('PAResBase', $PAres);

        if (empty($PAres['Message']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Message element not found', 'message');
        }

        validate(Validator::$PAresRules, $PAres, false);

        $dotted_pares = array_dot($PAres);

        $difference = array_diff($PAres, Validator::$PAresRules);

        foreach ($difference as $key => $value)
        {

        }

        $PAResBase = $PAres['Message']['PARes'];

        if (in_array($PAResBase['TX']['status'], [ParesStatus::Y, ParesStatus::A], true))
        {
            Validator::validateLastFour($this->input['card']['last4'], $PAResBase['pan']);
        }

        $xid = '000000'.$this->input['payment']['id'];
        $xid = base64_encode($xid);

        if ($PAResBase['Purchase']['xid'] !== $xid)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Value mismatch', 'xid');
        }

        $purchaseDate = Carbon::createFromTimestamp($this->input['payment']['created_at'], 'Asia/Kolkata')
                                ->format('Ymd H:m:s');

        if ($PAResBase['Purchase']['date'] !== $purchaseDate)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Value mismatch', 'xid');
        }

        $currency = (int) $PAResBase['Purchase']['currency'];

        if ($currency !== 356)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Invalid currency code', 'xid');
        }

        $amount = (int) $PAResBase['Purchase']['purchAmount'];

        if ($amount !== $this->input['payment']['amount'])
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Amount mismatch', 'amount');
        }

        $exponent = (int) $PAResBase['Purchase']['exponent'];

        if ($exponent !== 2)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Exponent mismatch', 'exponent');
        }

        if ($PAres['Message']['@attributes']['id'] !== $this->input['payment']['public_id'])
        {
            throw new Exception\BadRequestValidationFailureException(
                    'ID mismatch', 'id');
        }

        $this->validateCredentials($PAResBase);
    }

    protected function validateCredentials($PARes)
    {
        $credentials = $this->getCreds();

        if (($PARes['Merchant']['acqBIN'] !== $credentials['acq_bin']) or
            ($PARes['Merchant']['merID'] !== $credentials['merchant_id']))
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Credentials mismatch');
        }
    }

    protected function validateVERes($veres)
    {
        $paymentId = $this->input['payment']['public_id'];

        $veres = json_decode(json_encode($veres), true);

        $this->trace->info(TraceCode::VERIFY_ENROLLMENT_RESPONSE, $veres);

        //TODO : check if iReqDetail validation needs to be done
        //Test case 42e-11-VERes
        (new JitValidator)->rules(Validator::$veresRules)
                          ->input($veres)
                          ->validate();


        if ($veres['Message']['@attributes']['id'] !== $paymentId)
        {
            throw new Exception\BadRequestValidationFailureException(
                'ID mismatch',
                $paymentId);
        }
    }

    public function sendCRReq()
    {
        $this->messageId = 'rzp_' . \Str::random();

        $request = $this->getCrreqRequestArray();

        $xml = $this->sendGatewayRequest($request);

        $valid = $this->validateXml($xml);

        if ($valid === false)
        {
            $this->trace->warning(
                TraceCode::BLADE_VERES_PARSE_FAILURE,
                ['message' => 'Malformed xml: ' . $xml]);

            return Enrolled::U;
        }

        $parsedXml = simplexml_load_string($xml);

        $CRres = json_decode(json_encode($parsedXml), true);

        $this->validateCrreq($CRres);

        $cSerial = Cache::get('cache_serial', null);
        $cache = Cache::get('card_cache', []);

        $serial = null;

        if (isset($CRres['Message']['CRRes']['serialNumber']))
        {
            $serial = $CRres['Message']['CRRes']['serialNumber'];
        }

        $this->validateCR($CRres);

        if (($serial !== null) and
            (isset($CRres['Message']['CRRes']['IReq']) === false))
        {
            // if ($cSerial === null)
            // {
            //     if (!$this->isSequentialArray($CRres['Message']['CRRes']['CR']))
            //     {
            //         $CR[] = $CRres['Message']['CRRes']['CR'];
            //     }
            //     else
            //     {
            //         $CR = $CRres['Message']['CRRes']['CR'];
            //     }

            //     foreach ($CRres['Message']['CRRes']['CR'] as $CR)
            //     {
            //         $cache[$CR['begin'] . '-' . $CR['end']] = $CR;
            //     }
            // }
            // else
            // {
            //     if (empty($cache) === false)
            //     {
            //         if (isset($CRres['Message']['CRRes']['CR'][0]))
            //         {
            //             foreach ($CRres['Message']['CRRes']['CR'] as $CR)
            //             {
            //                 if (isset($cardCache[$CR['begin'] . '-' . $CR['end']]))
            //                 {
            //                     $cardCache[$CR['begin'] . '-' . $CR['end']] = $CR;
            //                 }
            //             }
            //         }
            //         else
            //         {
            //             if (isset($CRres['Message']['CRRes']['CR']))
            //             {
            //                 $CR = $CRres['Message']['CRRes']['CR'];

            //                 if (isset($cardCache[$CR['begin'] . '-' . $CR['end']]))
            //                 {
            //                     $cache[$CR['begin'] . '-' . $CR['end']] = $CR;
            //                 }
            //             }
            //         }
            //     }
            // }

            if (isset($CRres['Message']['CRRes']['CR']))
            {
                if (!$this->isSequentialArray($CRres['Message']['CRRes']['CR']))
                {
                    $CR[] = $CRres['Message']['CRRes']['CR'];
                }
                else
                {
                    $CR = $CRres['Message']['CRRes']['CR'];
                }

                foreach ($CRres['Message']['CRRes']['CR'] as $CR)
                {
                    $cache[$CR['begin'] . '-' . $CR['end']] = $CR;
                }
            }
        }

        if (isset($CRres['Message']['CRRes']['IReq']) === false)
        {
            Cache::forever('card_cache', $cache);
            Cache::forever('cache_serial', $serial);
        }

        if ($serial === null)
        {
            Cache::forever('card_cache', []);
            Cache::forever('cache_serial', null);
        }

        // if (empty($CRres['Message']['CRRes']['serialNumber']) === false)
        // {
        //     Cache::forever('blade_serial_number', $CRres['Message']['CRRes']['serialNumber']);
        // }
        // else
        // {
        //     Cache::forever('blade_serial_number', null);
        //     Cache::forever('blade_card_cache', []);
        // }
    }

    protected function isSequentialArray(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    protected function validateCR(array $CRres)
    {
        if (isset($CRres['Message']['CRRes']['CR']))
        {
            if (!$this->isSequentialArray($CRres['Message']['CRRes']['CR']))
            {
                $CR[] = $CRres['Message']['CRRes']['CR'];
            }
            else
            {
                $CR = $CRres['Message']['CRRes']['CR'];
            }

            foreach ($CRres['Message']['CRRes']['CR'] as $CR)
            {
                if ((isset($CR['begin']) === false) or
                    (isset($CR['end']) === false) or
                    (isset($CR['action']) === false))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Invalid value in CR');
                }

                $beginLength = strlen($CR['begin']);
                $endLength = strlen($CR['end']);

                if ((is_numeric($CR['begin']) === false) or
                    (is_numeric($CR['end']) === false) or
                    ($beginLength > 19) or ($beginLength < 13) or
                    ($endLength > 19) or ($endLength < 13) or
                    ($beginLength !== $endLength) or
                    (in_array($CR['action'], ['A', 'D']) === false))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Invalid value in CR');
                }
            }
        }
    }

    public function getCrreqRequestArray()
    {
        $url = Url::CTH_DS;

        $certFile = $this->config['mpi_ssl_client_pem'];
        $keyFile = $this->config['mpi_ssl_client_key'];

        $serialNumber = Cache::get('cache_serial', null);
        $messageId = $this->messageId;

        $creds = $this->getCreds();

        $s = '';
        if ($serialNumber !== null)
        {
            $s = '
                <serialNumber>'.$serialNumber.'</serialNumber>';
        }

        $xml = ''.
            '<?xml version="1.0" encoding="UTF-8"?>
            <ThreeDSecure>
              <Message id="' . $messageId . '">
                <CRReq>
                  <version>1.0.2</version>
                  <Merchant>
                    <acqBIN>' . $creds['acq_bin'] . '</acqBIN>
                    <merID>' . $creds['merchant_id'] . '</merID>
                    <password>' . $creds['password'] . '</password>
                  </Merchant>' . $s . '
                </CRReq>
              </Message>
            </ThreeDSecure>';

        $headers = [
            'Content-Type' => 'application/xml; charset=utf-8'
        ];

        $options = [
            'body'      => $xml,
            'headers'   => $headers,
            'cert'      => [$certFile, ''],
            'ssl_key'   => [$keyFile, ''],
            'verify'    => false,
            'debug'     => false,
            'timeout'   => 30
        ];

        $request = [
            'url'       => $url,
            'method'    => 'POST',
            'options'   => $options
        ];

        return $request;
    }

    protected function validateCrreq(array $CRres)
    {
        $this->trace->info('CRres', $CRres);

        validate(Validator::$CRresRules, $CRres, false);

        if ($CRres['Message']['@attributes']['id'] !== $this->messageId)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'ID mismatch', 'id');
        }
    }

    protected function processVeres($veres)
    {
        if ($veres instanceof \SimpleXMLElement)
        {
            $this->validateVERes($veres);

            $VERes = $veres->Message->VERes;

            $error = (array) $VERes->Error;

            if (count($error) !== 0)
            {
                $msg = 'Error message: ' . $error['errorMessage'] . ' ' .
                       'Error detail: ' . $error['errorDetail'];

                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                    $error['errorCode'],
                    $msg);
            }

            $CH = (array) $VERes->CH;

            return $CH['enrolled'];
        }

        return $veres;
    }

    protected function sendPareq($input, $veres)
    {
        $creds = $this->getCreds();

        $url = $veres->Message->VERes->url;

        $xml = $this->getPareqXmlString($input, $veres, $creds);

        $content = [
            'PaReq'     => $xml,
            'TermUrl'   => $input['callbackUrl'],
            'MD'        => $input['payment']['public_id']
        ];

        $request = [
            'url'       => $url,
            'method'    => 'post',
            'content'   => $content
        ];

        return $request;
    }

    protected function sendVereq($input)
    {
        $request = $this->getVereqRequestArray($input);

        $xml = $this->sendGatewayRequest($request);

        $body = $xml->body;

        $valid = $this->validateXml($body);

        if ($valid === false)
        {
            $this->trace->warning(
                TraceCode::BLADE_VERES_PARSE_FAILURE,
                ['message' => 'Malformed xml: ' . $body]);

            return Enrolled::U;
        }

        return simplexml_load_string($body);
    }

    protected function getVereqRequestArray($input)
    {
        $url = Url::CTH_DS;

        $certFile = $this->config['mpi_ssl_client_pem'];
        $keyFile = $this->config['mpi_ssl_client_key'];

        // $id = $input['payment']['public_id'];

        // $content = array(
        //     'pan' => $input['card']['number'],
        //     'message_id' => $id,
        //     ''
        // );

        // $content = array_merge($content, $creds);

        $xml = $this->getVereqXmlString($input);

        $headers = [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Accept' => $this->app['request']->header('Accept'),
            'User-Agent' => $this->app['request']->header('User-Agent')
        ];

        $options = [
            'headers'   => $headers,
            //TODO fix me
            //'cert'      => [$certFile, ''],
            //'ssl_key'   => [$keyFile, ''],
            'verify'    => false,
            'debug'     => false,
            'timeout'   => 30
        ];

        $request = [
            'content'   => $xml,
            'url'       => $url,
            'method'    => 'POST',
            'options'   => $options
        ];

        return $request;
    }

    protected function getPareqXmlString($input, $veres, $creds)
    {
        // Format YYYYMMDD HH:MM:SS
        $date = Carbon::createFromTimestamp($input['payment']['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s');

        $mid = $input['payment']['public_id'];

        $content = [
            'Message' => [
                '@attributes' => [
                    'id' => $mid,
                ],
                'PAReq' => [
                    'version' => self::VERSION,
                    'Merchant' => [
                        'acqBIN'  => $creds['acq_bin'],
                        'merID'   => $creds['merchant_id'],
                        // todo: make it dynamic
                        'name'    => 'Razorpay Software Pvt Ltd',
                        'country' => '356',
                        'url'     => 'https://razorpay.com',
                    ],
                    'Purchase' => [
                        'xid'     => $this->generateXid($input),
                        'date'    => $date,
                        'amount'  => $this->getFormattedAmount($input['payment']),
                        'purchAmount' => $input['payment']['amount'],
                        'currency' => Currency::getIsoCode($input['payment']['currency']),
                        'exponent' => '2',
                    ],
                    'CH' => [
                        'acctID' => $veres->Message->VERes->CH->acctID,
                        'expiry' => $this->getFormattedCardExpiry($input['card']),
                    ]
                ]
            ]
        ];

        if (isset($recurring) === true)
        {
            $content['Message']['PAReq']['Purchase']['Recur'] = [
                'frequency' => '',
                'endRecur'  => '',
            ];
        }

        if (isset($emi) === true)
        {
            $content['Message']['PAReq']['Purchase']['install'] = $emi;
        }

        // if (empty($input['payment']['notes']['installments']) === false)
        // {
        //     $installments = '<install>'. $input['payment']['notes']['installments'] . '</install>';
        // }

        // if (empty($input['payment']['notes']['recurring_frequency']) === false)
        // {
        //     $recurring = '<Recur>
        //                 <frequency>'.$input['payment']['notes']['recurring_frequency'].'</frequency>
        //                 <endRecur>'.$input['payment']['notes']['recurring_expiry'] .'</endRecur>
        //                 </Recur>';
        // }

        /* Currency is INR (356 - ISO 4217 numeric value) for now
        // TODO: Make it dynamic with INR as default
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<ThreeDSecure>
                    <Message id="'.$mid.'">
                        <PAReq>
                          <version>'.self::VERSION.'</version>
                          <Merchant>
                            <acqBIN>'.$creds['acq_bin'].'</acqBIN>
                            <merID>'.$creds['merchant_id'].'</merID>
                            <name>Razorpay Payments</name>
                            <country>356</country>
                            <url>https://razorpay.com/</url>
                          </Merchant>
                          <Purchase>
                            <xid>'.$xid.'</xid>
                            <date>'.$date.'</date>
                            <amount>'.($input['payment']['amount']/100).'</amount>
                            <purchAmount>'.$input['payment']['amount'].'</purchAmount>
                            <currency>356</currency>
                            <exponent>2</exponent>
                            '.$recurring.'
                            '.$installments.'
                          </Purchase>
                          <CH>
                            <acctID>'.$veres->Message->VERes->CH->acctID.'</acctID>
                            <expiry>'.$expiry.'</expiry>
                          </CH>
                        </PAReq>
                    </Message>
                </ThreeDSecure>';

        */

        $xml = Xml::create('ThreeDSecure', $content);

        $xml = zlib_encode($xml, 15);
        $xml = base64_encode($xml);

        return $xml;
    }

    private function generateXid(array $input)
    {
        $xid = str_pad($input['payment']['id'], 18, '0', STR_PAD_LEFT);

        return base64_encode($xid);
    }

    private function getFormattedAmount(array $payment)
    {
        $currency = Currency::getSymbol($payment['currency']);

        $amount = (string) ($payment['amount'] / 100);

        return trim($currency . ' ' . $amount);
    }

    private function getFormattedCardExpiry(array $card)
    {
        $year = substr($card['expiry_year'], -2);
        $month = str_pad($card['expiry_month'], 2, 0, STR_PAD_LEFT);

        return $year . $month;
    }

    protected function getVereqXmlString($input)
    {
        $creds = $this->getCreds();

        $accept = substr($this->app['request']->header('Accept'), 0, 2048);
        $userAgent = substr($this->app['request']->header('User-Agent'), 0, 256);

        $content = [
            'Message' => [
                '@attributes' => [
                    'id' => $input['payment']['public_id']
                ],
                'VEReq' => [
                    'version' => self::VERSION,
                    'pan'     => $input['card']['number'],
                    'Merchant' => [
                        'acqBIN' => $creds['acq_bin'],
                        'merID'  => $creds['merchant_id'],
                        'password' => $creds['password'],
                    ],
                    'Browser' => [
                        'deviceCategory' => DeviceCategory::DESKTOP,
                        'accept'         => $accept,
                        'userAgent'      => $userAgent,
                    ]
                ]
            ]
        ];

        return Xml::create('ThreeDSecure', $content);
    }

    protected function getCreds()
    {
        if ($this->mode === Mode::TEST)
        {
            $creds = [
                'acq_bin'       => '11111111111',
                'merchant_id'   => '12AB,cd/34-EF  -g,5/H-67',
                'password'      => '12345678',
            ];
        }
        else
        {
            $terminal = $this->terminal;

            $creds = [
                'acq_bin'       => $terminal['gateway_access_code'],
                'merchant_id'   => $terminal['gateway_merchant_id2'],
                'password'      => $terminal['gateway_terminal_password'],
            ];
        }

        return $creds;
    }

    /**
     * Validates the xml against the mpi schema.
     * @return  bool true/false whether the xml is valid or not
     */
    protected function validateXml($xml)
    {
        $dom = $this->loadXmlViaDom($xml);

        return ($dom !== false);
    }

    protected function loadXmlViaDom($xml)
    {
        // XML DTD Schema file
        $file = __DIR__ . '/Schema/mpiXmlSchema.dtd';

        // For validating against the xml dtd schema,
        // we need to insert this line in the xml
        // If the xml starts '<?xml version="1.0"?\>
        // then this line is inserted as second line
        // else it's inserted as first line.
        //
        $dtdLine = '<!DOCTYPE ThreeDSecure SYSTEM "' . $file . '">';

        $ix = strpos($xml, '?>');

        if ($ix === false)
        {
            // Doesn't have xml version line,
            // so it's the first line in this case.
            $xml = $dtdLine . $xml;
        }
        else
        {
            $xml = substr_replace($xml, $dtdLine, $ix + 2, 0);
        }

        $dom = new DOMDocument;
        $dom->validateOnParse = true;

        try
        {
            $ret = $dom->loadXML($xml);

            if ($ret === false)
            {
                return $ret;
            }

            return $dom;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            $error = $e->getMessage();

            switch (true)
            {
                case strpos($error, 'CanonicalizationMethod') !== false:
                case strpos($error, 'SignedInfo') !== false:
                case strpos($error, 'Signature') !== false:
                case strpos($error, 'DigestMethod') !== false:
                case strpos($error, 'DigestValue') !== false:
                case strpos($error, 'SignatureMethod') !== false:
                case strpos($error, 'SignatureValue') !== false:
                case strpos($error, 'KeyInfo') !== false:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_XML_SIGNATURE_ERROR);
            }

            throw new Exception\BadRequestValidationFailureException(
                    'Invalid XML');
        }
    }

    protected function postCurlRequest(string $url,string $txt, $certFile, $keyFile)
    {
        $curl_resource = curl_init();

        curl_setopt($curl_resource, CURLOPT_URL, $url);
        curl_setopt($curl_resource, CURLOPT_POST, 1);
        curl_setopt($curl_resource, CURLOPT_POSTFIELDS, $txt);
        curl_setopt($curl_resource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_resource, CURLOPT_HEADER, true);
        curl_setopt($curl_resource, CURLOPT_SSLCERT, $certFile);
        curl_setopt($curl_resource, CURLOPT_SSLCERTPASSWD, '');
        curl_setopt($curl_resource, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($curl_resource, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl_resource, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl_resource, CURLOPT_SSLCERTTYPE, 'PEM');

        $output = curl_exec($curl_resource);
        curl_close($curl_resource);

        return $output;
    }
}
