<?php

namespace RZP\Gateway\Mpi\Blade;

use Cache;
use DOMDocument;
use Carbon\Carbon;
use Lib\Formatters\Xml;

use RZP\Exception;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Models\Card;
use RZP\Diag\EventCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Gateway\Mpi\Base;

use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base as BaseGateway;
use RZP\Gateway\Base\Action as Action;
use RZP\Models\Payment as PaymentEntity;
use RZP\Gateway\Mpi\Base\DeviceCategory;

class Gateway extends Base\Gateway
{
    const VERSION = '1.0.2';

    const EXPONENT = '2';
    const COUNTRY  = '356';

    const GATEWAY_ACCESS_CODE        = 'gateway_access_code';
    const GATEWAY_MERCHANT_ID2       = 'gateway_merchant_id2';
    const GATEWAY_TERMINAL_PASSWORD  = 'gateway_terminal_password';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    /**
     * Fingerprint of the root signing certificate. This ensures that
     * while any intermediate certs may change over time (provided they
     * are signed correctly and not expired), the root cert ensures that
     * the trust is in the same authority. So someone else cannot
     * create a new chain and use that.
     *
     * Note: Only put production cert fingerprints in here
     */
    const ROOT_CERT_FINGERPRINTS = [
        // MasterCard Root
        '32dfd35574d8811bb90ebe33846dd3a0b945e0d9',
        // VISA
        '70179b868c00a4fa609152223f9f3e32bde00562',
    ];

    protected $gateway = 'mpi_blade';

    /**
     * Authenticate the payment
     * As we cannot authorize payment using MPI only
     * So, currently we authenticate only, other gateway has to authorize payment
     */
    public function authorize(array $input)
    {
        parent::action($input, Action::AUTHORIZE);

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
        parent::action($input, Action::AUTHENTICATE);

        $runEnrollmentCheck = $this->runEnrollmentCheckForCard($input);

        if ($runEnrollmentCheck === false)
        {
            return null;
        }

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED,
            $input);

        // Send card enrollment verification request
        $response = $this->sendEnrollmentRequest($input);

