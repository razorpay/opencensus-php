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
use RZP\Gateway\Wallet\Freecharge;
use RZP\Gateway\Wallet\Freecharge\Action;
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
        'received'      => 'received'
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

    }

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
    }


    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);
        $this->domainType = 'login';

        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);


        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['status'];

        if($code === Status::OTP_SENT)
        {
            // Freecharge generates an OtpId which we should send to OTP Submit API
            $this->storeOtpIdInCache($input, $content);
        }

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
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        if (isset($content['accessToken']))
        {
            $data['token'] = $this->getTokenAttributes($content);

            $this->accessToken = $content['accessToken'];

            $content['access_token']  = '';
            $content['refresh_token'] = '';
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        // 202 Status code is set when OTP validation fails
        // TODO: Handle 500 errors by gateway elegantly.
        if ($response->status_code !== 200)
        {
            $errorCode = ResponseCode::getApiErrorCode($content['errorCode']);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['errorCode'],
                $content['errorMessage']);
        }

        return $data;
    }

    protected function storeOtpIdInCache($otpId, $paymentId, $gateway)
    {
        $key = $this->getCacheKeyForOtpId($paymentId, $gateway);

        Cache::store($this->secureCache)->put($key, $otpId, 10);
    }

    protected function getOtpIdFromCache($paymentId, $gateway)
    {
        $key = $this->getCacheKeyForOtpId($paymentId, $gateway);

        return Cache::store($this->secureCache)->pull($key);
    }

    protected function getCacheKeyForOtpId($paymentId, $gateway)
    {
        return $gateway.'_'.$paymentId.'_otpId';
    }

    public function debit(array $input)
    {
        $this->action($input, Action::DEBIT_WALLET);

        $request = $this->getDebitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

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

        //  Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;

        $this->createGatewayPaymentEntity($contentToSave);

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

        if ($content['status'] === Status::SUCCESS and
            isset($content['result']['availableBalance']))
        {
            return (int) ($content['result']['availableBalance'] * 100);
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

        $content['hash'] = $this->getHashForUserWalletLimit($content);

        $request = $this->getStandardRequestArray($content, $method = 'get');

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
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

    protected function getDebitRequestArray()
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

        $content['hash'] = $this->getHashForDebitWallet($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken
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
            'email'     => $input['payment']['email'],
            'mobileNumber'    => $this->getFormattedContact($input['payment']['contact'])
        );

        $content['checksum'] = $this->getHashForRegisterUser($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    public function getOtpSubmitRequestArray($input)
    {
        $paymentId = $input['payment']['Id'];

        $content = array(
            'otpId'                     => $this->getOtpIdFromCache($paymentId, $this->gateway),
            'otp'                       => $input['gateway']['otp'],
            'userMachineIdentifier'     => $this->getUMIForVerifyUser($input),
        );

        $content['merchantId'] = $this->getMerchantId($this->input['terminal']);

        $content['checksum'] = $this->getHashForOtpSubmitRequest();

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
            'merchantId',
            'email',
            'mobileNumber'
        ];

        $content['merchantId'] = $this->getMerchantId($this->input['terminal']);

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
}
