<?php

namespace RZP\Gateway\Wallet\Freecharge;

use Cache;
use Carbon\Carbon;
use Config;
use Lib\PhoneBook;
use View;


use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Gateway\Wallet\Freecharge\ResponseCodeMap;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant;
use RZP\Models\Payment\Core;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_freecharge';

    protected $sortRequestContent = false;

    protected $canRunOtpFlow = true;

    protected $topup = true;

    protected $action = null;

    protected $map = array(
        'email'         => 'email',
        'mobileNumber'  => 'contact',
        'merchantId'    => 'gateway_merchant_id',
        'txnId'         => 'gateway_payment_id',
        'refundId'      => 'gateway_refund_id',
        'status'        => 'status_code',
        'amount'        => 'amount',
        'message'       => 'response_description',
        'received'      => 'received',
        'otpId'         => 'reference1',
        // boolean true or false to store if topup happened here.
        'topup'         => 'reference2',
    );

    public function __construct()
    {
        parent::__construct();

        $this->secureCache = Config::get('cache.secure_default');
    }

    public function authorize(array $input)
    {
        parent::authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackTopupFlow($input);
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

    protected function sortKeysAndGenerateHash(array $response)
    {
        // Sort all keys and arrange <K-V> pair in alphabetical order
       ksort($response);

       $secretKey = $this->app->config['test_hash_secret'];

       $hashString = json_encode($response).$secretKey;

       return hash('sha256', $hashString);
    }


    protected function getStringToHash($content, $glue = '')
    {
        return json_encode($content).$this->getSecret();
    }

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $request = parent::getStandardRequestArray($content, $method);
        if($this->mode == Mode::LIVE)
            $request['content'] = json_encode($request['content']);
        $request['headers'] = [
            'Content-Type'  => 'application/json',
        ];

        return $request;
    }

    protected function getHashOfString($str)
    {
        return hash('sha256', $str);
    }

    protected function verifyCheckSumForResponse($response)
    {
        $checkSum = $response['checksum'];
        unset($response['checksum']);
        $expectedCheckSum  = $this->sortKeysAndGenerateHash($response);

        assert($checkSum === $expectedCheckSum);
    }

    /*
     * Creates a login token for freecharge topup
     * 1. Encrypt accessToken with first 16 chars of merchantId
     * 2. Convert to hex format and return it
     *
     * @return string
     */
    protected function createLoginToken($accessToken, $merchantId)
    {
        $key = mb_substr($merchantId, 0, 16);

        // Encrypt accesstoken using AES 128 bit, ECB, PKCS7 padding
        $cipherText = openssl_encrypt($accessToken, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);

        $loginToken = base64_encode($cipherText);

        return $loginToken;
    }


    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);
        $this->domainType = 'login';


        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        // 'VERIFY' code is set, when OTP is successfully sent.
        // 'REDIRECT' code is set, when new user needs to be cerated
        if ($response->status_code !== 200)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            if (isset($content['errorCode']))
            {
                $errorCode = ResponseCode::getApiErrorCode($content['errorCode']);

                // Payment fails, throw exception
                throw new Exception\GatewayErrorException(
                    $errorCode,
                    $content['errorCode'],
                    $content['errorMessage']);
            }

        }

        $code = $content['status'];

        if($code === Status::OTP_SENT)
        {
            $this->action  = Action::AUTHORIZE;
            $contentToSave = [
                'otpId'        => $content['otpId'],
                'merchantId'   => $this->getMerchantId($input['terminal']),
                'email'        => $input['payment']['email'],
                'mobileNumber' => $this->getFormattedContact($input['payment']['contact']),
                'amount'       => $input['payment']['amount'],
            ];
            $this->createGatewayPaymentEntity($contentToSave);
            $this->action = Action::OTP_GENERATE;
        }
    }

    /*
     * Freecharge gives us an otpId and a separate API for resending OTP.
     * If otp attempts for the payment is greater than zero. We use otpResend instead of otpGenerate
     *
     */
    public function otpResend($input)
    {
        $this->action($input, Action::OTP_RESEND);
        $this->domainType = 'login';


        $request = $this->getOtpResendRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if ($response->status_code !== 200)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            if (isset($content['errorCode']))
            {
                $errorCode = ResponseCode::getApiErrorCode($content['errorCode']);

                // Payment fails, throw exception
                throw new Exception\GatewayErrorException(
                    $errorCode,
                    $content['errorCode'],
                    $content['errorMessage']);
            }
        }

        $otpId = $content['otpId'];
        // For Authorize action
        $wallet = $this->fetchPaymentGateway($input['payment']['id']);
        $this->updateGatewayPaymentEntity($wallet, ['otpId' => $otpId]);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $data = array();

        if (isset($content['accessToken']))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $this->accessToken = $content['accessToken'];

            $content['access_token']  = '';
            $content['refresh_token'] = '';

            return $data;
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        // 202 Status code is set when OTP validation fails
        // TODO: Handle 500 errors by gateway elegantly.
        if ($response->status_code !== 200)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($content['errorCode']);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['errorCode'],
                $content['errorMessage']);
        }

    }

    protected function getTokenAttributes($content)
    {
        $input = $this->input;

        $attributes = array(
            Token\Entity::METHOD           => 'wallet',
            Token\Entity::WALLET           => $input['payment']['wallet'],
            Token\Entity::TERMINAL_ID      => $input['terminal']['id'],
            Token\Entity::GATEWAY_TOKEN    => $content['accessToken'],
            Token\Entity::GATEWAY_TOKEN2   => $content['refreshToken'],
            Token\Entity::EXPIRED_AT       => time() + $content['accessTokenExpiry'],
        );

        return $attributes;
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->verifyCheckSumForResponse($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if ($content['Status'] !== 'COMPLETED')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['errorCode'],
                $content['errorMessage']);
        }

        // Maintain consistency in status_code value for successful debit/topup/verify
        // Replace completed with 'success'
        if($content['Status'] === Freecharge\Status::DEBIT_SUCCESS)
        {
            $content['Status'] = Freecharge\Status::TRANSACTION_SUCCESS;
        }

        $contentToSave = array(
            'merchantId' => $this->getMerchantId($input['terminal']),
            'email'    => $input['payment']['email'],
            'mobileNumber'   => $this->getFormattedContact($input['payment']['contact']),
            'status'   => $content['Status'],
            'amount'   => $input['payment']['amount'],
            'txnId'    => $content['txnId'],
            'received' => true
        );

        //  Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;
        $wallet = $this->getRepo()
            ->fetchWalletByPaymentIdAndAction(
                $input['payment']['id'],
                $this->action);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);

        $this->action = Action::DEBIT_WALLET;
    }

    public function checkBalance(array $input)
    {
        $userBalance = $this->getUserWalletLimit($input);

        if ($input['payment']['amount'] > $userBalance)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);
        }
    }

    protected function getUserWalletLimit($input)
    {
        $this->action($input, Action::GET_BALANCE);

        $request = $this->getUserWalletLimitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if (isset($content['walletBalance']))
        {
            return (int) ($content['walletBalance'] * 100);
        }

        return 0;
    }

    protected function getUserWalletLimitRequestArray($input)
    {
        $content = [];

        $content = array(
            'merchantId'    => $this->getMerchantId($input['terminal']),
            'accessToken'   => $this->accessToken,
        );

        $content['checksum'] = $this->getHashForUserWalletLimit($content);


        $request = $this->getStandardRequestArray($content, $method = 'GET');

        $content = http_build_query($content);
        $request['url'] .= '?'.$content;
        $request['content'] = [];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['headers'] = array(
            'Accept' => 'application/json',
        );

        return $request;
    }

    protected function getHashForUserWalletLimit($content)
    {
        $fieldsInOrder = array(
            'accessToken',
            'merchantId',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getDebitRequestArray($input)
    {
        $content = array(
            'merchantId'            => $this->getMerchantId($input['terminal']),
            'amount'                => (string) ($input['payment']['amount'] / 100),
            'merchantTxnId'         => $input['payment']['id'],
            // Unnecessary info requested by Freecharge, Put it as WEB for everything
            'channel'               => 'WEB',
            'accessToken'           => $this->accessToken,
            'currency'              => 'INR',
        );

        $content['checksum'] = $this->getHashForDebitWallet($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
        );

        return $request;
    }

    protected function getHashForDebitWallet($content)
    {
        $fieldsInOrder = array(
            'accessToken',
            'amount',
            'channel',
            'currency',
            'merchantId',
            'merchantTxnId',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    public function getOtpGenerateRequestArray($input)
    {
        $content = array(
            'email'         => $input['payment']['email'],
            'merchantId'    => $this->getMerchantId($input['terminal']),
            'mobileNumber'  => $this->getFormattedContact($input['payment']['contact']),
        );

        $content['checksum'] = $this->getHashForRegisterUser($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = array(
            'Content-Type'  => 'application/json',
        );

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    public function getOtpResendRequestArray($input)
    {
        $wallet = $this->fetchPaymentGateway($input['payment']['id']);
        $content = array(
            'channel'       => 'THROUGH_SMS',
            'otpId'         => $wallet['reference1'],
            'merchantId'    => $this->getMerchantId($input['terminal']),
        );

        $content['checksum'] = $this->sortKeysAndGenerateHash($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = array(
            'Content-Type'  => 'application/json',
        );

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    public function getOtpSubmitRequestArray($input)
    {
        $wallet = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);
        $content = array(
            'otpId'                     => $wallet['reference1'],
            'otp'                       => $input['gateway']['otp'],
            'userMachineIdentifier'     => $this->getUMIForVerifyUser($input),
        );

        $content['merchantId'] = $this->getMerchantId($this->input['terminal']);

        $content['checksum'] = $this->getHashForOtpSubmitRequest($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    /*
     * Return an Unique Machine Identifier for the user's device
     * through which payment is being made
     *
     * @return array
     */
    public function getUMIForVerifyUser($input)
    {
        # TODO: In progress
        return 'asd';
    }

    public function getHashForRegisterUser($content)
    {
        $fieldsInOrder = [
            'email',
            'merchantId',
            'mobileNumber',
        ];

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashofArray($orderedData);
    }

    public function getHashForOtpSubmitRequest($content)
    {
        $fieldsInOrder = [
            'merchantId',
            'otp',
            'otpId',
            'userMachineIdentifier',
        ];

        $content['merchantId'] = $this->getMerchantId($this->input['terminal']);

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashofArray($orderedData);
    }

    public function topup($input)
    {
        $this->action($input, Action::TOPUP_REDIRECT);
        $wallet = $this->fetchPaymentGateway($input['payment']['id']);
        $this->updateGatewayPaymentEntity($wallet, ['topup' => 'true']);

        $token = $this->getValidWalletToken($input);

        if ($token === null)
        {
            throw new Exception\BaseException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $this->accessToken = $token->getGatewayToken();

        return $this->getTopupWalletRedirectRequestArray($input);

    }

    protected function getValidWalletToken($input)
    {

        $token = (new Customer\Token\Repository)
                        ->getByWalletTerminalAndCustomerId(
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
        $content = [];

        $merchantId = $this->getMerchantId($input['terminal']);

        $this->loginToken = $this->createLoginToken($this->accessToken, $merchantId);

        $content = array(
            'merchantId'    => $merchantId,
            // Topup amount is equal to payment amount - we topup how much he has to pay.
            'amount'        => $input['payment']['amount'] / 100,
            'loginToken'    => $this->loginToken,
            'callbackUrl'   => $input['callbackUrl'],
            'channel'       => 'WEB',
            'metadata'      => 'dummy',
        );

        $content['checksum'] = $this->getHashForTopupWallet($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::PAYMENT_TOPUP_REQUEST, $request);

        $request['headers'] = array(
            'Accept' => 'application/x-www-form-urlencoded',
        );

        return $request;
    }

    protected function getHashForTopupWallet($content)
    {
        $fieldsInOrder = array(
            'amount',
            'callbackUrl',
            'channel',
            'loginToken',
            'merchantId',
            'metadata',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->verifyCheckSumForResponse($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $attributes = $this->getRefundAttributesFromRefundResponse($input, $content);

        $refund = $this->createGatewayRefundEntity($attributes);

        if ($response->status_code !== 200 ||
                $content['status'] !== Status::REFUND_SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content['errorCode'],
                $content['errorMessage']);
        }
    }

    protected function getRefundRequestArray($input)
    {
        $content = [];

        $wallet = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);

        $content =  array(
            'merchantId'            => $this->getMerchantId($input['terminal']),
            'merchantTxnId'         => $input['payment']['id'],
            'refundAmount'          => $input['refund']['amount'] / 100,
            'refundMerchantTxnId'   => $input['refund']['id'],
            // Not mandatory when we send payment id
            'txnId'                 => $wallet['gateway_payment_id'],
        );

        $content['checksum'] = $this->getHashForRefund($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['headers'] = array(
            'Accept' => 'application/json',
        );

        return $request;
    }

    protected function getHashForRefund($content)
    {
        $fieldsInOrder = array(
            'merchantId',
            'merchantTxnId',
            'refundAmount',
            'txnId',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getRefundAttributesFromRefundResponse($input, $response)
    {
        $refundAttributes = array(
            'payment_id'            =>  $input['payment']['id'],
            'action'                =>  $this->action,
            'amount'                =>  $input['refund']['amount'],
            'wallet'                =>  $input['payment']['wallet'],
            'email'                 =>  $input['payment']['email'],
            'received'              =>  1,
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

    /*
     * Freecharge has finished with the topup checkout.
     * It sends a response with updated wallet balance..
     * Break the flow if the topup is a failure
     */
    public function callbackTopupFlow($input)
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_TOPUP_CALLBACK, $input['gateway']);

        $content = $input['gateway'];
        $this->verifyCheckSumForResponse($content);

        if (isset($content['status']) and
            ($content['status'] === Status::TOPUP_SUCCESS))
        {
            $token = $this->getValidWalletToken($input);

            if ($token !== null)
            {
                $this->accessToken = $token->getGatewayToken();
                return;
            }
        }

        // TODO: Map Response Code to Razorpay response code
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            $content['errorCode'],
            $content['errorMessage']);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request, 'GET');

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->verifyCheckSumForResponse($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => 'freecharge',
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

        // API call is a success, it it returns a 200.
        if ($response->status_code !== 200)
        {
            $verify->apiSuccess = false;
        }
        else
        {
            if ($content['status'] !== Status::TRANSACTION_SUCCESS)
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
            else if ($content['status'] === Status::TRANSACTION_SUCCESS)
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

    protected function saveVerifyContentIfNeeded($payment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if (isset($content['status']) and $content['status'] === Status::TRANSACTION_SUCCESS)
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
            'merchantId'            => $this->getMerchantId($this->input['terminal']),
            'email'                 => $this->input['payment']['email'],
            'mobileNumber'          => $this->getFormattedContact($this->input['payment']['contact']),
            'status'                => Status::TRANSACTION_SUCCESS,
            'txnId'                 => $content['txnId'],
            'received'              => true
        );

        if (isset($payment['amount']) === false)
        {
            $contentToSave['amount'] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

    protected function getVerifyRequestArray($input)
    {

        $wallet = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);

        $content = [
            'merchantId'    => $this->getMerchantId($this->input['terminal']),
            'merchantTxnId' => $input['payment']['id'],
            'txnId'         => $wallet['gateway_payment_id'],
            'txnType'       => 'CUSTOMER_PAYMENT',
        ];

        $content['checksum'] = $this->getHashForVerify($content);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function getHashForVerify($content)
    {
        $fieldsInOrder = array(
            'merchantId',
            'merchantTxnId',
            'txnId',
            'txnType',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }
}
