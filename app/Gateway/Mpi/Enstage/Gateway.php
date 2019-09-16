<?php

namespace RZP\Gateway\Mpi\Enstage;

use Cache;
use RZP\Diag\EventCode;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Gateway\Mpi\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\AESCrypto;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\CardCacheTrait;
use RZP\Gateway\Base as BaseGateway;

class Gateway extends Base\Gateway
{
    use CardCacheTrait;

    const CACHE_KEY               = 'enstage_%s_card_details';

    const CARD_CACHE_TTL          = 20;

    const GATEWAY_MERCHANT_ID     = 'gateway_merchant_id';

    const CHECKSUM_ATTRIBUTE      = 'messageHash';

    protected $secureCacheDriver;

    protected $gateway = 'mpi_enstage';

    protected $sortRequestContent = false;

    protected $map = [
        Field::MERCHANT_TXN_ID          => 'merchantTxnId',
        Field::ACS_TXN_ID               => 'acsTxnId',
        Field::RESPONSE_CODE            => 'resDesc',
        Field::MESSAGE_HASH             => 'messageHash',
        Field::OTP_RESEND_COUNT_LEFT    => 'resendCountLeft',
    ];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->secureCacheDriver = $this->getDriver($input);
    }

    /**
     * Authenticate the payment
     * As we cannot authorize payment using MPI only
     * So, currently we authenticate only, other gateway has to authorize payment
     */
    public function authorize(array $input)
    {
        $this->action($input, Action::OTP_GENERATE);

        return $this->otpGenerate($input);
    }

    /**
     * Authenticate the payment
     *
     * @param array $input Input
     *
     * @return void
     */
    public function otpGenerate(array $input)
    {
        if ((isset($input['otp_resend']) === true) and ($input['otp_resend'] === true))
        {
            return $this->otpResend($input);
        }

        $this->action($input, Action::OTP_GENERATE);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED,
            $input);

        $response = $this->sendOtpGenerateRequest($input);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_RESPONSE);

        $this->validateResponseContent($response);

        $attributes = $this->getVeresAttributesToSave($response, $input);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED,
            $input,
            null,
            [
                'enrolled'  => $attributes[Base\Entity::ENROLLED]
            ]);

        $gatewayPaymentEntity = $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        return $this->decideAuthStepAfterEnroll($gatewayPaymentEntity, $input, $response);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackOtpSubmit($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->setCardNumberAndCvv($input);

        $response = $this->sendOtpValidateRequest($input, $gatewayPayment);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_RESPONSE);

        $this->validateResponseContent($response);

        $this->updateGatewayPaymentFromCallbackResponse($gatewayPayment, $response);

        $this->handleError($response, $input['payment']['id']);

        $this->app['diag']->trackGatewayPaymentEvent(
            EventCode::PAYMENT_AUTHENTICATION_PROCESSED,
            $input);

        return $gatewayPayment->toArray();
    }

    public function otpResend(array $input)
    {
        $this->action($input, Action::OTP_RESEND);

        $gatewayPaymentEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->setCardNumberAndCvv($input);

        $response = $this->sendOtpResendRequest($input, $gatewayPaymentEntity);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_PAYMENT_OTP_RESEND_RESPONSE);

        $this->handleError($response, $input['payment']['id']);

        return $this->getOtpSubmitRequest($input);
    }

    protected function sendOtpGenerateRequest(array $input)
    {
        $content = $this->getOtpGenerateRequestContent($input);

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST');

        $traceRequest['content'] = $content;

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $arrayResponse = $this->jsonToArray($response->body);

        return $arrayResponse;
    }

    protected function getOtpGenerateRequestContent(array $input)
    {
        $merchantId = $this->getMerchantId();
        $merchantName = $this->getMerchantName();

        $payment = $input['payment'];

        $hashContent = [
            Field::MERCHANT_TXN_ID => $payment['id'],
            Field::CARD_NUMBER     => $input['card']['number'],
            Field::MERCHANT_ID     => $merchantId,
            Field::AMOUNT          => $payment['amount'],
            Field::SECRET          => $this->getSecret(),
        ];

        $userAgent = substr($this->app['request']->header('User-Agent'), 0, 255);

        $userIP = $this->app['request']->getClientIp();

        $content = [
            Field::VERSION          => Constant::VERSION,
            Field::MERCHANT_TXN_ID  => $payment['id'],
            Field::CARD_DETAILS     => $this->getEncryptedCardDetails($input),
            Field::TXN_DETAILS      => [
                Field::EXPAY_IDENTIFIER     => Constant::EXPAY_IDENTIFIER,
                Field::PAYMENT_GATEWAY_NAME => Constant::PAYMENT_GATEWAY_NAME,
                Field::MERCHANT_NAME        => $merchantName,
                Field::MERCHANT_ID          => $merchantId,
                Field::AMOUNT               => (string) $payment['amount'],
                Field::CURRENCY             => Currency::getIsoCode($payment['currency']),
                Field::CURRENCY_EXPONENT    => (string) Currency::getExponent($payment['currency']),
                Field::ORDER_DESCRIPTION    => $payment['description'] ?? Constant::DEFAULT_ORDER_MESSAGE,
                Field::DEVICE_CATEGORY      => DeviceCategory::getDeviceCategory(DeviceCategory::DESKTOP),
                Field::ACQUIRER_BIN         => $this->getAcquirerBin($input),
            ],
            Field::MESSAGE_HASH        => $this->generateHash($hashContent),
            Field::ADDITIONAL_DATA_REQ => [
                Field::USER_AGENT => $userAgent,
                Field::USER_IP    => $userIP,
            ]
        ];

        return $content;
    }

    protected function getEncryptedCardDetails($input)
    {
        $card = $input['card'];

        $data = [
            'cardNumber' => $card['number'],
            'expiry'     => $this->getFormattedCardExpiry($card)
        ];

        return $this->encrypt(json_encode($data));
    }

    protected function getFormattedCardExpiry(array $card)
    {
        $year = substr($card['expiry_year'], -2);

        $month = str_pad($card['expiry_month'], 2, 0, STR_PAD_LEFT);

        return $month . $year;
    }

    protected function getVeresAttributesToSave($response, $input)
    {
        $attributes = [
            Base\Entity::PAYMENT_ID           => $input['payment']['id'],
            Base\Entity::GATEWAY_PAYMENT_ID   => $response[Field::ACS_TXN_ID] ?? '',
            Base\Entity::AMOUNT               => $input['payment']['amount'],
            Base\Entity::CURRENCY             => $input['payment']['currency'],
            Base\Entity::RESPONSE_CODE        => $response[Field::RESPONSE_CODE],
            Base\Entity::ENROLLED             => $this->getEnrolledStatus($response[Field::RESPONSE_CODE]),
        ];

        return $attributes;
    }

    protected function getEnrolledStatus($responseCode)
    {
        switch ($responseCode)
        {
            case '000':
                $enrollmentStatus = Base\Enrolled::Y;
                break;

            case '016':
                $enrollmentStatus = Base\Enrolled::N;
                break;

            default:
                $enrollmentStatus = Base\Enrolled::U;
                break;
        }

        return $enrollmentStatus;
    }

    protected function decideAuthStepAfterEnroll($gatewayPayment, $input, $response)
    {
        $enrolled = $gatewayPayment->getEnrolled();

        switch ($enrolled)
        {
            case Base\Enrolled::Y;
                $this->persistCardDetailsTemporarily($input);

                return $this->getOtpSubmitRequest($input);

            case Base\Enrolled::N:

                return null;
        }

        $this->handleError($response, $input['payment']['id']);
    }

    protected function sendOtpValidateRequest(array $input, $gatewayPayment)
    {
        $content = $this->getOtpValidateRequestContent($input, $gatewayPayment);

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST');

        $traceRequest['content'] = $content;

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_REQUEST);

        $response =  $this->sendGatewayRequest($request);

        $arrayResponse = $this->jsonToArray($response->body);

        return $arrayResponse;
    }

    protected function getOtpValidateRequestContent(array $input, $gatewayPayment)
    {
        $otpToken =  $this->encrypt($input['gateway']['otp']);

        $gatewayPaymentId = $gatewayPayment->getGatewayPaymentId();

        $hash = [
            Field::CARD_NUMBER     => $input['card']['number'],
            Field::OTP_TOKEN       => $otpToken,
            Field::ACS_TXN_ID      => $gatewayPaymentId,
            Field::SECRET          => $this->getSecret(),
        ];

        $content = [
            Field::VERSION          => Constant::VERSION,
            Field::MERCHANT_TXN_ID  => $input['payment']['id'],
            Field::ACS_TXN_ID       => $gatewayPaymentId,
            Field::OTP_TOKEN        => $otpToken,
            Field::MESSAGE_HASH     => $this->generateHash($hash),
        ];

        return $content;
    }

    protected function sendOtpResendRequest(array $input, $gatewayPayment)
    {
        $content = $this->getOtpResendRequestContent($input, $gatewayPayment);

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST');

        $traceRequest['content'] = $content;

        $this->traceGatewayPaymentRequest($content, $input,TraceCode::GATEWAY_PAYMENT_OTP_RESEND_REQUEST);

        $response =  $this->sendGatewayRequest($request);

        $arrayResponse = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($arrayResponse, $input, TraceCode::GATEWAY_PAYMENT_OTP_RESEND_RESPONSE);

        $this->validateResponseContent($arrayResponse);

        return $arrayResponse;
    }

    protected function getOtpResendRequestContent(array $input, $gatewayPayment)
    {
        $gatewayPaymentId = $gatewayPayment->getGatewayPaymentId();

        $hash = [
            Field::MERCHANT_TXN_ID => $input['payment']['id'],
            Field::ACS_TXN_ID      => $gatewayPaymentId,
            Field::OTP_SENT_COUNT  => $input['payment']['otp_count'],
            Field::SECRET          => $this->getSecret(),
        ];

        $content = [
            Field::VERSION          => Constant::VERSION,
            Field::MERCHANT_TXN_ID  => $input['payment']['id'],
            Field::ACS_TXN_ID       => $gatewayPaymentId,
            Field::OTP_SENT_COUNT   => $input['payment']['otp_count'],
            Field::MESSAGE_HASH     => $this->generateHash($hash),
        ];

        return $content;
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $content = json_encode($content);

        $request = [
            'url'       => $this->getUrl($type),
            'method'    => $method,
            'content'   => $content,
        ];

        $request['headers'] = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return $request;
    }

    protected function traceGatewayPaymentRequest(array $request,
                                                  $input,
                                                  $traceCode = TraceCode::GATEWAY_ENROLL_REQUEST)
    {
        unset($request['content'][Field::CARD_DETAILS]);

        parent::traceGatewayPaymentRequest($request, $input, $traceCode);
    }

    protected function getCallbackResponseAttributes($response)
    {
        $attributes = [
            Base\Entity::CAVV                 => $response[Field::CAVV] ?? null,
            Base\Entity::ECI                  => $response[Field::ECI] ?? null,
            Base\Entity::RESPONSE_CODE        => $response[Field::RESPONSE_CODE] ?? null,
            Base\Entity::RESPONSE_DESCRIPTION => $response[Field::RES_DESC] ?? null,
            Base\Entity::ACC_ID               => $response[Field::ACC_ID] ?? null,
            Base\Entity::STATUS               => $this->getAuthenticationStatus($response[Field::RESPONSE_CODE]),
            Base\Entity::XID                  => $this->generateXid($this->input),
            BASE\Entity::CAVV_ALGORITHM       => $this->getCavvAlgorthm(),
        ];

        return $attributes;
    }

    protected function getAuthenticationStatus($responseCode)
    {
        switch ($responseCode)
        {
            case '000':
                $authenticationStatus = Base\AuthenticationStatus::Y;
                break;

            case '001':
                $authenticationStatus = Base\AuthenticationStatus::F;
                break;

            case '016':
                $authenticationStatus = Base\AuthenticationStatus::N;
                break;

            default:
                $authenticationStatus = Base\AuthenticationStatus::U;
        }

        return $authenticationStatus;
    }

    protected function getCavvAlgorthm()
    {
        $cavvAlgo = null;

        switch ($this->input['card']['network_code'])
        {
            case Card\Network::MC:
                $cavvAlgo= 3;
                break;

            case Card\Network::VISA:
                $cavvAlgo= 2;
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

        return $cavvAlgo;
    }

    protected function validateResponseContent($response)
    {
        if ((isset($response[Field::RESPONSE_CODE]) === true) and
            (in_array($response[Field::RESPONSE_CODE], ['000'], true) === true))
        {
            $content = $this->getCheckSumArray($response);

            $content[Field::SECRET] = $this->getSecret();

            $this->verifySecureHash($content);
        }
    }

    protected function getCheckSumArray($response)
    {
        if ($this->action === Action::OTP_RESEND)
        {
            return [
                Field::MERCHANT_TXN_ID  => $response[Field::MERCHANT_TXN_ID],
                Field::ACS_TXN_ID       => $response[Field::ACS_TXN_ID],
                Field::RESPONSE_CODE    => $response[Field::RESPONSE_CODE],
                Field::MESSAGE_HASH     => $response[Field::MESSAGE_HASH],
            ];
        }

        if ($this->action === Action::OTP_SUBMIT)
        {
            return [
                Field::ACS_TXN_ID       => $response[Field::ACS_TXN_ID],
                Field::ACC_ID           => $response[Field::ACC_ID],
                Field::CAVV             => $response[Field::CAVV],
                Field::ECI              => $response[Field::ECI],
                Field::MESSAGE_HASH     => $response[Field::MESSAGE_HASH],
            ];
        }

        return $this->getMappedAttributes($response);
    }

    protected function handleError($response, $paymentId)
    {
        if (in_array($response[Field::RESPONSE_CODE] , ['000', '016'], true) === false)
        {
            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($response[Field::RESPONSE_CODE]),
                $response[Field::RESPONSE_CODE],
                $response[Field::RES_DESC] ?? ResponseCode::getDescription($response[Field::RESPONSE_CODE]),
                [
                    'payment_id' => $paymentId,
                    'response_code' => $response[Field::RESPONSE_CODE],
                    'response_desc' => $response[Field::RES_DESC] ??
                                            ResponseCode::getDescription($response[Field::RESPONSE_CODE]),
                ],
                null,
                BaseGateway\Action::AUTHENTICATE);
        }
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($str)
    {
        return base64_encode(hex2bin(hash('sha256', $str)));
    }

    protected function encrypt($data)
    {
        $cryptoObject = $this->getCrytoObject();

        $encrypted = $cryptoObject->encryptString($data);

        $encryptedString = base64_encode($encrypted);

        return $encryptedString;
    }

    public function decrypt($data)
    {
        $cryptoObject = $this->getCrytoObject();

        $decryptedString = $cryptoObject->decryptString(base64_decode($data));

        return $decryptedString;
    }

    protected function getCrytoObject()
    {
        $key = $this->getEncryptionKey();

        $aes = new AESCrypto(AES::MODE_CBC, $key);

        return $aes;
    }

    protected function getEncryptionKey()
    {
        $key = $this->config['test_encryption_key'];

        if ($this->mode === Mode::LIVE)
        {
            $key = $this->config['live_encryption_key'];
        }

        return hex2bin($key);
    }

    public function getSecret()
    {
        $secret = $this->config['test_secret_key'];

        if ($this->mode === Mode::LIVE)
        {
            $secret = $this->config['live_secret'];
        }

        return $secret;
    }

    protected function getMerchantId()
    {
        $merchantConfig = $this->getMerchantConfig();

        return $merchantConfig[self::GATEWAY_MERCHANT_ID];
    }

    protected function getMerchantName()
    {
        $merchantConfig = $this->getMerchantConfig();

        return $merchantConfig['gateway_merchant_name'];
    }

    protected function getMerchantConfig()
    {
        if ($this->mode === Mode::LIVE)
        {
            $merchantId = $this->input['merchant']['id'];

            return $this->config[$merchantId] ?? $this->config['live'];
        }

        return $this->config['test'];
    }

    protected function getAcquirerBin(array $input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_acq_bin'];
        }

        switch ($input['card']['network_code'])
        {
            case Card\Network::MC:
                $acqBin = $this->config['live_mastercard_acq_bin'];
                break;

            case Card\Network::VISA:
                $acqBin = $this->config['live_visa_acq_bin'];
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

        return $acqBin;
    }
}
