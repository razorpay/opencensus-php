<?php

namespace RZP\Gateway\Wallet\Freecharge;

use Cache;
use Carbon\Carbon;
use Config;
use Lib\PhoneBook;
use View;

use RZP\Constants\Mode;
use RZP\Constants\HashAlgo;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant;
use RZP\Models\Payment\Core;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const DEFAULT_TXN_CHANNEL = 'WEB';

    const DEFAULT_TXN_TYPE    = 'CUSTOMER_PAYMENT';

    protected $gateway = 'wallet_freecharge';

    protected $sortRequestContent = true;

    protected $canRunOtpFlow = true;

    protected $topup = true;

    protected $action = null;

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

        throw new Exception\BaseException(
            'It is a Power Wallet, It should not go here');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        if ((isset($content[ResponseFields::ERROR_CODE])) and
                ($content[ResponseFields::ERROR_CODE] != ResponseCode::SUCCESS_CODE))
        {
            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERROR_CODE]),
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }

        // OTP_REDIRECT sends a authCode as query param
        // If it exists, handle it as callback for OTP_REDIRECT
        if (isset($input['gateway'][ResponseFields::AUTH_CODE]))
        {
            return $this->callbackOtpRedirectFlow($input);
        }

        return $this->callbackTopupFlow($input);
    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $this->domainType = Url::LOGIN;

        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content[ResponseFields::STATUS];

        $contentToSave = [
            RequestFields::MERCHANT_ID   => $this->getMerchantId($input['terminal']),
            RequestFields::EMAIL         => $input['payment']['email'],
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::AMOUNT        => $input['payment']['amount'],
        ];

        $this->action  = Action::AUTHORIZE;

        if ($code === Status::OTP_SENT)
        {
            $contentToSave['otpId'] = $content[ResponseFields::OTP_ID];
        }

        $this->createGatewayPaymentEntity($contentToSave);

        $this->action = Action::OTP_GENERATE;

        if ($code === Status::OTP_REDIRECT)
        {
            return $this->getOtpRedirectRequestArray($input);
        }

    }

    /*
     * Freecharge gives us an otpId and a separate API for resending OTP.
     * If otp count for the payment is greater than zero. We use otpResend instead of otpGenerate
     */
    public function otpResend(array $input)
    {
        $this->action($input, Action::OTP_RESEND);

        $this->domainType = Url::LOGIN;

        $request = $this->getOtpResendRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $otpId = $content[ResponseFields::OTP_ID];

        // Payment Gateway Entity for Authorize action
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, ['otpId' => $otpId]);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->domainType = Url::LOGIN;

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $data = array();

        if (isset($content[ResponseFields::ACCESS_TOKEN]))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $this->accessToken = $content[ResponseFields::ACCESS_TOKEN];

            $content[ResponseFields::ACCESS_TOKEN]  = '';

            $content[ResponseFields::REFRESH_TOKEN] = '';

            $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

            return $data;
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $this->verifyCheckSumForResponse($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

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

        //  Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;

        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            $this->action);

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
        $this->updateGatewayPaymentEntity($wallet, ['topup' => 'true']);

        $token = $this->getValidWalletToken($input);

        if ($token === null)
        {
            throw new Exception\BaseException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $this->accessToken = $token->getGatewayToken();

        return $this->getTopupWalletRedirectRequestArray($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $this->verifyCheckSumForResponse($content);

        $attributes = $this->getRefundAttributesFromRefundResponse($input, $content);

        $refund = $this->createGatewayRefundEntity($attributes);
    }

    protected function getTokenAttributes($content)
    {
        $input = $this->input;

        $attributes = array(
            Token\Entity::METHOD           => 'wallet',
            Token\Entity::WALLET           => $input['payment']['wallet'],
            Token\Entity::TERMINAL_ID      => $input['terminal']['id'],
            Token\Entity::GATEWAY_TOKEN    => $content[ResponseFields::ACCESS_TOKEN],
            Token\Entity::GATEWAY_TOKEN2   => $content[ResponseFields::REFRESH_TOKEN],
            //TODO Check the format of it.
            Token\Entity::EXPIRED_AT       => time() + $content[ResponseFields::ACCESS_TOKEN_EXPIRY],
        );

        return $attributes;
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

            $domainConstantName = $mode.'_'.$domainType.'_DOMAIN';
        }

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getStringToHash($content, $glue = '')
    {
        return json_encode($content, JSON_UNESCAPED_SLASHES).$this->getSecret();
    }

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $request = parent::getStandardRequestArray($content, $method);

        if (!$this->mock)
            $request['content'] = json_encode($request['content']);

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

        if($checkSum !== $expectedCheckSum)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getHashOfArray($content)
    {
        foreach ($content as $key => $value)
        {
            if($value === null or $value === "")
            {
                unset($content[$key]);
            }
        }

        return parent::getHashOfArray($content);
    }

    /*
     * Creates a login token for freecharge topup
     * 1. Encrypt accessToken with first 16 chars of merchantId
     * 2. Convert to hex format and return it
     *
     * @return string
     */
    protected function strToHex($cipherText)
    {
        $hex = '';

        for ($i = 0; $i < strlen($cipherText); $i++)
        {
            $ord = ord($cipherText[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0'.$hexCode, -2);
        }

        return strtoupper($hex);
    }

    protected function generateLoginToken($accessToken)
    {
        $secret = $this->getSecret();

        $key = substr($secret, 0, 16);

        // Encrypt accesstoken using AES 128 bit, ECB, PKCS7 padding
        $cipherText = openssl_encrypt($accessToken, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);

        return $this->strToHex($cipherText);
    }

    protected function getUserWalletBalance($input)
    {
        $this->action($input, Action::GET_BALANCE);

        $this->domainType = null;

        $request = $this->getUserWalletBalanceRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if (isset($content[ResponseFields::WALLET_BALANCE]))
        {
            return (int) ($content[ResponseFields::WALLET_BALANCE] * 100);
        }

        return 0;
    }

    protected function getUserWalletBalanceRequestArray($input)
    {
        $content = [
            'accessToken'   => $this->accessToken,
            'merchantId'    => $this->getMerchantId($input['terminal']),
        ];

        $content[ResponseFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content, $method = 'GET');

        $content = http_build_query($content);
        $request['url'] .= '?' . $content;
        $request['content'] = [];

        return $request;
    }

    protected function getDebitRequestArray($input)
    {
        $content = array(
            RequestFields::ACCESS_TOKEN    => $this->accessToken,
            RequestFields::AMOUNT          => (string) ($input['payment']['amount'] / 100),
            RequestFields::CHANNEL         => self::DEFAULT_TXN_CHANNEL,
            RequestFields::CURRENCY        => 'INR',
            RequestFields::MERCHANT_ID     => $this->getMerchantId($input['terminal']),
            RequestFields::MERCHANT_TXN_ID => $input['payment']['id'],
        );

        $content[ResponseFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

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

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpResendRequestArray($input)
    {
        $wallet = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = array(
            RequestFields::CHANNEL     => 'THROUGH_SMS',
            RequestFields::MERCHANT_ID => $this->getMerchantId($input['terminal']),
            RequestFields::OTP_ID      => $wallet['reference1'],
        );

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $wallet = $this->repo->retrieveByPaymentIdOrFail($input['payment']['id']);

        $content = array(
            RequestFields::OTP_ID                  => $wallet['reference1'],
            RequestFields::OTP                     => $input['gateway']['otp'],
            RequestFields::USER_MACHINE_IDENTIFIER => $this->getUMIForVerifyUser(),
            RequestFields::MERCHANT_ID             => $this->getMerchantId($this->input['terminal']),
        );

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    /*
     * Return an Unique Machine Identifier for the user's device
     * through which payment is being made
     * Presently, We return a unique string.
     *
     * @return string
     */
    protected function getUMIForVerifyUser()
    {
        return uniqid();
    }

    protected function getValidWalletToken($input)
    {

        $token = (New Token\Repository)->getByWalletTerminalAndCustomerId(
            $input['payment']['wallet'],
            $input['terminal']['id'],
            $input['customer']['id']);

        if ($token !== null and $token->getExpiredAt() > time())
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
            RequestFields::LOGIN_TOKEN  => $this->generateLoginToken($this->accessToken),
            RequestFields::MERCHANT_ID  => $this->getMerchantId($input['terminal']),
            RequestFields::METADATA     => 'dummy',
        );

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = parent::getStandardRequestArray($content);

        $request['headers'] = [
            'Accept' => 'application/x-www-form-urlencoded',
        ];

        $this->trace->info(TraceCode::PAYMENT_TOPUP_REQUEST, $request);

        return $request;
    }

    protected function getRefundRequestArray($input)
    {
        $wallet = $this->repo->retrieveByPaymentIdOrFail($input['payment']['id']);

        $content = [
            RequestFields::MERCHANT_ID            => $this->getMerchantId($input['terminal']),
            RequestFields::MERCHANT_TXN_ID        => $input['payment']['id'],
            RequestFields::REFUND_AMOUNT          => (string) ($input['refund']['amount'] / 100),
            RequestFields::REFUND_MERCHANT_TXN_ID => $input['refund']['id'],
            RequestFields::TXN_ID                 => $wallet['gateway_payment_id'],
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

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
            'response_code'         =>  '',
            'response_description'  =>  '',
            'status_code'           =>  $response['status'],
            'error_message'         =>  '',
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
            $request);

        $response = $this->sendGatewayRequest($request, 'GET');

        $this->handleRequestFailed($response);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->verifyCheckSumForResponse($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

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
        $response = $verify->verifyResponse;

        $verify->status = VerifyResult::STATUS_MATCH;

        // Gateway marked payment as a failure
        if ($content[ResponseFields::STATUS] !== Status::TRANSACTION_SUCCESS)
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

    protected function verifyStatusOnGatewayFailure()
    {
        $verify->gatewaySuccess = false;

        // Gateway payment is not created and payment status is not marked
        // as authorized.
        if (($payment === null) or
            (($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created')))
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
        else if ($payment['status_code'] === Status::SUCCESS)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyStatusOnGatewaySuccess()
    {
        $verify->gatewaySuccess = true;

        if (($input['payment']['status'] !== 'created') and
                ($input['payment']['status'] !== 'failed') and
                $payment['received'] === true)
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

        if ((isset($content[ResponseFields::STATUS]))
              and ($content[ResponseFields::STATUS] === Status::TRANSACTION_SUCCESS))
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

        if (!isset($payment['amount']))
        {
            $contentToSave['amount'] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

    protected function getVerifyRequestArray($input)
    {
        $wallet = $this->repo->retrieveByPaymentId($input['payment']['id']);

        $content = [
            RequestFields::MERCHANT_ID     => $this->getMerchantId($input['terminal']),
            RequestFields::MERCHANT_TXN_ID => $input['payment']['id'],
            RequestFields::TXN_ID          => $wallet['gateway_payment_id'],
            RequestFields::TXN_TYPE        => self::DEFAULT_TXN_TYPE,
        ];

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function getOtpRedirectRequestArray(array $input)
    {
        $this->action = Action::OTP_REDIRECT;

        $this->domainType = Url::LOGIN;

        $content = [
            RequestFields::CALLBACK_URL  => $input['callbackUrl'],
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::MERCHANT_ID   => $this->getMerchantId($input['terminal']),
        ];

        $request = parent::getStandardRequestArray($content);

        return $request;
    }

    /*
     * Freecharge has finished with the topup checkout.
     * It sends a response with updated wallet balance..
     * Break the flow if the topup is a failure
     */
    protected function callbackTopupFlow($input)
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_TOPUP_CALLBACK, $input['gateway']);

        $content = $input['gateway'];

        if ((isset($content[ResponseFields::STATUS]) === true) and
            ($content[ResponseFields::STATUS] === Status::TOPUP_SUCCESS))
        {
            $this->verifyCheckSumForResponse($content);

            $token = $this->getValidWalletToken($input);

            if ($token !== null)
            {
                $this->accessToken = $token->getGatewayToken();
            }
        }

        $this->handleRequestFailed($content);
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

        $request = $this->getExchangeTokenRequestArray($callback);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $content);

        $response = $this->sendGatewayRequest($request);

        $this->handleRequestFailed($response);

        $content = $this->jsonToArray($response->body);

        if (isset($content[ResponseFields::ACCESS_TOKEN]))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $this->accessToken = $content[ResponseFields::ACCESS_TOKEN];

            $content[ResponseFields::ACCESS_TOKEN]  = '';

            $content[ResponseFields::REFRESH_TOKEN] = '';

            return $data;
        }
    }

    protected function getExchangeTokenRequestArray($callback)
    {
        $content = [
            RequestFields::AUTH_CODE   => $callback[ResponseFields::AUTH_CODE],
            RequestFields::GRANT_TYPE  => 'AUTHORIZATION_CODE',
            RequestFields::MERCHANT_ID => $this->getMerchantId($input['terminal']),
        ];

        $content[ResponseFields::CHECKSUM] = $this->getHashOfArray($content);

        return $this->getStandardRequestArray($content);
    }

    /*
     * When API call to freecharge returns an error, Throw a gateway error Exception
     * exception.
     */
    protected function handleRequestFailed($response)
    {
        if ($response->status_code === 500)
        {
            $this->trace->info(TraceCode::GATEWAY_PAYMENT_ERROR, $response);

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
        }
        else if ($response->status_code === 202)
        {
            $content = $this->jsonToArray($response->body);

            $this->trace->info(TraceCode::GATEWAY_PAYMENT_ERROR, $content);

            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERROR_CODE]),
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_MESSAGE]);
        }
    }
}
