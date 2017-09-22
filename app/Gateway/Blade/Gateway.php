<?php

namespace RZP\Gateway\Blade;

use Cache;
use Carbon\Carbon;
use GuzzleHttp;
use DOMDocument;
use RZP\Constants\Timezone;
use RZP\Exception;
use Requests_Hooks;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Lib\Formatters\Xml;
use RZP\Models\Currency\Currency;

class Gateway extends Base\Gateway
{
    const VERSION = '1.0.2';

    const EXPONENT = '2';
    const COUNTRY  = '356';

    const GATEWAY_ACCESS_CODE        = 'gateway_access_code';
    const GATEWAY_MERCHANT_ID2       = 'gateway_merchant_id2';
    const GATEWAY_TERMINAL_PASSWORD  = 'gateway_terminal_password';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    protected $gateway = 'blade';


    /**
     * Authenticate the payment
     * As we cannot authorize payment using MPI only
     * So, currently we authenticate only, other gateway has to authorize payment
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        return $this->authenticate($input);
    }

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

        $attributes = $this->getVeresAttributesToSave($response, $input);

        $this->createGatewayPaymentEntity($attributes, $input);

        return $this->decideAuthStepAfterEnroll($input, $response);
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

        $eci = $gatewayPayment->getEci();

        $network = strtoupper($input['card']['network']);

        $this->validateEci($eci, $network);

        $txnStatus = $PARes[PARes::TX][PARes::STATUS];

        $authenticateStatus = ParesStatus::getAuthenticationStatus($txnStatus);

        if ($authenticateStatus !== AuthenticationStatus::Y)
        {
            // Throw GatewayErrorException with authentication failed error code
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED);
        }

        // Blade callback response field is being used by Hitachi
        // These fields are already set in gatewayPayment entity
        return $gatewayPayment->toArray();
    }

    protected function getVeresAttributesToSave(array $response, array $input)
    {
        $attributes = [];

        $ch = $response[VERes::MESSAGE][VERes::VERES][VERes::CH];

        $attributes = [
            Entity::ENROLLED   => $ch[VERes::ENROLLED],
            Entity::PAYMENT_ID => $input['payment']['id'],
            Entity::AMOUNT     => $input['payment']['amount'],
            Entity::CURRENCY   => $input['payment']['currency'],
        ];

        if (empty($ch[VERes::ACCID]) === false)
        {
            $attributes[Entity::ACC_ID] = $ch[VERes::ACCID];
        }

        return $attributes;
    }

    protected function updateGatewayPaymentFromCallbackResponse(
        Entity $gatewayPayment,
        array $resp)
    {
        $gatewayPayment->setXid($resp[PARes::PURCHASE][PARes::XID]);

        $gatewayPayment->setCavv($resp[PARes::TX][PARes::CAVV]);

        $gatewayPayment->setCavvAlgorithm($resp[PARes::TX][PARes::CAVVALGORITHM]);

        $gatewayPayment->setStatus($resp[PARes::TX][PARes::STATUS]);

        $gatewayPayment->setEci($resp[PARes::TX][PARes::ECI]);

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

    protected function validateEci(string $eci = null, string $networkCode)
    {
        if ((($networkCode === Card\Network::VISA) and ($eci !== '05')) or
            (($networkCode === Card\Network::MC) and ($eci !== '02')))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
                $eci,
                'Invalid Eci value for network ' . $networkCode);
        }
    }

    protected function validateSignatureAndInflatePares($paresXml)
    {
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
        $pares = $input['gateway'][PARes::GATEWAY_PARES];

        $pares = base64_decode($pares);

        $paresXml = $this->validateSignatureAndInflatePares($pares);

        $paresArray = $this->xmlToArray($paresXml);

        // Validate Payer Authentication Response
        $this->validatePaRes($input, $paresArray);

        $paresMessage = $paresArray[PARes::MESSAGE][PARes::PARES];

        return $paresMessage;
    }

    protected function validatePARes(array $input, array $paresArray)
    {
        if (empty($paresArray[PARes::MESSAGE]) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                'Message element not found');
        }

        (new Validator)->rules(Validator::$paresRules)
                       ->input($paresArray)
                       ->strict(false)
                       ->validate();

        $paresMessage = $paresArray[PARes::MESSAGE][PARes::PARES];

        if (in_array($paresMessage[PARes::TX][PARes::STATUS], [ParesStatus::Y, ParesStatus::A], true))
        {
            Validator::validateLastFour($input['card']['last4'], $paresMessage[PARes::PAN]);
        }

        $expectedXid = $this->generateXid($input);

        Validator::validateResponse($paresMessage, $input);

        Validator::validatePaymentId($paresArray, $input);

        Validator::validateXid($paresMessage, $expectedXid);

        $this->validateCredentials($input, $paresMessage);
    }

    protected function validateCredentials(array $input, array $paresMessage)
    {
        if (($paresMessage[PARes::MERCHANT][PARes::ACQBIN] !== $this->getAcquirerBin($input)) or
            ($paresMessage[PARes::MERCHANT][PARes::MERID] !== $this->getMerchantId($input)))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Credentials mismatch');
        }
    }

    protected function validateVERes(array $input, array $response)
    {
        $this->trace->info(TraceCode::VERIFY_ENROLLMENT_RESPONSE, $response);

        (new Validator)->rules(Validator::$veresRules)
                       ->input($response)
                       ->validate();

        if ($response[VERes::MESSAGE][VERes::ATTRIBUTES][VERes::ID] !== $input['payment']['public_id'])
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Invalid payment id received',
                [
                    'expected' => $input['payment']['public_id'],
                    'actual'   => $response[VERes::MESSAGE][VERes::ATTRIBUTES][VERes::ID],
                ]);
        }
    }

    protected function processEnrollmentResponse(array $input, array $response)
    {
        $this->validateVERes($input, $response);

        $VERes = $response[VERes::MESSAGE][VERes::VERES];

        if ((isset($VERes[VERes::ERROR]) === true) and
            (count($VERes[VERes::ERROR]) !== 0))
        {
            $msg = 'Error message: ' . $error[VERes::ERROR_MSG] . ' ' .
                   'Error detail: ' . $error[VERes::ERROR_DETAILS];

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                $msg);

            //TODO check if we need to trace response
        }

        $ch = $VERes[VERes::CH];

        return $ch[VERes::ENROLLED];
    }

    protected function getPayerAuthenticationRequest(array $input, array $response)
    {
        $url = $response[VERes::MESSAGE][VERes::VERES][VERes::URL];

        $pareq = $this->getPayerAuthenticationContent($input, $response);

        $request = [
            'url'       => $url,
            'method'    => 'post',
            'content'   => [
                PAReq::PAREQ     => $pareq,
                PAReq::TERMURL   => $input['callbackUrl'],
                PAReq::MD        => $input['payment']['id']
            ]
        ];

        return $request;
    }

    protected function sendEnrollmentRequest(array $input)
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

    protected function getEnrollmentRequestArray(array $input)
    {
        $content = $this->getVEReqContent($input);

        //$options = $this->getRequestOptions();

        $options = [];

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
        switch ($this->input['card']['network_code'])
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
        switch ($this->input['card']['network_code'])
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

    protected function getPayerAuthenticationContent(array $input, array $response)
    {
        // Format YYYYMMDD HH:MM:SS
        $date = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)->format('Ymd H:m:s');

        $mid = $input['payment']['public_id'];

        $content = [
            PAReq::MESSAGE => [
                PAReq::ATTRIBUTES => [
                    PAReq::ID     => $mid,
                ],
                PAReq::MSG_PAREQ => [
                    PAReq::VERSION => self::VERSION,
                    PAReq::MERCHANT => [
                        PAReq::ACQBIN      => $this->getAcquirerBin($input),
                        PAReq::MERID       => $this->getMerchantId($input),
                        PAReq::NAME        => $this->getDynamicMerchantName($input['merchant']),
                        PAReq::COUNTRY     => self::COUNTRY,
                        PAReq::URL         => $this->app['config']->get('app.url'),
                    ],
                    PAReq::PURCHASE => [
                        PAReq::XID         => $this->generateXid($input),
                        PAReq::DATE        => $date,
                        PAReq::AMOUNT      => $this->getFormattedAmount($input['payment']),
                        PAReq::PURCHAMOUNT => $input['payment']['amount'],
                        PAReq::CURRENCY    => Currency::getIsoCode($input['payment']['currency']),
                        PAReq::EXPONENT    => self::EXPONENT,
                    ],
                    PAReq::CH => [
                        PAReq::ACCID       => $response[VERes::MESSAGE][VERes::VERES][VERes::CH][VERes::ACCID],
                        PAReq::EXPIRY      => $this->getFormattedCardExpiry($input['card']),
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

    protected function getVEReqContent(array $input)
    {
        $creds = $this->getCreds();

        $accept = substr($this->app['request']->header('Accept'), 0, 2048);
        $userAgent = substr($this->app['request']->header('User-Agent'), 0, 256);

        $content = [
            VEReq::MESSAGE => [
                VEReq::ATTRIBUTES => [
                    'id' => $input['payment']['public_id']
                ],
                VEReq::VEREQ => [
                    VEReq::VERSION    => self::VERSION,
                    VEReq::PAN        => $input['card']['number'],
                    VEReq::MERCHANT   => [
                        VEReq::ACQBIN       => $this->getAcquirerBin($input),
                        VEReq::MERCHANT_ID  => $this->getMerchantId($input),
                        // 'password' => $creds['password'],
                    ],
                    VEReq::BROWSER    => [
                        VEReq::DEVICE_CATEGORY => DeviceCategory::DESKTOP,
                        VEReq::DEVICE_ACCEPT   => $accept,
                        VEReq::DEVICE_UA       => $userAgent,
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
                VEReq::ACQ_BIN          => $this->config[self::GATEWAY_ACCESS_CODE],
                VEReq::CRED_MERCHANT_ID => $this->config[self::GATEWAY_MERCHANT_ID2],
                VEReq::PASSWORD         => $this->config[self::GATEWAY_TERMINAL_PASSWORD],
            ];
        }
        else
        {
            $terminal = $this->terminal;

            $creds = [
                VEReq::ACQ_BIN          => $terminal[self::GATEWAY_ACCESS_CODE],
                VEReq::CRED_MERCHANT_ID => $terminal[self::GATEWAY_MERCHANT_ID2],
                VEReq::PASSWORD         => $terminal[self::GATEWAY_TERMINAL_PASSWORD],
            ];
        }

        return $creds;
    }

    protected function getAcquirerBin(array $input)
    {
        $certName = '';

        switch ($input['card']['network_code'])
        {
            case Card\Network::MC:
                $certName = $this->config['live_mastercard_acq_bin'];
                break;

            case Card\Network::VISA:
                $certName = $this->config['live_visa_acq_bin'];
                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID);

        }

        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_acq_bin'];
        }

        return $certName;
    }

    protected function getMerchantId(array $input)
    {
        $merchantId = '';

        switch ($input['card']['network_code'])
        {
            case Card\Network::MC:
                $merchantId = $this->config['live_mastercard_merchant_id'];

                break;

            case Card\Network::VISA:
                $merchantId = $this->config['live_visa_merchant_id'];

                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID);
        }

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->config['test_merchant_id'];;
        }

        return $merchantId;
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
            'Accept'       => $this->app['request']->header('Accept'),
            'User-Agent'   => $this->app['request']->header('User-Agent')
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
