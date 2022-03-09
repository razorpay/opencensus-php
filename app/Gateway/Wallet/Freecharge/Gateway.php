<?php

namespace RZP\Gateway\Wallet\Freecharge;

use Config;
use View;
use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Gateway\Billdesk\Fields;
use RZP\Models\Payment\Processor;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment as PaymentModel;
use RZP\Models\Payment\Gateway as PaymentGateway;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const DEFAULT_TXN_CHANNEL = 'WEB';

    const BALANCE_CACHE_KEY = 'gateway:freecharge_balance_';

    const ENCRYPTION_MODE     = 'aes-128-ecb';

    protected $gateway = 'wallet_freecharge';

    protected $sortRequestContent = true;

    protected $canRunOtpFlow = true;

    protected $topup = true;

    protected $map = array(
        RequestFields::EMAIL         => 'email',
        RequestFields::MOBILE_NUMBER => 'contact',
        RequestFields::MERCHANT_ID   => 'gateway_merchant_id',
        RequestFields::TXN_ID        => 'gateway_payment_id',
        RequestFields::REFUND_ID     => 'gateway_refund_id',
        RequestFields::STATUS        => 'status_code',
        RequestFields::AMOUNT        => 'amount',
        RequestFields::MESSAGE       => 'response_description',
        RequestFields::RECEIVED      => 'received',
        RequestFields::OTP_ID        => 'reference1',
        RequestFields::TOPUP         => 'reference2',
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        throw new Exception\LogicException(
            'It is a Power Wallet, It should not go here');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'response'   => $input['gateway'],
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $content = $input['gateway'];

        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::TOPUP_SUCCESS))
        {
            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERROR_CODE]),
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }

        return $this->callbackTopupFlow($input);
    }

    public function otpGenerate(array $input)
    {
        if ((isset($input['otp_resend'])) and ($input['otp_resend'] === true))
        {
            return $this->otpResend($input);
        }

        $this->action($input, Action::OTP_GENERATE);

        $this->domainType = Url::LOGIN;

        $request = $this->getOtpGenerateRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_RESPONSE);

        $this->handleRequestFailed($response);

        $code = $content[ResponseFields::STATUS];

        $contentToSave = [
            RequestFields::MERCHANT_ID   => $this->getMerchantId1($input['terminal']),
            RequestFields::EMAIL         => $input['payment']['email'],
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::AMOUNT        => $input['payment']['amount'],
        ];

        if ($code === Status::OTP_SENT)
        {
            $contentToSave['otpId'] = $content[ResponseFields::OTP_ID];
        }

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        if ($code === Status::OTP_REDIRECT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST);
        }

        return $this->getOtpSubmitRequest($input);
    }

    /**
     * @param array $input
     *
     * Freecharge gives us an otpId and a separate API for resending OTP.
     * If otp count for the payment is greater than zero. We use otpResend instead of otpGenerate
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function otpResend(array $input)
    {
        parent::otpResend($input);

        $this->domainType = Url::LOGIN;

        $request = $this->getOtpResendRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_RESPONSE);

        $this->handleRequestFailed($response);

        $otpId = $content[ResponseFields::OTP_ID];

        // Payment Gateway Entity for Authorize action
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, ['otpId' => $otpId]);

        return $this->getOtpSubmitRequest($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->domainType = Url::LOGIN;

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $data = [];

        if (isset($content[ResponseFields::ACCESS_TOKEN]) === true)
        {
            $data['token'] = $this->getTokenAttributes($content);

            $content[ResponseFields::ACCESS_TOKEN]  = '';

            $content[ResponseFields::REFRESH_TOKEN] = '';
        }

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_RESPONSE);

        $callbackResponse = $this->getCallbackResponseData($input);

        $callbackResponse = array_merge($callbackResponse, $data);

        return $callbackResponse;
    }

    public function debit(array $input)
    {
        if (isset($input['gateway'][ResponseFields::TXN_ID]) === true)
        {
            $contentToSave = array(
                RequestFields::MERCHANT_ID   => $this->getMerchantId1($input['terminal']),
                RequestFields::EMAIL         => $input['payment']['email'],
                RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
                RequestFields::STATUS        => $input['gateway'][ResponseFields::STATUS],
                RequestFields::AMOUNT        => $input['payment']['amount'],
                RequestFields::TXN_ID        => $input['gateway'][ResponseFields::TXN_ID],
                RequestFields::RECEIVED      => true
            );

            $wallet = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], Action::AUTHORIZE);

            $this->updateGatewayPaymentEntity($wallet, $contentToSave);

            return;
        }

        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_DEBIT_RESPONSE);

        $this->handleRequestFailed($response);

        $this->verifyCheckSumForResponse($content);

        if ($content[ResponseFields::STATUS] === Status::DEBIT_FAILED)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }

        // Maintain consistency in status_code value for successful debit/topup/verify
        // Replace completed with 'success'
        if ($content[ResponseFields::STATUS] === Status::DEBIT_SUCCESS)
        {
            $content[ResponseFields::STATUS] = Status::TRANSACTION_SUCCESS;
        }

        $contentToSave = array(
            RequestFields::MERCHANT_ID   => $this->getMerchantId1($input['terminal']),
            RequestFields::EMAIL         => $input['payment']['email'],
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::STATUS        => $content[ResponseFields::STATUS],
            RequestFields::AMOUNT        => $input['payment']['amount'],
            RequestFields::TXN_ID        => $content[ResponseFields::TXN_ID],
            RequestFields::RECEIVED      => true
        );

        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    public function topup($input)
    {
        $this->action($input, Action::TOPUP_REDIRECT);

        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE);

        // Set reference2 that a topup occurred during a transaction
        $this->updateGatewayPaymentEntity(
            $wallet,
            [RequestFields::TOPUP => 'true']);

        return $this->getTopupWalletRedirectRequestArray($input);
    }

    public function refund(array $input, bool $retry = false)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $content = [];

        try
        {
            $response = $this->sendGatewayRequest($request);

            $content = $this->jsonToArray($response->body);

            $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

            $this->handleRequestFailed($response);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                null,
                null,
                [
                    PaymentModel\Gateway::GATEWAY_RESPONSE   => json_encode($content),
                    PaymentModel\Gateway::GATEWAY_KEYS       => $this->getGatewayData($content)
                ]);
        }

        $this->verifyCheckSumForResponse($content);

        $attributes = $this->getRefundAttributesFromRefundResponse($input, $content);

        //
        // This is required when we are marking the refund as successful from initiated.
        // But, if the refund had initially failed, the verify refund would return
        // back failed, in which case, we would RE-INITIATE the refund. When we re-initiate,
        // we don't create a new refund entity, but update the existing refund
        // entity (which has status = initiated).
        //
        $wallet = $this->repo->findByRefundId($input['refund']['id']);

        if ($wallet !== null)
        {
            $this->updateGatewayRefundEntity($wallet, $attributes, false);
        }
        else
        {
            $this->createGatewayRefundEntity($attributes);
        }

        $errorCode = $content[Fields::ERROR_CODE] ?? null;

        $errorReason = $content[Fields::ERROR_REASON] ?? null;

        if ((isset($content[ResponseFields::STATUS]) === false) or ($content[ResponseFields::STATUS] !== Status::TRANSACTION_SUCCESS))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                $errorCode,
                $errorReason,
                [
                    PaymentModel\Gateway::GATEWAY_RESPONSE   => json_encode($content),
                    PaymentModel\Gateway::GATEWAY_KEYS       => $this->getGatewayData($content)
                ]);
        }

        return [
            PaymentModel\Gateway::GATEWAY_RESPONSE => json_encode($content),
            PaymentModel\Gateway::GATEWAY_KEYS     => $this->getGatewayData($content)
        ];
    }

   protected function getGatewayData(array $refundFields =[])
    {
        if (empty($refundFields) === false)
        {
            return [
                ResponseFields::STATUS           => $refundFields[ResponseFields::STATUS] ?? null,
                ResponseFields::REFUND_TXN_ID    => $refundFields[ResponseFields::REFUND_TXN_ID] ?? null,
                ResponseFields::ERROR_CODE       => $refundFields[ResponseFields::ERROR_CODE ] ?? null,
                ResponseFields::ERROR_MESSAGE    => $refundFields[ResponseFields::ERROR_MESSAGE ] ?? null,
            ];
        }

        return [];
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $gatewayRefundEntities = $this->repo->findSuccessfulRefundByRefundId($refundId, Processor\Wallet::FREECHARGE);

        if ($gatewayRefundEntities->count() === 0)
        {
            return false;
        }

        $gatewayRefundEntity = $gatewayRefundEntities->first();

        $gatewayRefundEntityPaymentId = $gatewayRefundEntity->getPaymentId();
        $gatewayRefundEntityRefundAmount = $gatewayRefundEntity->getAmount();
        $gatewayRefundEntityStatusCode = $gatewayRefundEntity->getStatusCode();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input'                 => $input,
                'refund_payment_id'     => $gatewayRefundEntityPaymentId,
                'gateway_refund_amount' => $gatewayRefundEntityRefundAmount,
                'status_code'           => $gatewayRefundEntityStatusCode,
            ]);

        $gatewayRefundSuccess = (($gatewayRefundEntityStatusCode === Status::TRANSACTION_INITIATED) or
                                    ($gatewayRefundEntityStatusCode === Status::TRANSACTION_SUCCESS));

        if (($gatewayRefundEntityPaymentId !== $paymentId) or
            ($gatewayRefundEntityRefundAmount !== $refundAmount) or
            ($gatewayRefundSuccess === false))
        {
            return false;
        }

        return true;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyRefund(array $input)
    {
        $scroogeResponse = new GatewayBase\ScroogeResponse();

        list($refunded, $verifyResponse) = $this->verifyIfRefunded($input);

        if ($refunded === true)
        {
            $wallet = $this->repo->findByRefundId($input['refund']['id']);

            if ($wallet !== null)
            {
                $this->validateRefundOnSuccess($wallet);
            }

            return $scroogeResponse->setSuccess(true)
                                   ->setGatewayVerifyResponse($verifyResponse)
                                   ->toArray();
        }
        else
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setGatewayVerifyResponse($verifyResponse)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING)
                                   ->toArray();
        }

    }

    public function checkBalance(array $input)
    {
        $userBalance = $this->getUserWalletBalance($input);

        if ($input['payment']['amount'] > $userBalance)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);
        }
    }

    public function createRefundRecord(array $input)
    {
        $refundId = $input['refund']['id'];

        $gatewayRefundEntity = $this->repo->findByRefundId($refundId);

        $applicable = false;
        $success = null;

        if ($gatewayRefundEntity === null)
        {
            $applicable = true;

            list($refunded, $response) = $this->verifyIfRefunded($input);

            if ($refunded === true)
            {
                $success = true;

                $this->createMissingGatewayRefundEntity($response, $input);
            }
            else
            {
                //
                // The refund of the payment has been initiated but not
                // processed, The refund is then neither successful nor
                // failed. This refund should be handled in next cron
                //
                if ((isset($response[ResponseFields::STATUS]) === true) and
                    ($response[ResponseFields::STATUS] === Status::TRANSACTION_INITIATED))
                {
                    $success = false;
                }
                else
                {
                    $success = $this->callRefundForMissingGatewayRefundEntity($response, $input);
                }
            }
        }

        return [
            'applicable'    => $applicable,
            'success'       => $success,
            'refund_id'     => $refundId,
            'payment_id'    => $input['payment']['id'],
        ];
    }

    protected function callRefundForMissingGatewayRefundEntity(array $response, array $input)
    {
        $refundId = $input['refund']['id'];
        $paymentId = $input['payment']['id'];

        $this->trace->error(
            TraceCode::GATEWAY_ABSENT_REFUND_FAILED,
            [
                'refund_id'         => $refundId,
                'payment_id'        => $paymentId,
                'verify_response'   => $response,
            ]);

        //
        // It should have been refunded on the gateway side also. But, verify returned
        // false in the verify response for refund.
        // Hence, going to try and refund this now.
        //
        try
        {
            $this->refund($input, true);

            $success = true;
        }
        catch (Exception\BaseException $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::GATEWAY_ABSENT_REFUND_RETRY_FAILED,
                [
                    'refund_id'         => $refundId,
                    'payment_id'        => $paymentId,
                    'verify_response'   => $response,
                ]);

            $success = false;
        }

        return $success;
    }

    protected function createMissingGatewayRefundEntity(array $response, array $input)
    {
        $refundId = $input['refund']['id'];
        $paymentId = $input['payment']['id'];

        $response[ResponseFields::REFUND_TXN_ID] = $response[ResponseFields::TXN_ID];

        $attributes = $this->getRefundAttributesFromRefundResponse($input, $response);

        // We did not receive the refund response on first attempt
        $attributes['received'] = false;

        $this->createGatewayRefundEntity($attributes, Action::REFUND);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RECORD_CREATED,
            [
                'payment_id' => $paymentId,
                'refund_id'  => $refundId,
            ]);
    }

    protected function isStatusUnknown($ex)
    {
        $errorCode = null;

        $error = $ex->getError()->toArray();

        if (isset($error[Error\Error::GATEWAY_ERROR_CODE]) === true)
        {
            $errorCode = $error[Error\Error::GATEWAY_ERROR_CODE];
        }

        // Handle the unknown error (fatal errors) and mark it as skip refund
        // Verify it later
        return (ResponseCode::isStatusUnknownError($errorCode) === true);
    }

    protected function verifyIfRefunded(array $input)
    {
        $this->action($input, Action::VERIFY);

        $request = $this->getRefundVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'content'    => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        // If transaction is not found, treat it as refund failed.
        if ((isset($response[ResponseFields::ERROR_CODE]) === true) and
            (ResponseCode::isTransactionAbsent($response[ResponseFields::ERROR_CODE]) === true))
        {
            return [false, []];
        }

        $this->verifyCheckSumForResponse($response);

        $refunded = ($response[ResponseFields::STATUS] === Status::TRANSACTION_SUCCESS);

        return [$refunded, $response];
    }

    protected function getTokenAttributes($content)
    {
        $input = $this->input;

        $expiryTime = $content[ResponseFields::ACCESS_TOKEN_EXPIRY];
        $expiryTime = Carbon::createFromFormat('Y-m-d\TH:i:s', $expiryTime)
                            ->getTimestamp();

        $attributes = array(
            Token\Entity::METHOD           => 'wallet',
            Token\Entity::WALLET           => $input['payment']['wallet'],
            Token\Entity::TERMINAL_ID      => $input['terminal']['id'],
            Token\Entity::GATEWAY_TOKEN    => $content[ResponseFields::ACCESS_TOKEN],
            Token\Entity::GATEWAY_TOKEN2   => $content[ResponseFields::REFRESH_TOKEN],
            Token\Entity::EXPIRED_AT       => $expiryTime,
        );

        return $attributes;
    }

    protected function getMerchantId1($terminal)
    {
        $id = $this->getDealerId($terminal);

        if (empty($id) === false)
        {
            return $id;
        }

        return $this->getMerchantId($terminal);
    }

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getDealerId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_dealer_id'];
        }

        return $terminal['gateway_merchant_id2'];
    }

    protected function getUrlDomain()
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainConstantName = strtoupper($this->mode).'_DOMAIN';

        if ($this->domainType !== null)
        {
            $domainType = strtoupper($this->domainType);

            $mode = strtoupper($this->mode);

            $domainConstantName = "{$mode}_{$domainType}_DOMAIN";
        }

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getStringToHash($content, $glue = '')
    {
        // If JSON_UNESCAPED_SLASHES not used, wrong checksum
        // will be created due to escaped slashes.
        return json_encode($content, JSON_UNESCAPED_SLASHES) . $this->getSecret();
    }

    protected function getCustomRequestArray($content = [], $method = 'post')
    {
        $encodedContent = json_encode($content);

        $request = $this->getStandardRequestArray($encodedContent, $method);

        $request['headers'] = [
            'Content-Type' => 'application/json',
        ];

        if (strtolower($method) === 'get')
        {
            $content = http_build_query($content);
            $request['url'] .= '?' . $content;
            $request['content'] = [];
        }

        return $request;
    }

    protected function getHashOfString($str)
    {
        return hash(HashAlgo::SHA256, $str);
    }

    protected function verifyCheckSumForResponse($response)
    {
        $checkSum = $response[ResponseFields::CHECKSUM];

        unset($response[ResponseFields::CHECKSUM]);

        $expectedCheckSum = $this->getHashOfArray($response);

        if (hash_equals($expectedCheckSum, $checkSum) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification',
                null,
                [
                    PaymentGateway::GATEWAY_RESPONSE  => json_encode($response),
                    PaymentGateway::GATEWAY_KEYS      => $this->getGatewayData($response)
                ]
            );
        }
    }

    protected function getHashOfArray($content)
    {
        foreach ($content as $key => $value)
        {
            if (empty($value) === true)
            {
                unset($content[$key]);
            }
        }

        return parent::getHashOfArray($content);
    }

    /**
     * Creates a login token for freecharge topup
     * 1. Encrypt accessToken with first 16 chars of secretKey
     * 2. Convert to hex format and return it
     *
     * @return string
     */
    protected function generateLoginToken($accessToken)
    {
        $secret = $this->getSecret();

        $key = substr($secret, 0, 16);

        // Encrypt accesstoken using AES 128 bit, ECB, PKCS7 padding
        $cipherText = openssl_encrypt(
            $accessToken, self::ENCRYPTION_MODE, $key, OPENSSL_RAW_DATA);

        assert($cipherText !== false); // nosemgrep : assert-fix-false-positives

        return bin2hex($cipherText);
    }

    protected function getUserWalletBalance($input)
    {
        $this->action($input, Action::GET_BALANCE);

        $this->domainType = null;

        $request = $this->getUserWalletBalanceRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_CHECK_BALANCE_RESPONSE);

        if ((isset($input['isAutoDebitFlow']) === true) and
            ($input['isAutoDebitFlow'] === true))
        {
            $contentToSave = [
                RequestFields::MERCHANT_ID   => $this->getMerchantId1($input['terminal']),
                RequestFields::EMAIL         => $input['payment']['email'],
                RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
                RequestFields::AMOUNT        => $input['payment']['amount'],
            ];

            $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);
        }

        if (isset($content[ResponseFields::WALLET_BALANCE]))
        {
            $key = $this->getBalanceKeyForCache($input['payment']);

            $walletBalance = (int) ($content[ResponseFields::WALLET_BALANCE] * 100);

            // Multiplying by 60 since cache put() expect ttl in seconds
            $this->app['cache']->put($key, $walletBalance, self::PAYMENT_TTL * 60);

            return $walletBalance;
        }

        return 0;
    }

    protected function getUserWalletBalanceRequestArray($input)
    {
        $content = [
            RequestFields::ACCESS_TOKEN   => '',
            RequestFields::MERCHANT_ID    => $this->getMerchantId1($input['terminal']),
        ];

        $this->traceGatewayPaymentRequest($content, $input, TraceCode::GATEWAY_CHECK_BALANCE_REQUEST);

        $content[RequestFields::ACCESS_TOKEN] = $input['token']['gateway_token'];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content, $method = 'get');

        return $request;
    }

    protected function getDebitRequestArray($input)
    {
        $merchantId = $this->getDealerId($input['terminal']);
        $dealerId = null;

        // Read Mpesa code on why I used to this approach.
        // TODO This is wrong, we have to find a better solution.
        if (empty($merchantId) === true)
        {
            $merchantId = $this->getMerchantId($input['terminal']);
        }
        else
        {
            $dealerId = $this->getMerchantId($input['terminal']);
        }

        $content = [
            RequestFields::ACCESS_TOKEN    => '',
            RequestFields::AMOUNT          => (string) ($input['payment']['amount'] / 100),
            RequestFields::CHANNEL         => self::DEFAULT_TXN_CHANNEL,
            RequestFields::CURRENCY        => $input['payment']['currency'],
            RequestFields::MERCHANT_ID     => $merchantId,
            RequestFields::MERCHANT_TXN_ID => $input['payment']['public_id'],
        ];

        if (empty($dealerId) === false)
        {
            $content[RequestFields::DEALER_ID] = $dealerId;
        }

        $this->traceGatewayPaymentRequest($content, $input, TraceCode::GATEWAY_PAYMENT_DEBIT_REQUEST);

        $content[RequestFields::ACCESS_TOKEN] = $input['token']['gateway_token'];

        $content[ResponseFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content);

        return $request;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $content = array(
            RequestFields::EMAIL         => $input['payment']['email'],
            RequestFields::MERCHANT_ID   => $this->getMerchantId1($input['terminal']),
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
        );

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content);

        return $request;
    }

    protected function getOtpResendRequestArray($input)
    {
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = array(
            RequestFields::CHANNEL     => OtpChannel::SMS,
            RequestFields::MERCHANT_ID => $this->getMerchantId1($input['terminal']),
            RequestFields::OTP_ID      => $wallet['reference1'],
        );

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = array(
            RequestFields::OTP_ID                  => $wallet['reference1'],
            RequestFields::OTP                     => '',
            RequestFields::USER_MACHINE_IDENTIFIER => $input['payment']['id'],
            RequestFields::MERCHANT_ID             => $this->getMerchantId1($input['terminal']),
        );

        $this->traceGatewayPaymentRequest($content, $input, TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_REQUEST);

        $content[RequestFields::OTP] = $input['gateway']['otp'];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content);

        return $request;
    }

    protected function getValidWalletToken($input)
    {
        $token = (New Token\Repository)->getByWalletTerminalAndCustomerId(
            $input['payment']['wallet'],
            $input['terminal']['id'],
            $input['customer']['id']);

        if (($token !== null) and ($token->getExpiredAt() > time()))
        {
            return $token;
        }
    }

    protected function getTopupWalletRedirectRequestArray($input)
    {
        $key = $this->getBalanceKeyForCache($input['payment']);

        // Wallet Balance is in paise
        $walletBalance = $this->app['cache']->get($key, 0);

        $topupAmount = ($input['payment']['amount']) / 100;

        $content = array(
            // Topup amount is equal to payment amount - we topup how much he has to pay.
            RequestFields::AMOUNT          => (string) $topupAmount,
            RequestFields::SURL            => $input['callbackUrl'],
            RequestFields::FURL            => $input['callbackUrl'],
            RequestFields::CHANNEL         => self::DEFAULT_TXN_CHANNEL,
            RequestFields::LOGIN_TOKEN     => '',
            RequestFields::MERCHANT_ID     => $this->getMerchantId1($input['terminal']),
            RequestFields::METADATA        => $input['payment']['public_id'],
            RequestFields::MERCHANT_TXN_ID => $input['payment']['public_id'],
        );

        $this->traceGatewayPaymentRequest($content, $input, TraceCode::GATEWAY_PAYMENT_TOPUP_REQUEST);

        $content[RequestFields::LOGIN_TOKEN] = $this->generateLoginToken($input['token']['gateway_token']);

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return $request;
    }

    protected function getRefundRequestArray($input)
    {
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $content = [
            RequestFields::MERCHANT_ID            => $this->getMerchantId1($input['terminal']),
            RequestFields::MERCHANT_TXN_ID        => $input['payment']['public_id'],
            RequestFields::REFUND_AMOUNT          => (string) ($input['refund']['amount'] / 100),
            RequestFields::REFUND_MERCHANT_TXN_ID => $input['refund']['id'],
            RequestFields::TXN_ID                 => $wallet['gateway_payment_id'],
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content);

        return $request;
    }

    protected function getRefundAttributesFromRefundResponse($input, $response)
    {
        $refundAttributes = array(
            WalletEntity::PAYMENT_ID          => $input['payment']['id'],
            WalletEntity::ACTION              => $this->action,
            WalletEntity::AMOUNT              => $input['refund']['amount'],
            WalletEntity::WALLET              => $input['payment']['wallet'],
            WalletEntity::EMAIL               => $input['payment']['email'],
            WalletEntity::RECEIVED            => true,
            WalletEntity::CONTACT             => $this->getFormattedContact($input['payment']['contact']),
            WalletEntity::GATEWAY_MERCHANT_ID => $this->getMerchantId1($input['terminal']),
            WalletEntity::REFUND_ID           => $input['refund']['id'],
            WalletEntity::STATUS_CODE         => $response['status'],
            WalletEntity::GATEWAY_REFUND_ID   => $response['refundTxnId'],
        );

        return $refundAttributes;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        if ((isset($content[ResponseFields::STATUS]) === true) and
            ($content[ResponseFields::STATUS] === Status::TRANSACTION_SUCCESS))
        {
            $this->verifyCheckSumForResponse($content);
        }

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        // Gateway marked payment as a failure
        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::TRANSACTION_SUCCESS))
        {
            $this->verifyStatusOnGatewayFailure($verify, $payment, $input);
        }
        else if ($content[ResponseFields::STATUS] === Status::TRANSACTION_SUCCESS)
        {
            $this->verifyStatusOnGatewaySuccess($verify, $payment, $input);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    protected function verifyStatusOnGatewayFailure($verify, $payment, $input)
    {
        $verify->gatewaySuccess = false;

        // Gateway payment is not created and payment status is not marked
        // as authorized.
        if (($payment === null) or
            ($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }
        else if (($payment['received'] === false) and
                 (($payment['status_code'] === null) or
                  ($payment['status_code'] !== Status::TRANSACTION_SUCCESS)))
        {
            $verify->apiSuccess = false;
        }
        // Gateway declared it as false but we marked it as true.
        else if ($payment['status_code'] === Status::TRANSACTION_SUCCESS)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyStatusOnGatewaySuccess($verify, $payment, $input)
    {
        $verify->gatewaySuccess = true;

        if (($input['payment']['status'] !== 'created') and
            ($input['payment']['status'] !== 'failed') and
            ($payment['received'] === true))
        {
            $verify->apiSuccess = true;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
        }
    }

    protected function saveVerifyContentIfNeeded($payment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if ((isset($content[ResponseFields::STATUS])) and
            ($content[ResponseFields::STATUS] === Status::TRANSACTION_SUCCESS))
        {
            $walletAttributes = $this->getWalletContentFromVerify($payment, $content);

            if ($payment === null)
            {
                $payment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if ($payment['received'] === false)
            {
                $attr = $this->getMappedAttributes($walletAttributes);

                $payment->fill($attr);

                $payment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = array(
            RequestFields::MERCHANT_ID   => $this->getMerchantId1($this->input['terminal']),
            RequestFields::EMAIL         => $this->input['payment']['email'],
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($this->input['payment']['contact']),
            RequestFields::STATUS        => Status::TRANSACTION_SUCCESS,
            RequestFields::TXN_ID        => $content[ResponseFields::TXN_ID],
            'received'                   => true
        );

        if (isset($payment['amount']) === false)
        {
            $contentToSave['amount'] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

    protected function getRefundVerifyRequestArray(array $input)
    {
        $content = [
            RequestFields::MERCHANT_ID     => $this->getMerchantId1($input['terminal']),
            RequestFields::MERCHANT_TXN_ID => $input['refund']['id'],
            RequestFields::TXN_TYPE        => TxnType::CANCELLATION_REFUND,
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content, 'get');

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        return $request;
    }

    protected function getVerifyRequestArray($input)
    {
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = [
            RequestFields::MERCHANT_ID     => $this->getMerchantId1($input['terminal']),
            RequestFields::MERCHANT_TXN_ID => $input['payment']['public_id'],
            RequestFields::TXN_ID          => $wallet['gateway_payment_id'],
            RequestFields::TXN_TYPE        => TxnType::CUSTOMER_PAYMENT,
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content, 'get');

        return $request;
    }

    /**
     * Not used since otp register flow is presently disabled.
     * TODO Once freecharge gives us a simpler flow, integrate it
     */
    protected function getOtpRedirectRequestArray(array $input)
    {
        $this->action = Action::OTP_REDIRECT;

        $this->domainType = Url::LOGIN;

        $content = [
            RequestFields::CALLBACK_URL  => $input['callbackUrl'],
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::MERCHANT_ID   => $this->getMerchantId1($input['terminal']),
        ];

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    /*
     * Freecharge has finished with the topup checkout.
     * It sends a response with updated wallet balance..
     * Break the flow if the topup is a failure
     */
    protected function callbackTopupFlow($input)
    {
        $content = $input['gateway'];

        if ((isset($content[ResponseFields::STATUS]) === true) and
            ($content[ResponseFields::STATUS] === Status::TOPUP_SUCCESS))
        {
            $this->verifyCheckSumForResponse($content);
        }

        return [];
    }

    /*
     * User has successfully registered on freecharge,
     * Generate AccessToken for the user.
     */
    protected function callbackOtpRedirectFlow($input)
    {
        $callback = $input['gateway'];

        $this->action = Action::EXCHANGE_TOKEN;

        $this->domainType = Url::LOGIN;

        $request = $this->getExchangeTokenRequestArray($input, $callback);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_EXCHANGE_TOKEN_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        if (isset($content[ResponseFields::ACCESS_TOKEN]))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $content[ResponseFields::ACCESS_TOKEN]  = '';

            $content[ResponseFields::REFRESH_TOKEN] = '';

            $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_EXCHANGE_TOKEN_RESPONSE);

            return $data;
        }

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_EXCHANGE_TOKEN_RESPONSE);
    }

    protected function getExchangeTokenRequestArray(array $input, $callback)
    {
        $content = [
            RequestFields::AUTH_CODE   => $callback[ResponseFields::AUTH_CODE],
            RequestFields::GRANT_TYPE  => 'AUTHORIZATION_CODE',
            RequestFields::MERCHANT_ID => $this->getMerchantId1($input['terminal']),
        ];

        $content[ResponseFields::CHECKSUM] = $this->getHashOfArray($content);

        return $this->getCustomRequestArray($content);
    }

    /*
     * When API call to freecharge returns an error, Throw a gateway error Exception
     * exception.
     */
    protected function handleRequestFailed($response)
    {
        $content = $this->jsonToArray($response->body);

        if (($response->status_code === 202 ) or ((isset($content[ResponseFields::ERROR_CODE]) === true) and
            ($content[ResponseFields::ERROR_CODE] !== ResponseCode::SUCCESS_CODE)))
        {

            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERROR_CODE]),
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE],
                [
                    PaymentGateway::GATEWAY_RESPONSE  => json_encode($content),
                    PaymentGateway::GATEWAY_KEYS      => $this->getGatewayData($content)
                ]);
        }
    }

    protected function getBalanceKeyForCache($payment)
    {
        return self::BALANCE_CACHE_KEY . $payment['id'];
    }

    protected function validateRefundOnSuccess(WalletEntity $wallet)
    {
        // updateGatewayPaymentEntity takes mapped attributes
        $refundAttr = [
            RequestFields::STATUS => Status::TRANSACTION_SUCCESS,
        ];

        $this->updateGatewayRefundEntity($wallet, $refundAttr);

        // return success as true
        return true;
    }

}