        $attributes = $this->getVeresAttributesToSave($response, $input);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED,
            $input,
            null,
            [
                'enrolled' => $attributes[Base\Entity::ENROLLED]
            ]);

        $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        return $this->decideAuthStepAfterEnroll($input, $response);
    }

    protected function runEnrollmentCheckForCard(array $input)
    {
        // We will skip the enrollment check for all non IN cards if merchant has enabled feature
        if (($input['card']['country'] !== 'IN') and
            ($input['merchant']->isFeatureEnabled('skip_international_auth') === true))
        {
            return false;
        }

        return true;
    }

    protected function decideAuthStepAfterEnroll(array $input, array $response)
    {
        $enrolled = $this->processEnrollmentResponse($input, $response);

        //
        // Determine card enrollment status and take next action
        //
        switch ($enrolled)
        {
            case Base\Enrolled::Y:
                if ($this->isIvrPayment($input) === true)
                {
                    if ((empty($response[VERes::MESSAGE][VERes::VERES][VERes::EXTENSION][VERes::IVR_AUTH_DATA]) === true) or
                        (empty($response[VERes::MESSAGE][VERes::VERES][VERes::EXTENSION][VERes::IVR_AUTH_DATA_ENCRYPT_TYPE]) === false))
                    {
                        throw new Exception\GatewayErrorException(
                            ErrorCode::GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE,
                            null,
                            null,
                            [
                                'iin'    => $input['card']['iin'],
                                'issuer' => $input['card']['issuer']
                            ],
                            null,
                            BaseGateway\Action::AUTHENTICATE);
                    }

                    return $this->getOtpSubmitRequest($input, $response);
                }

                return $this->getPayerAuthenticationRequest($input, $response);

            case Base\Enrolled::N:
                return null;

            case Base\Enrolled::U:

                if ($input['card'][Card\Entity::INTERNATIONAL] === true)
                {
                    return null;
                }

            default:
                $this->trace->warning(
                    TraceCode::GATEWAY_ERROR_ISSUER_AUTHENTICATION_NOT_AVAILABLE,
                    [
                        'enrollment_status' => $enrolled,
                        'isInternational' => $input['card'][Card\Entity::INTERNATIONAL],
                        'iin' => $input['card'][Card\Entity::IIN]
                    ]);

                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
                    'enrollment_status' . $enrolled,
                    'Unexpected response',
                    [
                        'enrollment_status' => $enrolled,
                        'isInternational' => $input['card'][Card\Entity::INTERNATIONAL],
                        'iin' => $input['card'][Card\Entity::IIN]
                    ],
                    null,
                    Action::AUTHENTICATE,
                    true);
        }
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->model = $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        if ($input['payment']['auth_type'] === 'otp')
        {
            $input['gateway'] = $this->submitOtp($input);
        }

        $PARes = $this->validateAndGetPayerAuthenticationResponse($input);

        $this->updateGatewayPaymentFromCallbackResponse($gatewayPayment, $PARes);

        $eci = $gatewayPayment->getEci();

        $paresStatus = $PARes[PARes::TX][PARes::STATUS];

        $networkCode = Card\Network::getCode($input['card']['network']);

        $isInternational = $input['card']['international'];

        $this->validateAuthResponse($eci, $networkCode, $paresStatus, $isInternational);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_PROCESSED,
            $input);

        // Blade callback response field is being used by Hitachi
        // These fields are already set in gatewayPayment entity
        return $gatewayPayment->toArray();
    }

    protected function validateAuthResponse($eci, $networkCode, $paresStatus, $isInternational)
    {
        if (($networkCode === Card\Network::VISA) and
            (($eci === '05') or
             (($eci === '06') and
              ($isInternational === true))))
        {
            return true;
        }
        if ((in_array($networkCode, [Card\Network::MC, Card\Network::MAES], true) === true) and
            (($eci === '02') or
             (($eci === '01') and
              ($isInternational === true))))
        {
            return true;
        }

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED;

        if ($paresStatus === ParesStatus::A)
        {
            $errorCode = ErrorCode::GATEWAY_ERROR_AUTHENTICATION_STATUS_ATTEMPTED;
        }
        else if ($paresStatus === ParesStatus::N)
        {
            $errorCode = ErrorCode::GATEWAY_ERROR_AUTHENTICATION_STATUS_FAILED;
        }

        throw new Exception\GatewayErrorException(
            $errorCode,
            null,
            null,
            [
                'eci'             => $eci,
                'paresStatus'     => $paresStatus,
                'network'         => $networkCode,
                'isInternational' => $isInternational,
            ],
            null,
            BaseGateway\Action::AUTHENTICATE
        );
    }

    protected function getVeresAttributesToSave(array $response, array $input)
    {
        $attributes = [];

        if (isset($response[VERes::MESSAGE]['Error']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                null,
                null,
                $response[VERes::MESSAGE]['Error'],
                null,
                BaseGateway\Action::AUTHENTICATE);
        }

        $ch = $response[VERes::MESSAGE][VERes::VERES][VERes::CH];

        $attributes = [
            Base\Entity::ENROLLED   => $ch[VERes::ENROLLED],
            Base\Entity::PAYMENT_ID => $input['payment']['id'],
            Base\Entity::AMOUNT     => $input['payment']['amount'],
            Base\Entity::CURRENCY   => $input['payment']['currency'],
        ];

        if (empty($ch[VERes::ACCID]) === false)
        {
            $attributes[Base\Entity::ACC_ID] = $ch[VERes::ACCID];
        }

        return $attributes;
    }

    protected function getCallbackResponseAttributes($response)
    {
        $attributes = [
            Base\Entity::XID            => $response[PARes::PURCHASE][PARes::XID] ?? null,
            Base\Entity::CAVV           => $response[PARes::TX][PARes::CAVV] ?? null,
            Base\Entity::CAVV_ALGORITHM => $response[PARes::TX][PARes::CAVVALGORITHM] ?? null,
            Base\Entity::STATUS         => $response[PARes::TX][PARes::STATUS],
            Base\Entity::ECI            => $response[PARes::TX][PARes::ECI] ?? null,
        ];

        return $attributes;
    }

    /**
     * Decodes the Pares
     *
     * @param String base64 encoded PAres
     * @return string ParesXml
     * @throws Exception\GatewayErrorException if pares could not be inflated
     */
    protected function inflatePares($pares)
    {
        $decodePares = base64_decode($pares);

        try
        {
            $paresXml = gzinflate(substr($decodePares, 2));
        }
        catch (\ErrorException $e)
        {
            $message = $e->getMessage();

            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                                                      null,
                                                      $message,
                                                      [],
                                                      $e,
                                                      BaseGateway\Action::AUTHENTICATE);
        }

        return $paresXml;
    }

    protected function validateParesSignature($paresXml)
    {
        $dom = new DOMDocument;

        $dom->loadXML($paresXml);

        $adapter = new XmlseclibsAdapter;

        $adapter->setRootCertFingerprints(static::ROOT_CERT_FINGERPRINTS);

        $ret = false;

        try
        {
            $ret = $adapter->verify($dom);
        }
        catch (\Exception $e)
        {
            $msg = $e->getMessage();

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_PARES_XML_SIGNATURE_ERROR,
                null,
                $msg,
                [],
                $e,
                BaseGateway\Action::AUTHENTICATE);
        }

        if ($ret === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_PARES_XML_SIGNATURE_ERROR,
                null,
                null,
                [],
                null,
                BaseGateway\Action::AUTHENTICATE);
        }

        return $paresXml;
    }

    protected function submitOtp(array $input)
    {
        // Hack for updating the authentication terminal auth type
        $input['authenticate']['auth_type'] = 'otp';

        $pareq = $this->getPayerAuthenticationContent($input);

        $request = [
            'url'       => $this->cache->get('acs_url_' . $input['payment']['id']),
            'method'    => 'post',
            'content'   => [
                PAReq::PAREQ     => $pareq,
                PAReq::MD        => $input['payment']['id'],
                PAReq::TERMURL   => '',
            ]
        ];

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::PAYER_AUTHENTICATION_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response->body, $input, TraceCode::PAYER_AUTHENTICATION_RESPONSE);

        $decoded = [];

        parse_str($response->body, $decoded);

        return $decoded;
    }

    /**
     * Validates the PARes.the Pares is first base64 decode and inflate
     * and then converted to array .
     *
     * @param array $input
     * @return mixed
     * @throws Exception\GatewayErrorException if PARes cannot be validated
     * @throws Exception\RuntimeException if PARES cannot be converted to json
     */
    protected function validateAndGetPayerAuthenticationResponse(array $input)
    {
        $pares = $input['gateway'][PARes::GATEWAY_PARES];

        $paresXml = $this->inflatePares($pares);

        $paresArray = $this->xmlToArray($paresXml);

        $traceContent = $paresArray;
        unset($traceContent['Message']['Signature']);

        $this->trace->info(TraceCode::GATEWAY_PARES_RESPONSE,
            [
                'content'   => $traceContent,
            ]);

        // Validate Payer Authentication Response
        $this->validatePares($input, $paresArray);

        $this->validateXml($paresXml);

        $this->validateParesSignature($paresXml);

        $paresMessage = $paresArray[PARes::MESSAGE][PARes::PARES];

        return $paresMessage;
    }

    protected function validatePares(array $input, array $paresArray)
    {
        if (empty($paresArray[PARes::MESSAGE]) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_PARES_XML,
                null,
                'Message element not found',
                [],
                null,
                BaseGateway\Action::AUTHENTICATE);
        }

        if (isset($paresArray[PARes::MESSAGE][PARes::ERROR]) === true)
        {
            $error = $paresArray[PARes::MESSAGE][PARes::ERROR];
            $errorCode = $error[PARes::ERROR_CODE] ?? null;

            $internalCode = InvalidRequestCode::map($errorCode) ?: ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE;

            throw new Exception\GatewayErrorException(
                $internalCode,
                $errorCode,
                $error[PARes::ERROR_MESSAGE] ?? null,
                [
                   'PaRes'   => $paresArray,
                   'payment' => $input['payment'],
                   'network' => $input['card']['network'],
                ],
                null,
                BaseGateway\Action::AUTHENTICATE);
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
                'Credentials mismatch',
                [],
                null,
                BaseGateway\Action::AUTHENTICATE);
        }
    }

    // @codingStandardsIgnoreLine
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
                ],
                null,
                BaseGateway\Action::AUTHENTICATE);
        }
    }

    protected function processEnrollmentResponse(array $input, array $response)
    {
        $this->validateVERes($input, $response);

        $VERes = $response[VERes::MESSAGE][VERes::VERES];

        if ((isset($VERes[VERes::ERROR]) === true) and
            (count($VERes[VERes::ERROR]) !== 0))
        {
            $msg = 'Error message: ' . $VERes[VERes::ERROR_MSG] . ' ' .
                   'Error detail: ' . $VERes[VERes::ERROR_DETAILS];

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                '',
                $msg,
                [],
                null,
                BaseGateway\Action::AUTHENTICATE);
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

    protected function getOtpSubmitRequest(array $input): array
    {
        $response = func_get_arg(1);

        $this->cache->put('acs_url_' . $input['payment']['id'], $response[VERes::MESSAGE][VERes::VERES][VERes::URL], 20 * 60);

        return parent::getOtpSubmitRequest($input);
    }

    protected function sendEnrollmentRequest(array $input)
    {
        $request = $this->getEnrollmentRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_ENROLL_RESPONSE,
            [
                'gateway' => 'mpi_blade',
                'response' => $response->body,
                'payment_id' => $input['payment']['id']
            ]);

        $body = $response->body;

        $this->validateXml($body);

        return $this->xmlToArray($body);
    }

    protected function getEnrollmentRequestArray(array $input)
    {
        $traceContent = $content = $this->getVEReqContent($input);

        $this->paymentId = $input['payment']['id'];

        $options = $this->getRequestOptions();

        unset($traceContent[VEReq::MESSAGE][VEReq::VEREQ][VEReq::PAN]);
        unset($traceContent[VEReq::MESSAGE][VEReq::VEREQ][VEReq::MERCHANT][VEReq::PASSWORD]);

        $content = Xml::create('ThreeDSecure', $content);

        $type = $input['card']['network'];

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', $type, $options);

        $traceRequest['content'] = $traceContent;
        unset($traceRequest['options']['hooks']);

        $this->trace->info(TraceCode::GATEWAY_ENROLL_REQUEST, [
            'gateway' => 'mpi_blade',
            'payment_id' => $input['payment']['id'],
            'request' => $traceRequest
        ]);

        return $request;
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            $networkName = $this->getNetworkName();

            $cert = $this->config['live_' . $networkName . '_certificate'];

            $cert = str_replace('\n', "\n", $cert);

            file_put_contents($clientCertPath, $cert);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'clientCertPath' => $clientCertPath
                ]);
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
            $networkName = $this->getNetworkName();

            $cert = $this->config['live_' . $networkName . '_key'];

            $cert = str_replace('\n', "\n", $cert);

            file_put_contents($clientCertPath, $cert);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    protected function getCaInfo()
    {
        $clientCertPath = dirname(__FILE__) . '/cainfo/cainfo.pem';

        return $clientCertPath;
    }

    public function getClientCertificateName()
    {
        $networkName = $this->getNetworkName();

        return $networkName . '_v2.crt';
    }

    public function getClientSslKeyName()
    {
        $networkName = $this->getNetworkName();

        return $networkName . '_v2.key';
    }

    public function getNetworkName()
    {
        switch ($this->input['card']['network_code'])
        {
            case Card\Network::MC:
            case Card\Network::MAES:
                $network = Card\NetworkName::MC;
                break;

            case Card\Network::VISA:
                $network = Card\NetworkName::VISA;
                break;
        }

        return strtolower($network);
    }

    protected function getPayerAuthenticationContent(array $input, array $response = [])
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
                        PAReq::EXPONENT    => Currency::getExponent($input['payment']['currency']),
                    ],
                    PAReq::CH => [
                        PAReq::ACCID       => $response[VERes::MESSAGE][VERes::VERES][VERes::CH][VERes::ACCID] ?? null,
                        PAReq::EXPIRY      => $this->getFormattedCardExpiry($input['card']),
                    ]
                ]
            ]
        ];

        if ($this->isIvrPayment($input) === true)
        {
            $content[PAReq::MESSAGE][PAReq::MSG_PAREQ][PAReq::CH][PAReq::ACCID] = $this->model->getAccId();

            $content[PAReq::MESSAGE][PAReq::MSG_PAREQ][PAReq::EXTENSION] = [
                PAReq::ATTRIBUTES => [
                    PAReq::ID       => 'visa.3ds.india_ivr',
                    PAReq::CRITICAL => 'false',
                ],
                PAReq::IVR_AUTH_USER_DATA => [
                    PAReq::ATTRIBUTE => [
                        PAReq::ATTRIBUTES => [
                            PAReq::NAME      => 'OTP2',
                            PAReq::VALUE     => $input['gateway']['otp'],
                            PAReq::STATUS    => 'Y',
                            PAReq::ENCRYPTED => 'false',
                        ],
                    ]
                ]
            ];
        }

        $xml = Xml::create('ThreeDSecure', $content);

        $this->traceGatewayPaymentRequest(['content' => $content, 'xml' => $xml], $input, TraceCode::PAYER_AUTHENTICATION_REQUEST);

        $xml = zlib_encode($xml, 15);
        $xml = base64_encode($xml);

        return $xml;
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

    // @codingStandardsIgnoreLine
    protected function getVEReqContent(array $input)
    {
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
                    ],
                    VEReq::BROWSER    => [
                        VEReq::DEVICE_CATEGORY => DeviceCategory::getDeviceCategory(DeviceCategory::DESKTOP),
                        VEReq::DEVICE_ACCEPT   => $accept,
                        VEReq::DEVICE_UA       => $userAgent,
                    ],
                ]
            ]
        ];

        if ($this->isIvrPayment($input) === true)
        {
            $content[VEReq::MESSAGE][VEReq::VEREQ][VEReq::EXTENSION] = [
                VEReq::ATTRIBUTES => [
                    VEReq::ID       => 'visa.3ds.india_ivr',
                    VEReq::CRITICAL => 'false',
                ],
                VEReq::IVR_CH_PHONE_FORMAT    => 'D',
                VEReq::IVR_CH_PHONE           => '',
                VEReq::IVR_PAREQ_CHANNEL      => 'DIRECT',
                VEReq::IVR_SHOP_CHANNEL       => 'IVR',
                VEReq::IVR_AVAIL_AUTH_CHANNEL => 'SMS',
                VEReq::IVR_ITP_CREDENTIAL     => '',
            ];
        }

        $traceContent = $content;

        return $content;
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

    protected function getMerchantId(array $input)
    {
        $merchantId = '';

        $gateway = $input['payment']['gateway'];

        $network = $input['card']['network_code'];

        if ($gateway === PaymentEntity\Gateway::HDFC)
        {
            return $input['terminal'][Terminal\Entity::GATEWAY_MERCHANT_ID];
        }

        if ($gateway == PaymentEntity\Gateway::FIRST_DATA)
        {
            // For Authenticating First Data requests we need to create merid by appending id provided from
            // first data with the store id that is placed in gateway_merchant_id field in terminal
            $envMerchantId = $this->config[$gateway]['live_merchant_id'];
            $storeId = $input['terminal'][Terminal\Entity::GATEWAY_MERCHANT_ID];

            return $envMerchantId . substr($storeId, -8);
        }

        switch ($network)
        {
            case Card\Network::MC:
            case Card\Network::MAES:
                $merchantId = $this->config['live_mastercard_merchant_id'];

                break;

            case Card\Network::VISA:
                $merchantId = $this->config['live_visa_merchant_id'];

                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID,
                    null,
                    null,
                    [],
                    null,
                    BaseGateway\Action::AUTHENTICATE);
        }

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->config['test_merchant_id'];
        }

        return $merchantId;
    }

    protected function validateXml($xml)
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
            $dom->loadXML($xml);
        }
        catch (\Exception $e)
        {
            $error = $e->getMessage();

            $internalCode = ErrorCode::GATEWAY_ERROR_INVALID_PARES_XML;

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
                    $internalCode = ErrorCode::BAD_REQUEST_PAYMENT_PARES_XML_SIGNATURE_ERROR;
            }

            throw new Exception\GatewayErrorException(
                $internalCode,
                null,
                $error,
                [
                    'pares' => $xml,
                ],
                $e,
                BaseGateway\Action::AUTHENTICATE);
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

        $request['options'] = $options;

        $request['options']['timeout'] = 20;
        $request['options']['connect_timeout'] = 20;
        $request['options']['verify'] = $this->getCaInfo();

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
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    protected function isIvrPayment($input)
    {
        return ((isset($input['authenticate']['auth_type']) === true) and
                ($input['authenticate']['auth_type'] === 'otp'));
    }
}
