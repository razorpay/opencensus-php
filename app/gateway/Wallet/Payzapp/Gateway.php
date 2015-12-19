<?php

namespace Gateway\Wallet\Payzapp;

use Constants\Mode;
use EE\Error;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base\Action;
use Gateway\Base\AuthorizeFailed;
use Gateway\Base\Verify;
use Gateway\Base\VerifyResult;
use Gateway\Wallet\Base;
use Trace\Trace;
use Trace\TraceCode;
use Carbon\Carbon;
use View;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_payzapp';
    protected $pgname  = 'hdfcpg';

    protected $map = array(
        'custEmail'         => 'email',
        'custMobile'        => 'contact',
        'merId'             => 'gateway_merchant_id',
        'wibmoTxnId'        => 'gateway_payment_id',
        'pgTxnId'           => 'gateway_payment_id_2',
        'pgVoidTxnId'       => 'gateway_refund_id',
        'resCode'           => 'response_code',
        'resDesc'           => 'response_description',
        'pgStatusCode'      => 'status_code',
        'dataPickUpCode'    => 'reference1',
        'actionCode'        => 'reference2',
        'txnAmount'         => 'amount',
    );

    protected $performMap = array(
        'void'              => 'processMerchantAPI#DirectVoid',
        'refund'            => 'processMerchantAPI#DirectRefund',
        'verify'            => 'getPaymentResult',
    );

    protected $perform;

    protected $acosaActions = array(
        ACTION::VERIFY, ACTION::REFUND
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthContent($input);

        $contentToSave = [];

        foreach ($content as $category => $info)
        {
            foreach ($info as $key => $value)
            {
                $contentToSave[$key] = $value;
            }
        }

        $contentToSave['supportedPaymentType'] = '*';

        $payment = $this->createGatewayPaymentEntity($contentToSave);

        $content['msgHash'] = $this->getHashForAuthorizeRequest($contentToSave);

        $request = array(
            'url'     => $this->getUrlDomain(),
            'method'  => 'direct',
            'content' => $content,
            'callback_url' => $input['callbackUrl'],
        );

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'       => $request,
                'gateway'       => 'wallet_payzapp',
                'payment_id'    => $input['payment']['id'],
            ]);

        $request['content'] = View::make('gateway.payzapp')
                                  ->with('request', $request)
                                  ->render();

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway']['resCode'])) and
            ($input['gateway']['resCode'] === '050'))
        {
            throw new Exception\RuntimeException(
                'Payzapp payment callback, bad merchant id had been sent. Please resolve',
                [$input['gateway']]);
        }

        assert ($input['gateway']['merTxnId'] === $input['payment']['id']);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['gateway']['merTxnId'], Action::AUTHORIZE);

        $mappedPayment = $this->getReverseMappedAttributes($payment->toArray());

        $this->verifySecureHash($input, $mappedPayment);

        $attrs = $this->getMappedAttributes($input['gateway']);
        $attrs['received'] = true;

        $pickupDataContent = array(
            'wibmoTxnId'        =>      $input['gateway']['wibmoTxnId'],
            'dataPickupCode'    =>      $input['gateway']['dataPickUpCode'],
            'merTxnId'          =>      $input['gateway']['merTxnId'],
            'merchantInfo'      =>      array(
                'merId'                 => $input['terminal']['gateway_merchant_id'],
                'merAppId'              => $input['terminal']['gateway_terminal_id'],
                'merCountryCode'        => 'IN',
            )
        );

        $serverData = $this->pickupData($pickupDataContent);

        $this->verifyPaymentCallbackResponse($serverData);

        $attrs['gateway_payment_id_2'] = $serverData['data']['pgTxnId'];

        $payment->fill($attrs);
        $payment->saveOrFail();

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'pickedup_data' => $serverData,
            ]);

        $this->verifyPaymentCallbackResponse($input['gateway']);
    }

    protected function getAuthContent($input)
    {
        $amount = $input['payment']['amount'] / 100;

        $content = array(
            'merchantInfo' => array(
                'merId'                 => $input['terminal']['gateway_merchant_id'],
                'merAppId'              => $input['terminal']['gateway_terminal_id'],
                'merCountryCode'        => 'IN',
                'merName'               => 'RazorPay',
            ),
            'transactionInfo'   => array(
                'txnAmount'             => $input['payment']['amount'],
                'txnCurrency'           => '356',
                'txnDesc'               => 'Transaction for amount: ' . $amount,
                'merTxnId'              => $input['payment']['id'],
                'merAppData'            => '',
                'supportedPaymentType'  => ['*'],
            ),
            'customerInfo' => array(
                'custEmail'             => $input['payment']['email'],
                'custMobile'            => $input['payment']['contact'],
            ),
        );

        $this->addMerchantDetailsInTest($content);

        return $content;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->setDomainType();

        $payment = $this->getRepo()->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);

        $wallet = $this->getRepo()->
                    fetchWalletByPaymentId($input['payment']['id']);

        $originalTransactionId = $wallet['gateway_payment_id_2'];

        $perform = $this->getPerformForPayment($payment);

        $content =  array(
            'pg_instance_id'                    => $this->config['live_pg_instance_id'],
            'merchant_id'                       => $input['terminal']['gateway_merchant_id'],
            'perform'                           => $perform,
            'orginal_transaction_id'            => $originalTransactionId,
            'original_merchant_reference_no'    => $input['payment']['id'],
            'login_id'                          => $this->config['pg_merchant_login_id'],
            'pgName'                            => $this->pgname,
        );

        $this->addMerchantDetailsInTest($content);

        $refundAttributes = array(
            'payment_id'            =>    $input['payment']['id'],
            'action'                =>    $this->action,
            'amount'                =>    $payment['amount'],
            'wallet'                =>    $input['payment']['wallet'],
            'email'                 =>    $payment['email'],
            'received'              =>    0,
            'contact'               =>    $payment['contact'],
            'gateway_merchant_id'   =>    $input['terminal']['gateway_merchant_id'],
            'refund_id'             =>    $input['refund']['id'],
        );

        $refund = $this->createGatewayRefundEntity($refundAttributes);

        $content['message_hash'] = 'MERCHANT-API-HTTPS:7:'.$this->getHashForRefundRequest($content);

        $responseContent =  "";
        $response = $this->postRequest($content);

        parse_str($response, $responseContent);

        $postTxnAttributes = array(
            'response_code'         =>      $responseContent['pg_error_code'],
            'response_description'  =>      $responseContent['pg_error_detail'],
            'status_code'           =>      $responseContent['status'],
            'error_message'         =>      $responseContent['pg_error_detail'],
        );

        $refund->fill($postTxnAttributes);
        $refund->saveOrFail();

        if (ResponseCode::$statusCodes[$responseContent['status']] !== 'Success')
        {
            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                [$content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED);
        }

        $successfulTxnAttributes = array(
            'gateway_payment_id_2'  =>      $responseContent['new_transaction_id'],
            'gateway_refund_id'     =>      $responseContent['new_transaction_id'],
        );

        $refund->fill($successfulTxnAttributes);
        $refund->saveOrFail();


    }

    public function verify(array $input)
    {
        parent::verify($input);

        $this->setDomainType();

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    protected function pickupData($content)
    {
        $this->addMerchantDetailsInTest($content);

        $content['msgHash'] = $this->getHashForDataPickupRequest($content);

        $content = $this->makePickUpDataRequest($content);

        return $content;
    }

    protected function makePickUpDataRequest($content)
    {
        return $this->postRequest($content, 'pickup_data');
    }

    protected function performProcessForTxn()

    {

    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $resCode = (int) $input['resCode'];

        if ($resCode === 0)
        {
            return;
        }

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $input['resCode'],
                $input['resDesc']);
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

        $payzappEntity = $this->getRepo()->fetchWalletByPaymentId(
                            $verify->input['payment']['id']);

        $status = VerifyResult::STATUS_MATCH;

        $txnResultStrings = explode("transaction_id=", $content);

        $originalTxnIdRecord = $txnResultStrings[1];

        $originalTxnIdRecord = "transaction_id=".$originalTxnIdRecord;

        parse_str($originalTxnIdRecord, $txnStatus);

        $responseCode = strtoupper(ResponseCode::$statusCodes[$txnStatus['status']]);

        if ($responseCode !== $payzappEntity['response_description'])
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $postVerifyAttributes = array(
            'response_code'         =>      $txnStatus['pg_error_code'],
            'response_description'  =>      $txnStatus['pg_error_msg'],
            'status_code'           =>      $txnStatus['status'],
            'error_message'         =>      $txnStatus['pg_error_msg'],
        );

        $payzappEntity->fill($postVerifyAttributes);
        $payzappEntity->saveOrFail();

        return $status;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $walletEntity = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);

        $verify->wallet = $walletEntity;
        $this->perform  = 'verify';

        $content =  array(
            'pg_instance_id'                    => $this->config['live_pg_instance_id'],
            'merchant_id'                       => $input['terminal']['gateway_merchant_id'],
            'perform'                           => $this->performMap[$this->perform],
            'currency_code'                     => '356',
            'transaction_type'                  => TransactionType::$codes['SALE'],
            'amount'                            => $input['payment']['amount'],
            'merchant_reference_no'             => $input['payment']['id'],
        );

        $this->addMerchantDetailsInTest($content);

        $content['message_hash'] = 'CURRENCY:7:'.$this->getHashForVerifyRequest($content);

        $content = $this->postRequest($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            array('content' => $content));

        $verify->verifyResponse = $this->response;
        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                    $input['payment']['id'], Action::AUTHORIZE);

        $mappedPayment = $this->getReverseMappedAttributes($payment->toArray());

        $verify->payment = $mappedPayment;

        return $payment;
    }

    protected function postRequest($content, $type = null)
    {
        $url = $this->getUrl($type);

        if (in_array($this->action, $this->acosaActions))
        {

            $request = array(
                'method'  => 'post',
                'url'     => 'https://' . $url,
                'content' => $content,
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']);

            $response = $this->runRequestResponseFlow($request);

            $this->response = $response;
            $content = $response->body;

        } else {

            $request = array(
                'method'  => 'post',
                'url'     => 'https://' . $url,
                'content' => json_encode($content),
                'headers' => ['Content-Type' => 'application/json']);

            $options = [];

            if($this->mode === Mode::LIVE)
            {
                $options = array('proxy'   => true);
            }

            $response = $this->runRequestResponseFlow($request, $options);

            $content = json_decode($response->body, true);

        }

        return $content;
    }

    protected function setDomainType()
    {
        if (in_array($this->action, $this->acosaActions))
        {
            $this->domainType = 'acosa';
        }
    }

    protected function getTestSecret()
    {
        $secret = parent::getTestSecret();

        if($this->domainType !== null)
        {
            return $this->config['test_pg_hash_key'];
        }

        return $secret;
    }

    protected function getLiveSecret()
    {
        return $this->input['terminal']['gateway_secure_secret'];
    }


    protected function verifySecureHash($input, $payment)
    {
        $generatedHash = $this->generateCallbackSecureHash($input, $payment);

        $hash = $input['gateway']['msgHash'];

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                                    'Failed checksum verification');
        }
    }

    protected function generateCallbackSecureHash($input, $payment)
    {
        $content = array_merge($payment, $input['gateway']);

        $content['merAppId'] = $this->getMerchantAppId($input);
        $content['merAppData'] = '';
        $content['txnCurrency'] = '356';

        $generatedHash = $this->getHashForAuthorizeResponse($content);

        return $generatedHash;
    }

    /**
     * PayZapp transactions are processed
     * at midnight : till then the void api could be used
     *              else use the refund api.
     *
     * @param payment $payment
     * @param boolean $forceRefund
     * @return string
     */
    protected function getPerformForPayment($payment, $forceRefund = false)
    {
        $now                = Carbon::now('Asia/Kolkata');
        $paymentCreatedDate = Carbon::createFromTimestamp($payment->created_at, 'Asia/Kolkata');

        if (!$forceRefund && $paymentCreatedDate->isSameDay($now))
        {
            $this->perform = 'void';
        }
        else
        {
            $this->perform = 'refund';
        }

        return $this->performMap[$this->perform];
    }

    protected function getHashForDataPickupRequest($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'wibmoTxnId',
            'dataPickupCode',
        );

        $content['wpay'] = 'wpay';

        $content = array_merge($content, $content['merchantInfo']);

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $hash = $this->getHashOfArray($orderedData);

        return $hash;
    }

    protected function getHashForAuthorizeRequest($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'merAppData',
            'txnAmount',
            'txnCurrency',
            'supportedPaymentType',
            'merName');

        $content['wpay'] = 'wpay';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    public function getHashForAuthorizeResponse($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'merAppData',
            'txnAmount',
            'txnCurrency',
            'wibmoTxnId',
            'resCode',
            'dataPickUpCode'
        );

        $content['wpay'] = 'wpay';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForRefundRequest(array $content)
    {
        $fieldsInOrder = array(
            'pg_instance_id',
            'merchant_id',
            'perform',
            'orginal_transaction_id',
            'original_merchant_reference_no',
            'login_id',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForVerifyRequest(array $content)
    {

        $fieldsInOrder = array(
            'pg_instance_id',
            'merchant_id',
            'perform',
            'currency_code',
            'amount',
            'merchant_reference_no',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function addMerchantDetailsInTest(array & $content)
    {
        if ($this->mode === Mode::LIVE)
        {
            return;
        }

        if (in_array($this->action, $this->acosaActions))
        {
            $content['merchant_id'] = $this->config['test_pg_merchant_id'];
            $content['pg_instance_id'] = $this->config['test_pg_instance_id'];
        }
        else
        {
            $content['merchantInfo']['merId'] = $this->config['test_merchant_id'];
            $content['merchantInfo']['merAppId'] = $this->config['test_merchant_app_id'];
        }
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "|");

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $str = $str . '|'.$secret.'|';

        if ($this->domainType !== null)
        {
            $hash =  base64_encode(sha1($str, true));
        }
        else
        {
            $hash =  base64_encode(hash('sha256', $str, true));
        }

        return $hash;
    }

    protected function getMerchantAppId($input)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_app_id'];
        }

        return $input['terminal']['gateway_terminal_id'];
    }

    protected function runRequestResponseFlow(array $request, array $options = [])
    {
        if ((isset($options['proxy'])) && ($options['proxy'] === true))
        {
            $request['options']['proxy'] = 'https://splunk.razorpay.com:8888';
        }

        $request['options']['timeout'] = 30;

        try
        {
            // send the request and get response
            return $this->sendGatewayRequest($request);
        }
        catch(\Requests_Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (\Gateway\Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw $e;
            }
        }
    }
}
