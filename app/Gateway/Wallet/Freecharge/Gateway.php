<?php

namespace RZP\Gateway\Wallet\Freecharge;

use Cache;
use Carbon\Carbon;
use Config;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Trace\TraceCode;
use View;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const DEFAULT_TXN_CHANNEL = 'WEB';

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

        if ($content[ResponseFields::ERROR_CODE] !== ResponseCode::SUCCESS_CODE)
        {
            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERROR_CODE]),
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }

        // OTP_REDIRECT sends a authCode as query param
        // If it exists, handle it as callback for OTP_REDIRECT
        if (isset($input['gateway'][ResponseFields::AUTH_CODE]) === true)
        {
            return $this->callbackOtpRedirectFlow($input);
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

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input);

        $this->handleRequestFailed($response);

        $code = $content[ResponseFields::STATUS];

        $contentToSave = [
            RequestFields::MERCHANT_ID   => $this->getMerchantId($input['terminal']),
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

    /*
     * Freecharge gives us an otpId and a separate API for resending OTP.
     * If otp count for the payment is greater than zero. We use otpResend instead of otpGenerate
     */
    public function otpResend(array $input)
    {
        parent::otpResend($input);

        $this->domainType = Url::LOGIN;

        $request = $this->getOtpResendRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input);

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

        $this->traceGatewayPaymentRequest($request, $input);

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

        $this->traceGatewayPaymentResponse($content, $input);

        $callbackResponse = $this->getCallbackResponseData($input);

        $callbackResponse = array_merge($callbackResponse, $data);

        return $callbackResponse;
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input);

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
            RequestFields::MERCHANT_ID   => $this->getMerchantId($input['terminal']),
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

        $this->action = Action::DEBIT_WALLET;
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

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input);

        $this->handleRequestFailed($response);

        $this->verifyCheckSumForResponse($content);

        $attributes = $this->getRefundAttributesFromRefundResponse($input, $content);

        $this->createGatewayRefundEntity($attributes);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
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

    protected function getTokenAttributes($content)
    {
        $input = $this->input;

        $expiryTime = $content[ResponseFields::ACCESS_TOKEN_EXPIRY];
        $expiryTime = Carbon::createFromFormat('Y-m-d\TH:i:s', $expiryTime)
                        ->timestamp;

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

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
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
        // If JSON_UNESCAPED_SLASHES not used, wrong checksum will be created due to
        // escaped slashes.
        return json_encode($content, JSON_UNESCAPED_SLASHES) . $this->getSecret();
    }

    protected function getCustomRequestArray($content = [], $method = 'post')
    {
        $content = json_encode($content);

        $request = $this->getStandardRequestArray($content, $method);

        $request['headers'] = [
            'Content-Type' => 'application/json',
        ];

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

        $expectedCheckSum  = $this->getHashOfArray($response);

        if (hash_equals($expectedCheckSum, $checkSum) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
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

        assert($cipherText !== false);

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

        $this->traceGatewayPaymentResponse($content, $input);

        if (isset($content[ResponseFields::WALLET_BALANCE]))
        {
            return (int) ($content[ResponseFields::WALLET_BALANCE] * 100);
        }

        return 0;
    }

    protected function getUserWalletBalanceRequestArray($input)
    {
        $content = [
            RequestFields::ACCESS_TOKEN   => '',
            RequestFields::MERCHANT_ID    => $this->getMerchantId($input['terminal']),
        ];

        $this->traceGatewayPaymentRequest($content, $input);

        $content[RequestFields::ACCESS_TOKEN] = $input['token']['gateway_token'];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content, $method = 'GET');

        $content = http_build_query($content);
        $request['url'] .= '?' . $content;
        $request['content'] = [];

        return $request;
    }

    protected function getDebitRequestArray($input)
    {
        $content = array(
            RequestFields::ACCESS_TOKEN    => '',
            RequestFields::AMOUNT          => (string) ($input['payment']['amount'] / 100),
            RequestFields::CHANNEL         => self::DEFAULT_TXN_CHANNEL,
            RequestFields::CURRENCY        => $input['payment']['currency'],
            RequestFields::MERCHANT_ID     => $this->getMerchantId($input['terminal']),
            RequestFields::MERCHANT_TXN_ID => $input['payment']['public_id'],
        );

        $this->traceGatewayPaymentRequest($content, $input);

        $content[RequestFields::ACCESS_TOKEN] = $input['token']['gateway_token'];

        $content[ResponseFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content);

        return $request;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $content = array(
            RequestFields::EMAIL         => $input['payment']['email'],
            RequestFields::MERCHANT_ID   => $this->getMerchantId($input['terminal']),
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
            RequestFields::MERCHANT_ID => $this->getMerchantId($input['terminal']),
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
            RequestFields::OTP                     => $input['gateway']['otp'],
            RequestFields::USER_MACHINE_IDENTIFIER => $input['payment']['id'],
            RequestFields::MERCHANT_ID             => $this->getMerchantId($input['terminal']),
        );

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
        $content = array(
            // Topup amount is equal to payment amount - we topup how much he has to pay.
            RequestFields::AMOUNT       => (string) ($input['payment']['amount'] / 100),
            RequestFields::CALLBACK_URL => $input['callbackUrl'],
            RequestFields::CHANNEL      => self::DEFAULT_TXN_CHANNEL,
            RequestFields::LOGIN_TOKEN  => '',
            RequestFields::MERCHANT_ID  => $this->getMerchantId($input['terminal']),
            RequestFields::METADATA     => $input['payment']['public_id'],
        );

        $this->trace->info(
            TraceCode::PAYMENT_TOPUP_REQUEST,
            [
                'request'    => $content,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

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
            RequestFields::MERCHANT_ID            => $this->getMerchantId($input['terminal']),
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
            'payment_id'            =>  $input['payment']['id'],
            'action'                =>  $this->action,
            'amount'                =>  $input['refund']['amount'],
            'wallet'                =>  $input['payment']['wallet'],
            'email'                 =>  $input['payment']['email'],
            'received'              =>  true,
            'contact'               =>  $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   =>  $this->getMerchantId($input['terminal']),
            'refund_id'             =>  $input['refund']['id'],
            'status_code'           =>  $response['status'],
            'gateway_refund_id'     =>  $response['refundTxnId'],
        );

        return $refundAttributes;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

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
                  ($payment['status_code'] !== Status::SUCCESS)))
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
                $payment->fill($walletAttributes);

                $payment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = array(
            RequestFields::MERCHANT_ID   => $this->getMerchantId($this->input['terminal']),
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

    protected function getVerifyRequestArray($input)
    {
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = [
            RequestFields::MERCHANT_ID     => $this->getMerchantId($input['terminal']),
            RequestFields::MERCHANT_TXN_ID => $input['payment']['public_id'],
            RequestFields::TXN_ID          => $wallet['gateway_payment_id'],
            RequestFields::TXN_TYPE        => TxnType::CUSTOMER_PAYMENT,
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getCustomRequestArray($content, 'GET');

        $content = http_build_query($content);
        $request['url'] .= '?' . $content;
        $request['content'] = [];

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
            RequestFields::MERCHANT_ID   => $this->getMerchantId($input['terminal']),
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

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        if (isset($content[ResponseFields::ACCESS_TOKEN]))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $content[ResponseFields::ACCESS_TOKEN]  = '';

            $content[ResponseFields::REFRESH_TOKEN] = '';

            $this->traceGatewayPaymentResponse($content, $input);

            return $data;
        }

        $this->traceGatewayPaymentResponse($content, $input);
    }

    protected function getExchangeTokenRequestArray(array $input, $callback)
    {
        $content = [
            RequestFields::AUTH_CODE   => $callback[ResponseFields::AUTH_CODE],
            RequestFields::GRANT_TYPE  => 'AUTHORIZATION_CODE',
            RequestFields::MERCHANT_ID => $this->getMerchantId($input['terminal']),
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
        if ($response->status_code === 500)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
        }
        else if (($response->status_code === 202) or
                 ((isset($content[ResponseFields::ERROR_CODE]) === true) and
                 (isset($content[ResponseFields::ERROR_CODE]) !== ResponseCode::SUCCESS_CODE)))
        {
            $content = $this->jsonToArray($response->body);

            $errorCode = $content[ResponseFields::ERROR_CODE];

            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($errorCode),
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }
    }
}
