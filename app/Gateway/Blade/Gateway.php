<?php

namespace RZP\Gateway\Blade;

use Cache;
use Carbon\Carbon;
use GuzzleHttp;
use DOMDocument;

use RZP\Exception;
use Requests_Hooks;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Lib\Formatters\Xml;
use RZP\Models\Currency\Currency;

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

        return $this->authenticate($input);
    }

    protected function authenticate(array $input)
    {
        // TODO: Add card range cache

        // Send card enrollment verification request
        $response = $this->sendVerifyEnrollmentRequest($input);

        // Process verification response
        $enrolled = $this->processVerifyEnrollmentResponse($input, $response);

        $attributes = $this->getVeresAttributesToSave($response);

        $this->createGatewayPaymentEntity($attributes, $input);

        //
        // Determine card enrollment status and take next action
        //
        switch ($enrolled)
        {
            case Enrolled::Y:
                return $this->getPayerAuthenticationRequest($input, $response);

            case Enrolled::N:
                return null;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                    $enrolled,
                    'Invalid enroll response');
        }
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Base\Action::AUTHORIZE);

        $PARes = $this->validateAndGetPayerAuthenticationResponse($input);

        $this->updateGatewayPaymentFromCallbackResponse($gatewayPayment, $PARes);

        $txnStatus = $PARes['TX']['status'];

        $authenticateStatus = ParesStatus::getAuthenticationStatus($txnStatus);

        if ($authenticateStatus !== AuthenticationStatus::Y)
        {
            // Throw GatewayErrorException with authentication failed error code
            throw new ThreeDSecureAuthenticationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED);
        }

        // TODO: Validate ECI here

        return $this->getCallbackResponseData($input);
    }

    protected function getVeresAttributesToSave(array $response)
    {
        $attributes = [];

        $ch = $response['Message']['VERes']['CH'];

        $attributes[Entity::ENROLLED] = $ch['enrolled'];

        if (empty($ch['acctID']) === false)
        {
            $attributes[Entity::ACC_ID] = $ch['acctID'];
        }

        return $attributes;
    }

    protected function updateGatewayPaymentFromCallbackResponse(
        Entity $gatewayPayment,
        array $resp)
    {
        $gatewayPayment->setXid($resp['Purchase']['xid']);

        $gatewayPayment->setCavv($resp['TX']['cavv']);

        $gatewayPayment->setCavvAlgorithm($resp['TX']['cavvAlgorithm']);

        $gatewayPayment->setStatus($resp['TX']['status']);

        $gatewayPayment->setEci($resp['TX']['eci']);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function createGatewayPaymentEntity(array $attributes, array $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $currency  = $input['payment']['currency'];

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAmount($amount);

        $gatewayPayment->setCurrency($currency);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
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

    protected function validateSignatureAndInflatePares($paresXml)
    {
        //
        // @ref http://forums.devshed.com/php-development-5/zlip-text-string-452797.html
        // Where you see 10 in the substr, replace with 2, the encoder is
        // using the older encoding method mentioned in RFC1950!
        // When PHP 5.3 comes out, you will have the function gzdecode(),
        // which will handle any type of header, but for now you have to
        // use gzinflate() with substr() (2 or 10) depending on the encoder,
        // Java defaults to (2) byte header, server based encoding HTTP GZIP
        // defaults to (10) byte header, even if the data is enclosed in a HTTP 1.1 CHUNKED stream.

        $paresXml = gzinflate(substr($pares, 2));

        $dom = $this->loadXmlViaDom($paresXml);

        $adapter = new XmlseclibsAdapter;

        $ret = false;

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

            throw new Exception\GatewayErrorException($errorCode);
        }

        if ($ret === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_XML_SIGNATURE_ERROR);
        }

        return $paresXml;
    }

    protected function validateAndGetPayerAuthenticationResponse(array $input)
    {
        $pares = $input['gateway']['PaRes'];
        $pares = base64_decode($pares);

        $paresXml = $this->validateSignatureAndInflatePares($pares);

        // Convert to an object
        $PaRes = $this->xmlToArray($paresXml);

        // Validate Payer Authentication Response
        $this->validatePayerAuthenticationResponse($input, $PaRes);

        $PARes = $PaRes['Message']['PARes'];

        return $PARes;
    }

    protected function validatePayerAuthenticationResponse($input, $paRes)
    {
        if (empty($paRes['Message']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                'Message element not found');
        }

        (new Validator)->rules(Validator::$paresRules)
                       ->input($paRes)
                       ->strict(false)
                       ->validate();

        $pARes = $paRes['Message']['PARes'];

        if (in_array($pARes['TX']['status'], [ParesStatus::Y, ParesStatus::A], true))
        {
            Validator::validateLastFour($input['card']['last4'], $pARes['pan']);
        }

        $expectedXid = $this->generateXid($input);

        if ($pARes['Purchase']['xid'] !== $expectedXid)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Value mismatch for xid',
                [
                    'expected' => $expectedXid,
                    'actual'   => $pARes['Purchase']['xid']
                ]
            );
        }

        $purchaseDate = Carbon::createFromTimestamp($input['payment']['created_at'], 'Asia/Kolkata')
            ->format('Ymd H:m:s');

        if ($pARes['Purchase']['date'] !== $purchaseDate)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Value mismatch',
                [
                    'expected' => $purchaseDate,
                    'actual'   => $pARes['Purchase']['date']
                ]);
        }

        $currency = $pARes['Purchase']['currency'];

        // TODO: Use payment currency to validate this
        if ($currency !== Currency::getIsoCode($input['payment']['currency']))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Invalid currency code',
                [
                    'expected' => $input['payment']['currency'],
                    'actual'   => $currency
                ]);
        }

        $amount = (int) $pARes['Purchase']['purchAmount'];

        if ($amount !== $input['payment']['amount'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Amount mismatch',
                [
                    'expected' => $input['payment']['amount'],
                    'actual'   => $amount
                ]);
        }

        $exponent = (int) $pARes['Purchase']['exponent'];

        // Move it to currency and then validate
        if ($exponent !== Currency::getExponent($input['payment']['currency']))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Exponent mismatch',
                [
                    'expected' => Currency::getExponent($input['payment']['currency']),
                    'actual'   => $exponent
                ]);
        }
        if ($paRes['Message']['@attributes']['id'] !== $input['payment']['public_id'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Payment ID mismatch',
                [
                    'actual'   => $input['payment']['public_id'],
                    'expected' => $pARes['@attributes']['id']
                ]);
        }

        $this->validateCredentials($input, $pARes);
    }

    protected function validateCredentials($input, $PARes)
    {
        if (($PARes['Merchant']['acqBIN'] !== $this->getAcquirerBin($input)) or
            ($PARes['Merchant']['merID'] !== $this->getMerchantId($input)))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Credentials mismatch');
        }
    }

    protected function validateVERes($input, $response)
    {
        $this->trace->info(TraceCode::VERIFY_ENROLLMENT_RESPONSE, $response);

        //TODO : check if iReqDetail validation needs to be done
        //Test case 42e-11-VERes
        (new Validator)->rules(Validator::$veresRules)
                       ->input($response)
                       ->validate();

        if ($response['Message']['@attributes']['id'] !== $input['payment']['public_id'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Invalid payment id received',
                [
                    'expected' => $input['payment']['public_id'],
                    'actual'   => $response['Message']['@attributes']['id'],
                ]);
        }
    }

    protected function isSequentialArray(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    protected function processVerifyEnrollmentResponse($input, $response)
    {
        $this->validateVERes($input, $response);

        $VERes = $response['Message']['VERes'];

        if ((isset($VERes['Error']) === true) and
            (count($VERes['Error']) !== 0))
        {
            $msg = 'Error message: ' . $error['errorMessage'] . ' ' .
                   'Error detail: ' . $error['errorDetail'];

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                $error['errorCode'],
                $msg);
        }

        $ch = $VERes['CH'];

        return $ch['enrolled'];
    }

    protected function getPayerAuthenticationRequest($input, $response)
    {
        $url = $response['Message']['VERes']['url'];

        $pareq = $this->getPayerAuthenticationContent($input, $response);

        $request = [
            'url'       => $url,
            'method'    => 'post',
            'content'   => [
                'PaReq'     => $pareq,
                'TermUrl'   => $input['callbackUrl'],
                'MD'        => $input['payment']['id']
            ]
        ];

        return $request;
    }

    protected function sendVerifyEnrollmentRequest($input)
    {
        $request = $this->getVerifyEnrollmentRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_ENROLL_RESPONSE,
            [
                'gateway' => 'blade',
                'response' => $response->body,
            ]);

        $body = $response->body;

        $valid = $this->validateXml($body);

        if ($valid === false)
        {
            $this->trace->warning(
                TraceCode::BLADE_VERES_PARSE_FAILURE,
                ['message' => 'Malformed xml: ' . $body]);

            return Enrolled::U;
        }

        return $this->xmlToArray($body);
    }

    protected function getVerifyEnrollmentRequestArray($input)
    {
        $content = $this->getVEReqContent($input);

        $options = $this->getRequestOptions();

        $type = $input['card']['network'];

        $request = $this->getStandardRequestArray($content, 'POST', $type, $options);

        return $request;
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            // TODO: Select client certificates from terminals

            // $this->trace->info(
                // TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                // [
                    // 'clientCertPath' => $clientCertPath
                // ]);
        }

        return $clientCertPath;
    }

    protected function getClientSslKey()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientSslKeyName();

        if (file_exists($clientCertPath) === false)
        {
            // TODO: Select client certificates from terminals

            // $this->trace->info(
                // TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                // [
                    // 'clientCertPath' => $clientCertPath
                // ]);
        }

        return $clientCertPath;
    }

    public function getClientCertificateName()
    {
        switch ($this->input['card']['network'])
        {
            case Card\Network::MC:
                $certName = $this->config['live_mastercard_certificate'];
                break;

            case Card\Network::VISA:
                $certName = $this->config['live_visa_certificate'];
                break;
        }

        return $certName;
    }

    public function getClientSslKeyName()
    {
        switch ($this->input['card']['network'])
        {
            case Card\Network::MC:
                $certName = $this->config['live_mastercard_pem'];
                break;

            case Card\Network::VISA:
                $certName = $this->config['live_visa_pem'];
                break;
        }

        return $certName;
    }

    protected function getPayerAuthenticationContent($input, $response)
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
                        'acqBIN'      => $this->getAcquirerBin($input),
                        'merID'       => $this->getMerchantId($input),
                        // TODO: Make it dynamic
                        'name'        => $input['merchant']->getBillingLabel(),
                        // TODO: Use country class
                        'country'     => '356',
                        'url'         => 'https://razorpay.com',
                    ],
                    'Purchase' => [
                        'xid'         => $this->generateXid($input),
                        'date'        => $date,
                        'amount'      => $this->getFormattedAmount($input['payment']),
                        'purchAmount' => $input['payment']['amount'],
                        'currency'    => Currency::getIsoCode($input['payment']['currency']),
                        'exponent'    => '2',
                    ],
                    'CH' => [
                        'acctID'      => $response['Message']['VERes']['CH']['acctID'],
                        'expiry'      => $this->getFormattedCardExpiry($input['card']),
                    ]
                ]
            ]
        ];

        $xml = Xml::create('ThreeDSecure', $content);

        $xml = zlib_encode($xml, 15);
        $xml = base64_encode($xml);

        return $xml;
    }

    private function generateXid(array $input)
    {
        $xid = str_pad($input['payment']['id'], 20, '0', STR_PAD_LEFT);

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

    protected function getVEReqContent($input)
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
                        'acqBIN' => $this->getAcquirerBin($input),
                        'merID'  => $this->getMerchantId($input),
                        // 'password' => $creds['password'],
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

    protected function getAcquirerBin($input)
    {
        if ($this->mode === Mode::TEST)
        {
            return '11111111111';
        }

        switch ($input['card']['network'])
        {
            case Card\Network::MC:
                $certName = $this->config['live_mastercard_acq_bin'];
                break;

            case Card\Network::VISA:
                $certName = $this->config['live_visa_acq_bin'];
                break;
        }
    }

    protected function getMerchantId($input)
    {
        if ($this->mode === Mode::TEST)
        {
            return '12AB,cd/34-EF  -g,5/H-67';
        }

        switch ($input['card']['network'])
        {
            case Card\Network::MC:
                $certName = $this->config['live_mastercard_merchant_id'];
                break;

            case Card\Network::VISA:
                $certName = $this->config['live_visa_merchant_id'];
                break;
        }
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
            // Throw Critical for now
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                'Invalid XML');
        }
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null, $options = [])
    {
        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Accept' => $this->app['request']->header('Accept'),
            'User-Agent' => $this->app['request']->header('User-Agent')
        ];

        $request['options']['timeout'] = 10;
        $request['options']['connect_timeout'] = 10;

        return $request;
    }

    protected function getUrl($type = null)
    {
        $urlDomain = $this->getUrlDomain($type);

        return $urlDomain;
    }

    protected function getUrlDomain($type = null)
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainType = $this->mode;

        $domainConstantName = strtoupper($domainType).'_'.strtoupper($type).'_DS';

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getRequestOptions()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        $options = [
            'hooks' => $hooks
        ];

        return $options;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientSslKey());

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    }
}
