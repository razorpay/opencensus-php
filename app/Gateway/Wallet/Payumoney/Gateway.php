<?php

namespace RZP\Gateway\Wallet\Payumoney;

use Cache;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use View;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const BALANCE_KEY = 'payumoney_balance_%s';

    const DEVICES = [
        Payment\Analytics\Metadata::MOBILE => '2',
    ];

    protected $gateway = 'wallet_payumoney';

    protected $sortRequestContent = false;

    protected $canRunOtpFlow = true;

    protected $topup = true;

    protected $map = array(
        'email'         => 'email',
        'mobile'        => 'contact',
        'key'           => 'gateway_merchant_id',
        'txnId'         => 'gateway_payment_id',
        'refundId'      => 'gateway_refund_id',
        'status'        => 'status_code',
        'amount'        => 'amount',
        'message'       => 'response_description',
        'received'      => 'received'
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        throw new Exception\LogicException(
            'Authorize function not implemented');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackTopupFlow($input);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'content' => $content,
                'gateway' => 'payumoney',
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($content['status'] === Status::SUCCESS)
        {
            if ($content['result'][0]['status'] !== 'success')
            {
                $verify->gatewaySuccess = false;

                if (($payment === null) and
                    (($input['payment']['status'] === 'failed') or
                     ($input['payment']['status'] === 'created')))
                {
                    $verify->apiSuccess = false;
                }
                else if (($payment['received'] === false) and
                         (($payment['status_code'] === null) or
                          ($payment['status_code'] !== (string) Status::SUCCESS)))
                {
                    $verify->apiSuccess = false;
                }
                else if ($payment['status_code'] === (string) Status::SUCCESS)
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->apiSuccess = true;
                }
            }
            else if ($content['result'][0]['status'] === "success")
            {
                $verify->gatewaySuccess = true;

                if (($input['payment']['status'] !== 'created') and
                    ($input['payment']['status'] !== 'failed'))
                {
                    $verify->apiSuccess = true;
                }
                else
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                    $verify->apiSuccess = false;
                }
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    public function verifyRefund(array $input)
    {
        parent::verifyRefund($input);
    }

    protected function saveVerifyContentIfNeeded($gatewayPayment, $response)
    {
        $content = $response['result'][0];

        if ((isset($content['status']) === true) and
            ($content['status'] === Status::VERIFY_SUCCESS))
        {
            $walletAttributes = $this->getWalletContentFromVerify($gatewayPayment, $content);

            if ($gatewayPayment === null)
            {
                $gatewayPayment = $this->createGatewayPaymentEntity($walletAttributes, Action::AUTHORIZE);
            }
        }

        return $gatewayPayment;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $content);

        $attributes = $this->getRefundAttributesFromRefundResponse($input, $content);

        $refund = $this->createGatewayRefundEntity($attributes);

        if ($content['status'] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content['status'],
                $content['message']);
        }
    }

    public function topup($input)
    {
        $this->action($input, Action::TOPUP_WALLET);

        $request = $this->getTopupWalletRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_TOPUP_RESPONSE, $content);

        if ($content['status'] === Status::SUCCESS)
        {
            return $this->getTopupWalletRedirectRequestArray($input, $content);
        }

        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            $content['status'],
            $content['message']);
    }

    public function checkExistingUser($input)
    {

    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_RESPONSE, $content);

        if ($content['status'] !== Status::SUCCESS)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            if (isset($content['errorCode']))
            {
                $errorCode = ResponseCodeMap::getApiErrorCode($content['errorCode']);
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['status'],
                $content['message']);
        }

        return $this->getOtpSubmitRequest($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $data = [];

        if (isset($content['result']['body']['access_token']))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $this->accessToken = $content['result']['body']['access_token'];

            $content['result']['body']['access_token'] = '';
            $content['result']['body']['refresh_token'] = '';
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_RESPONSE, $content);

        if (($content['status'] !== Status::SUCCESS) or
            (isset($content['result']['body']['access_token']) === false))
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($content['errorCode']);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['status'],
                $content['message']);
        }

        $callbackResponse = $this->getCallbackResponseData($input);

        $callbackResponse = array_merge($callbackResponse, $data);

        return $callbackResponse;
    }

    public function callbackTopupFlow($input)
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_TOPUP_CALLBACK, $input['gateway']);

        $content = $input['gateway'];

        // Not verifying hash as it's generated with different secret by payu
        if ((isset($content['status']) === false) or
            ($content['status'] !== Status::TOPUP_SUCCESS))
        {
            $status = $content['status'] ?? null;
            $message = $content['error_Message'] ?? null;

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $status,
                $message);
        }

        return [];
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_DEBIT_RESPONSE, $content);

        if ($content['status'] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['status'],
                $content['message']);
        }

        $contentToSave = array(
            'key'      => $this->getMerchantId($input['terminal']),
            'email'    => $input['payment']['email'],
            'mobile'   => $this->getFormattedContact($input['payment']['contact']),
            'status'   => $content['status'],
            'amount'   => $input['payment']['amount'],
            'txnId'    => $content['result'],
            'message'  => $content['message'],
            'received' => true
        );

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);
    }

    public function checkBalance(array $input)
    {
        list($availableBalance, $maxWalletLimit) = $this->getUserWalletLimit($input);

        if ($availableBalance !== null)
        {
            if (($input['payment']['amount'] - $availableBalance) > $maxWalletLimit)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED);
            }

            if ($input['payment']['amount'] > $availableBalance)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);
            }
        }
    }

    protected function getUserWalletLimit($input)
    {
        $this->action($input, Action::GET_BALANCE);

        $request = $this->getUserWalletLimitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_CHECK_BALANCE_RESPONSE, $content);

        if ((isset($content['status']) === true) and
            ($content['status'] === Status::SUCCESS) and
            (isset($content['result']['availableBalance']) === true))
        {
            $key = $this->getBalanceKeyForCache($input['payment']);

            // We are caching user wallet balance for 30 mins for
            // optimization purpose.
            // Optimization: Suppose user already has 50 rupee in
            // his wallet and is making a payment of 100 rupee.
            // With this optimization, user will only have to add
            // 50 rupee instead of 100.
            Cache::put($key, $content['result'], self::PAYMENT_TTL);

            return [(int) ($content['result']['availableBalance'] * 100),
                    (int) ($content['result']['maxLimit'] * 100)];
        }

        // If check balance API fails for some reason,
        // we let payumoney handle it in debit instead
        // of throwing low balance error.
        return [null, null];
    }

    protected function getBalanceKeyForCache($payment)
    {
        return sprintf(self::BALANCE_KEY, $payment['id']);
    }

    protected function checkWalletTokenValidity($input)
    {
        if ($input['token']->getExpiredAt() <= time())
        {
            throw new Exception\BaseException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function getUserWalletLimitRequestArray($input)
    {
        $content = array(
            'email'     => $input['payment']['email'],
            'client_id' => $this->getClientId($input['terminal']),
        );

        $content['hash'] = $this->getHashForUserWalletLimit($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_CHECK_BALANCE_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $input['token']['gateway_token']
        );

        return $request;
    }

    protected function getRefundRequestArray($input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $content =  array(
            'merchantKey'   => $this->getMerchantId($input['terminal']),
            'paymentId'     => $wallet['gateway_payment_id'],
            'refundAmount'  => (string) ($input['refund']['amount'] / 100)
        );

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => $this->getAuthHeader($input['terminal'])
        );

        return $request;
    }

    protected function getDebitRequestArray($input)
    {
        $content = array(
            'key'                   => $this->getMerchantId($input['terminal']),
            'totalAmount'           => (string) ($input['payment']['amount'] / 100),
            'client_id'             => $this->getClientId($input['terminal']),
            'merchantTransactionId' => $input['payment']['id'],
        );

        $content['hash'] = $this->getHashForDebitWallet($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_DEBIT_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $input['token']['gateway_token']
        );

        return $request;
    }

    protected function getRefundAttributesFromRefundResponse($input, $response)
    {
        $refundAttributes = array(
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            'amount'                => $input['refund']['amount'],
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'received'              => 1,
            'contact'               => $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'refund_id'             => $input['refund']['id'],
            'response_code'         => '',
            'response_description'  => $response['message'],
            'status_code'           => $response['status'],
            'error_message'         => '',
            'gateway_refund_id'     => $response['result']
        );

        return $refundAttributes;
    }

    protected function getVerifyRequestArray($input)
    {
        $content['key'] = $this->getMerchantId($this->input['terminal']);
        $content['merchantTransactionId'] = $input['payment']['id'];

        $content['hash'] = $this->getHashOfArray($content);

        // key is not to be sent in actual request but
        // only for calculating hash.
        unset($content['key']);

        // Client id is surprisingly not used for hashing so needs to be
        // added after hashing.

        $content['client_id'] = $this->getClientId($input['terminal']);

        $request = $this->getStandardRequestArray($content, 'GET');

        return $request;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $content = array(
            'email'     => $input['payment']['email'],
            'mobile'    => $this->getFormattedContact($input['payment']['contact']),
            'client_id' => $this->getClientId($input['terminal'])
        );

        $content['hash'] = $this->getHashForRegisterUser($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $content = array(
            'email'         => $input['payment']['email'],
            'mobile'        => $this->getFormattedContact($input['payment']['contact']),
            'client_id'     => $this->getClientId($input['terminal']),
            'otp'           => $input['gateway']['otp']
        );

        $content['hash'] = $this->getHashForVerifyUser($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_REQUEST, $request);

        return $request;
    }

    protected function getTopupWalletRequestArray($input)
    {
        $key = $this->getBalanceKeyForCache($input['payment']);

        $userWalletLimit = Cache::get($key);

        $amount = ($input['payment']['amount'] / 100);

        // Optimize wallet topup amount, if wallet balance
        // cache is available. Use payment amount if cache
        // is unavailable
        if (isset($userWalletLimit) === true)
        {
            $amount = ($amount - $userWalletLimit['availableBalance']);

            if ($amount < $userWalletLimit['minLimit'])
            {
                $amount = $userWalletLimit['minLimit'];
            }
        }

        $content = array(
            'key'           => $this->getMerchantId($input['terminal']),
            'txnDetails'    => json_encode(array(
                'email' => $input['payment']['email'],
                'surl'  => $input['callbackUrl'],
                'furl'  => $input['callbackUrl'],
            )),
            'totalAmount'   => ceil($amount),
            'client_id'     => $this->getClientId($input['terminal']),
        );

        if ((isset($input['analytics']['device']) === true) and
            ($input['analytics']['device'] === Payment\Analytics\Metadata::MOBILE))
        {
            $content['isMobile'] = self::DEVICES[Payment\Analytics\Metadata::MOBILE];
        }

        $content['hash'] = $this->getHashForTopupWallet($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_TOPUP_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $input['token']['gateway_token']
        );

        return $request;
    }

    protected function getTopupWalletRedirectRequestArray(array $input, $content)
    {
        $this->action($input, Action::TOPUP_REDIRECT);

        $content = array(
            'paymentId'     => $content['result'],
            'accessToken'   => $input['token']['gateway_token']
        );

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function getAuthHeader($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_auth_header'];
        }

        return $terminal['gateway_merchant_id2'];
    }

    protected function getClientId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestClientId();
        }

        return $terminal['gateway_access_code'];
    }

    protected function getTestClientId()
    {
        if (isset($this->config['test_access_code']))
        {
            return $this->config['test_access_code'];
        }

        return null;
    }

    protected function getHashForRegisterUser($content)
    {
        $fieldsInOrder = array(
            'key',
            'mobile',
            'email',
        );

        $content['key'] = $this->getMerchantId($this->input['terminal']);

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForUserWalletLimit($content)
    {
        $fieldsInOrder = array(
            'key',
            'mobile',
            'email',
        );

        $content['key']     = $this->getMerchantId($this->input['terminal']);
        $content['mobile']  = $this->getFormattedContact($this->input['payment']['contact']);

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForVerifyUser($content)
    {
        $fieldsInOrder = array(
            'key',
            'mobile',
            'email',
        );

        $content['key']     = $this->getMerchantId($this->input['terminal']);

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForDebitWallet($content)
    {
        $fieldsInOrder = array(
            'key',
            'totalAmount',
            'productInfo',
            'merchantTransactionId',
        );

        $content['productInfo'] = '';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForTopupWallet($content)
    {
        $fieldsInOrder = array(
            'key',
            'totalAmount',
            'dummy',
        );

        $content['dummy'] = '';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "|");

        $str .= '|' . $this->getSecret();

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        return strtolower(hash(HashAlgo::SHA512, $str, false));
    }

    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = array(
            'key'       => $this->getMerchantId($this->input['terminal']),
            'email'     => $this->input['payment']['email'],
            'mobile'    => $this->getFormattedContact($this->input['payment']['contact']),
            'status'    => Status::SUCCESS,
            'txnId'     => $content['paymentId'],
            'received'  => true
        );

        if (isset($payment['amount']) === false)
        {
            $contentToSave['amount'] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

    protected function getTokenAttributes($content)
    {
        $input = $this->input;

        $attributes = array(
            Token\Entity::METHOD           => 'wallet',
            Token\Entity::WALLET           => $input['payment']['wallet'],
            Token\Entity::TERMINAL_ID      => $input['terminal']['id'],
            Token\Entity::GATEWAY_TOKEN    => $content['result']['body']['access_token'],
            Token\Entity::GATEWAY_TOKEN2   => $content['result']['body']['refresh_token'],
            Token\Entity::EXPIRED_AT       => time() + $content['result']['body']['expires_in'],
        );

        return $attributes;
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function getValidWalletToken($input)
    {
        $token = (new Customer\Token\Repository)
                        ->getByWalletTerminalAndCustomerId(
                            $input['payment']['wallet'],
                            $input['terminal']['id'],
                            $input['customer']['id']);

        if (($token !== null) and ($token->getExpiredAt() > time()))
        {
            return $token;
        }
    }
}
