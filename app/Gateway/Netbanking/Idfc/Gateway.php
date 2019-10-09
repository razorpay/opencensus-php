<?php

namespace RZP\Gateway\Netbanking\Idfc;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const CHECKSUM_ATTRIBUTE = Fields::CHECKSUM;

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    protected $gateway              = 'netbanking_idfc';
    protected $bank                 = 'idfc';
    protected $sortRequestContent   = false;

    protected $map = [
        Fields::MERCHANT_ID             => NetbankingEntity::MERCHANT_ID,
        Fields::PAYMENT_ID              => NetbankingEntity::PAYMENT_ID,
        Fields::AMOUNT                  => NetbankingEntity::AMOUNT,
        Fields::MERCHANT_CODE           => NetbankingEntity::MERCHANT_CODE,
        Fields::BANK_REFERENCE_NUMBER   => NetbankingEntity::BANK_PAYMENT_ID,
        Fields::RESPONSE_CODE           => NetbankingEntity::STATUS
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[Fields::PAYMENT_ID]
        );

        $this->assertAmount(
            $this->formatAmount($input['payment']['amount'] / 100),
            $content[Fields::AMOUNT]
        );

        if (isset($content[Fields::ACCOUNT_NUMBER]) === false)
        {
            $content = array_merge(array_slice($content, 0, 4, true),
                                   [Fields::ACCOUNT_NUMBER => ''],
                                   array_slice($content, 4, 6, true));
        }

        $this->verifySecureHash($content);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $content[Fields::PAYMENT_ID],
            Action::AUTHORIZE);

        $this->saveCallbackResponse($content, $gatewayEntity);

        $this->checkCallbackStatus($content);

        $this->verifyCallback($gatewayEntity, $input);

        $acquirerData = $this->getAcquirerData($input, $gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback($gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function getAuthorizeRequestData($input)
    {
        $data = [
            Fields::MERCHANT_ID             => $this->getMerchantId(),
            Fields::PAYMENT_ID              => $input['payment'][Payment\Entity::ID],
            Fields::AMOUNT                  => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT] / 100),
            Fields::RETURN_URL              => $input['callbackUrl'],
            Fields::TRANSACTION_TYPE        => TransactionDetails::TYPE_PAYMENT,
            Fields::ACCOUNT_NUMBER          => '',
            Fields::PAYMENT_DESCRIPTION     => $this->getNarration($input),
            Fields::CHANNEL                 => RequestChannel::WEB,
            Fields::MERCHANT_CODE           => $input['terminal']['category'] ?: '3020',
            Fields::TRANSACTION_CURRENCY    => $input['payment']['currency'],
        ];

        // Change Content for Merchants with TPV Required
        if ($input['merchant']->isTPVRequired())
        {
            $data[Fields::ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $checksum = $this->generateHash($data);

        $data[Fields::CHECKSUM] = $checksum;

        // Account number is sent only in case if merchant has tpv enabled
        if ($input['merchant']->isTPVRequired() === false)
        {
            unset($data[Fields::ACCOUNT_NUMBER]);
        }

        return $data;
    }

    public function getNarration($input)
    {
        return $input['payment']['description'] ?: $input['payment']['id'];
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $content = json_encode($content);

        $request = $this->getStandardIdfcRequestArray($content);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = json_decode($response->body, true);

        $expectedAmount = number_format($verify->input['payment']['amount'] / 100, 2, '.', '');

        $this->assertAmount($expectedAmount, $verify->verifyResponseContent[Fields::AMOUNT]);

        $checksumContent = $this->getContentArrayForChecksumCalculation($verify->verifyResponseContent);

        $this->verifySecureHash($checksumContent);
    }

    public function getVerifyRequestData($verify)
    {
        $data = [
            Fields::MERCHANT_ID             => $this->getMerchantId(),
            Fields::PAYMENT_ID              => $verify->input['payment'][Payment\Entity::ID],
            Fields::AMOUNT                  => $this->formatAmount(
                                                $verify->input['payment'][Payment\Entity::AMOUNT] / 100
                                                    ),
            Fields::ACCOUNT_NUMBER          => '',
            Fields::TRANSACTION_TYPE        => TransactionDetails::TYPE_VERIFICATION,
            Fields::BANK_REFERENCE_NUMBER   => $verify->payment['bank_payment_id'] ?: '',
            Fields::MERCHANT_CODE           => $verify->input['terminal']['category'] ?: '3020',
        ];

        $checksum = $this->generateHash($data);

        $data[Fields::CHECKSUM] = $checksum;

        unset($data[Fields::ACCOUNT_NUMBER]);

        return $data;
    }

    protected function verifyPayment(Verify $verify)
    {
        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyResponse($verify);
    }

    protected function saveVerifyResponse(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $verify->verifyResponseContent = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($verify->verifyResponseContent);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[Fields::RESPONSE_CODE];
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus()
    {
        return StatusCode::SUCCESS_CODE;
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[Fields::RESPONSE_CODE]) === true) and
            ($content[Fields::RESPONSE_CODE] === StatusCode::SUCCESS_CODE))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function checkCallbackStatus(array $content)
    {
        if ($content[Fields::RESPONSE_CODE] !== StatusCode::SUCCESS_CODE)
        {
            throw new Exception\GatewayErrorException(
                StatusCode::getInternalErrorCode($content[Fields::RESPONSE_CODE]));
        }
    }

    protected function saveCallbackResponse(array $content, Base\Entity $gatewayEntity)
    {
        $attrs = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[Fields::RESPONSE_CODE],
            Base\Entity::BANK_PAYMENT_ID => $content[Fields::BANK_REFERENCE_NUMBER],
        ];

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);
    }

    protected function getContentArrayForChecksumCalculation($input)
    {
        $content[Fields::MERCHANT_ID]           = $input[Fields::MERCHANT_ID];
        $content[Fields::PAYMENT_ID]            = $input[Fields::PAYMENT_ID];
        $content[Fields::AMOUNT]                = $input[Fields::AMOUNT];
        $content[Fields::ACCOUNT_NUMBER]        = '';
        $content[Fields::TRANSACTION_TYPE]      = $input[Fields::TRANSACTION_TYPE];
        $content[Fields::BANK_REFERENCE_NUMBER] = $input[Fields::BANK_REFERENCE_NUMBER];
        $content[Fields::STATUS_RESULT]         = $input[Fields::STATUS_RESULT];
        $content[Fields::RESPONSE_CODE]         = $input[Fields::RESPONSE_CODE];
        $content[Fields::RESPONSE_MESSAGE]      = $input[Fields::RESPONSE_MESSAGE];
        $content[Fields::CHECKSUM]              = $input[Fields::CHECKSUM];

        return $content;
    }

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    public function getLiveMerchantId()
    {
        return $this->config['live_merchant_id'];
    }

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($str)
    {
        $secret = base64_encode($this->getSecret());

        return strtoupper(hash_hmac('sha512', $str, $secret, false));
    }

    public function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected function getStandardIdfcRequestArray($content)
    {
        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'payment_id' => $this->input['payment']['id'],
                'gateway' => $this->gateway,
            ]);

        if ($this->mock === true)
        {
            return $request;
        }

        $request['options']['verify'] = $this->getClientCertificate();

        $request['headers']['Content-Type'] = 'application/json';

        return $request;
    }

    protected function getUrlDomain()
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainType = $this->mode;

        $domainConstantName = strtoupper($domainType).'_'.strtoupper($this->action).'_DOMAIN';

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
            $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            $clientCertFile = fopen($clientCertPath, 'w');

            $encodedCert = $this->config['live_client_certificate'];

            if ($this->mode === Mode::TEST)
            {
                $encodedCert = $this->config['test_client_certificate'];
            }

            $key = base64_decode($encodedCert);

            fwrite($clientCertFile, $key);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    protected function getClientCertificateName()
    {
        $certName = $this->config['client_certificate'];

        return $certName;
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }
}
