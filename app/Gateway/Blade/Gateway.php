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

    const GATEWAY_ACCESS_CODE        = 'gateway_access_code';
    const GATEWAY_MERCHANT_ID2       = 'GATEWAY_MERCHANT_ID2';
    const GATEWAY_TERMINAL_PASSWORD  = 'GATEWAY_TERMINAL_PASSWORD';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    /**
     * Authenticate the payment
     *
     * @param array $input Input
     *
     * @return void
     */
    public function authenticate(array $input)
    {
        // TODO: Add card range cache

        // Send card enrollment verification request
        $response = $this->sendEnrollmentRequest($input);

        $attributes = $this->getVeresAttributesToSave($response);

        $this->createGatewayPaymentEntity($attributes, $input);

        $this->decideAuthStepAfterEnroll($input, $response);
    }

    protected function decideAuthStepAfterEnroll(array $input, array $response)
    {
        $enrolled = $this->processEnrollmentResponse($input, $response);
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

        $ch = $response[VereqResponse::MESSAGE][VereqResponse::VERES][VereqResponse::CH];

        $attributes = [
            Entity::ENROLLED   => $ch[VereqResponse::ENROLLED],
            Entity::PAYMENT_ID => $input['payment']['id'],
            Entity::AMOUNT     => $input['payment']['amount'],
            Entity::CURRENCY   => $input['payment']['currency'],
        ];

        if (empty($ch[VereqResponse::ACCID]) === false)
        {
            $attributes[Entity::ACC_ID] = $ch[VereqResponse::ACCID];
        }

        return $attributes;
    }

    protected function updateGatewayPaymentFromCallbackResponse(
        Entity $gatewayPayment,
        array $resp)
    {
        $gatewayPayment->setXid($resp[ParesResponse::PURCHASE][ParesResponse::XID]);

        $gatewayPayment->setCavv($resp[ParesResponse::TX][ParesResponse::CAVV]);

        $gatewayPayment->setCavvAlgorithm($resp[ParesResponse::TX][ParesResponse::CAVVALGORITHM]);

        $gatewayPayment->setStatus($resp[ParesResponse::TX][ParesResponse::STATUS]);

        $gatewayPayment->setEci($resp[ParesResponse::TX][ParesResponse::ECI]);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function createGatewayPaymentEntity(array $attributes, array $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

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
                    $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_INVALID;

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
        $pares = $input['gateway'][ParesResponse::GATEWAY_PARES];

        $pares = base64_decode($pares);

        $paresXml = $this->validateSignatureAndInflatePares($pares);

        $paresArray = $this->xmlToArray($paresXml);

        // Validate Payer Authentication Response
        $this->validatePayerAuthenticationResponse($input, $paresArray);

        $paresMessage = $paresArray[ParesResponse::MESSAGE][ParesResponse::PARES];

        return $paresMessage;
    }

    protected function validatePayerAuthenticationResponse(array $input, $paresArray)
    {
        if (empty($paresArray[ParesResponse::MESSAGE]) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                'Message element not found');
        }

        (new Validator)->rules(Validator::$paresRules)
                       ->input($paresArray)
                       ->strict(false)
                       ->validate();

        $paresMessage = $paresArray[ParesResponse::MESSAGE][ParesResponse::PARES];

        if (in_array($paresMessage[ParesResponse::TX][ParesResponse::STATUS], [ParesStatus::Y, ParesStatus::A], true))
        {
            Validator::validateLastFour($input['card']['last4'], $paresMessage[ParesResponse::PAN]);
        }

        $expectedXid = $this->generateXid($input);

        Validator::validateResponse($paresMessage, $input);

        Validator::validateXid($paresMessage, $expectedXid);

        $this->validateCredentials($input, $paresMessage);
    }

    protected function validateCredentials($input, $paresMessage)
    {
        if (($paresMessage[ParesResponse::MERCHANT][ParesResponse::ACQBIN] !== $this->getAcquirerBin($input)) or
            ($paresMessage[ParesResponse::MERCHANT][ParesResponse::MERID] !== $this->getMerchantId($input)))
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

        (new Validator)->rules(Validator::$veresRules)
                       ->input($response)
                       ->validate();

        if ($response[VereqResponse::MESSAGE][VereqResponse::ATTRIBUTES][VereqResponse::ID] !== $input['payment']['public_id'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Invalid payment id received',
                [
                    'expected' => $input['payment']['public_id'],
                    'actual'   => $response[VereqResponse::MESSAGE][VereqResponse::ATTRIBUTES][VereqResponse::ID],
                ]);
        }
    }

    protected function processEnrollmentResponse($input, $response)
    {
        $this->validateVERes($input, $response);

        $VERes = $response[VereqResponse::MESSAGE][VereqResponse::VERES];

        if ((isset($VERes[VereqResponse::ERROR]) === true) and
            (count($VERes[VereqResponse::ERROR]) !== 0))
        {
            $msg = 'Error message: ' . $error[VereqResponse::ERROR_MSG] . ' ' .
                   'Error detail: ' . $error[VereqResponse::ERROR_DETAILS];

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                $msg);

            //TODO check if we need to trace response
        }

        $ch = $VERes[VereqResponse::CH];

        return $ch[VereqResponse::ENROLLED];
    }

    protected function getPayerAuthenticationRequest($input, $response)
    {
        $url = $response[VereqResponse::MESSAGE][VereqResponse::VERES][VereqResponse::URL];

        $pareq = $this->getPayerAuthenticationContent($input, $response);

        $request = [
            'url'       => $url,
            'method'    => 'post',
            'content'   => [
                PareqRequest::PAREQ     => $pareq,
                PareqRequest::TERMURL   => $input['callbackUrl'],
                PareqRequest::MD        => $input['payment']['id']
            ]
        ];

        return $request;
    }

    protected function sendEnrollmentRequest($input)
    {
        $request = $this->getEnrollmentRequestArray($input);

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

            throw new Exception\LogicException(
                'Unexpected response',
                null,
                [
                    'payment_id'  => $input['payment']['id'],
                    'body'        => $body
                ]
            );
        }

        return $this->xmlToArray($body);
    }

    protected function getEnrollmentRequestArray($input)
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
            PareqRequest::MESSAGE => [
                PareqRequest::ATTRIBUTES => [
                    PareqRequest::ID     => $mid,
                ],
                PareqRequest::MSG_PAREQ => [
                    PareqRequest::VERSION => self::VERSION,
                    PareqRequest::MERCHANT => [
                        PareqRequest::ACQBIN      => $this->getAcquirerBin($input),
                        PareqRequest::MERID       => $this->getMerchantId($input),
                        PareqRequest::NAME        => $input['merchant']->getBillingLabel(),
                        // TODO: Use country class
                        PareqRequest::COUNTRY     => '356',
                        PareqRequest::URL         => 'https://razorpay.com',
                    ],
                    PareqRequest::PURCHASE => [
                        PareqRequest::XID         => $this->generateXid($input),
                        PareqRequest::DATE        => $date,
                        PareqRequest::AMOUNT      => $this->getFormattedAmount($input['payment']),
                        PareqRequest::PURCHAMOUNT => $input['payment']['amount'],
                        PareqRequest::CURRENCY    => Currency::getIsoCode($input['payment']['currency']),
                        PareqRequest::EXPONENT    => '2',
                    ],
                    PareqRequest::CH => [
                        PareqRequest::ACCID      => $response['Message']['VERes']['CH']['acctID'],
                        PareqRequest::EXPIRY      => $this->getFormattedCardExpiry($input['card']),
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
            VereqRequest::MESSAGE => [
                VereqRequest::ATTRIBUTES => [
                    'id' => $input['payment']['public_id']
                ],
                VereqRequest::VEREQ => [
                    VereqRequest::VERSION    => self::VERSION,
                    VereqRequest::PAN        => $input['card']['number'],
                    VereqRequest::MERCHANT   => [
                        VereqRequest::ACQBIN       => $this->getAcquirerBin($input),
                        VereqRequest::MERCHANT_ID  => $this->getMerchantId($input),
                        // 'password' => $creds['password'],
                    ],
                    VereqRequest::BROWSER    => [
                        VereqRequest::DEVICE_CATEGORY => DeviceCategory::DESKTOP,
                        VereqRequest::DEVICE_ACCEPT   => $accept,
                        VereqRequest::DEVICE_UA       => $userAgent,
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
                VereqRequest::ACQ_BIN          => $this->config[self::GATEWAY_ACCESS_CODE],
                VereqRequest::CRED_MERCHANT_ID => $this->config[self::GATEWAY_MERCHANT_ID2],
                VereqRequest::PASSWORD         => $this->config[self::GATEWAY_TERMINAL_PASSWORD],
            ];
        }
        else
        {
            $terminal = $this->terminal;

            $creds = [
                VereqRequest::ACQ_BIN          => $terminal[self::GATEWAY_ACCESS_CODE],
                VereqRequest::CRED_MERCHANT_ID => $terminal[self::GATEWAY_MERCHANT_ID2],
                VereqRequest::PASSWORD         => $terminal[self::GATEWAY_TERMINAL_PASSWORD],
            ];
        }

        return $creds;
    }

    protected function getAcquirerBin($input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_acq_bin'];
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
            return $this->config['test_merchant_id'];;
        }

        switch ($input['card']['network'])
        {
            case Card\Network::MC:
                return $this->config['live_mastercard_merchant_id'];

            case Card\Network::VISA:
                return $this->config['live_visa_merchant_id'];
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
        $domain = $type;

        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainMode = $this->mode;

        $domainConstantName = strtoupper($domainMode) . '_' . strtoupper($domain) . '_DS';

        if (defined($urlClass . '::' . $domainConstantName))
        {
            return constant($urlClass . '::' . $domainConstantName);
        }
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
    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }
}
