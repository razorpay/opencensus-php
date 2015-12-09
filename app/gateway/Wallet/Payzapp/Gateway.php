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
use ResponseCode;
use Url;
use View;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_payzapp';

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

    protected $perform = array(
        'void'              => 'processMerchantAPI#DirectVoid',
        'refund'            => 'processMerchantAPI#Refund',
    );

    protected $acosaActions = array(
        ACTION::VERIFY, ACTION::REFUND
    );
    public function authorize(array $input)
    {
        parent::authorize($input);

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
                'txnDesc'               => 'Transaction for amount' . $amount,
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

        $serverData = $this->pickupData($input);
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
            ]);

        $this->verifyPaymentCallbackResponse($input['gateway']);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndAction(
                        $input['payment']['id'], Action::AUTHORIZE);


        // step process
        // have to login user
        // have to perform refund

        // URL not sure
        // $login_content = $this->prepareLoginContent($input);

        // $login_response = $this->postRequest($login_content, 'basic');

        // $login_response = parse_str($login_response);

        // if (ResponseCode::$login_response['status'] !== 'Sucess')
        // {
        //     $this->trace->error(
        //         TraceCode::PAYMENT_REFUND_FAILURE,
        //         [$content]);

        //     throw new Exception\GatewayErrorException(
        //         ErrorCode::BAD_REQUEST_REFUND_FAILED);
        // }

        // URL confirmed
        $refund_content = $this->prepareRefundContent($login_response);

        $refund_response = $this->postRequest($login_content, 'basic');

        $refund_response = parse_str($refund_response);

        if (ResponseCode::$refund_response['status'] !== 'Sucess')
        {
            $this->trace->error(
                TraceCode::PAYMENT_REFUND_FAILURE,
                [$content]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED);
        }

        //Logout.

        //save refund


    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    public function pickupData($input)
    {
        $content = array(
            'wibmoTxnId'        =>      $input['gateway']['wibmoTxnId'],
            'dataPickupCode'    =>      $input['gateway']['dataPickUpCode'],
            'merTxnId'          =>      $input['gateway']['merTxnId'],
            'merchantInfo'      =>      array(
                'merId'                 => $input['terminal']['gateway_merchant_id'],
                'merAppId'              => $input['terminal']['gateway_terminal_id'],
                'merCountryCode'        => 'IN',
            )
        );

        $this->addMerchantDetailsInTest($content);

        $content['msgHash'] = $this->getHashForDataPickupRequest($content);

        $content = $this->postRequest($content, 'pickup_data');

        return $content;
    }

    protected function prepareLoginContent($input)
    {
        $login_request_content = array(
            'pg_instance_id'    => $input['terminal'][''],
            'merchant_id'       => $input['terminal']['gateway_merchant_id'],
            'perform'           => $this->action_map['login'],
            'login_id'          => $input['terminal']['gateway_login_id'],
            'password'          => $input['terminal']['gateway_password'],
            'pgName'            => 'RazorPay',
        );

        $login_request_content['message_hash'] = $this->getHashForLoginRequest($loginRequestContent);

        return $login_request_content;
    }

    protected function prepareRefundContent($input, $login_context)
    {
        $refund_request_content =  array(
            'pg_instance_id'                    => $input['terminal'][''],
            'merchant_id'                       => $input['terminal']['gateway_merchant_id'],
            'perform'                           => $this->action_map['refund'],
            'orginal_transaction_id'            => '',
            'original_merchant_reference_no'    => '',
            'login_id'                          => $input['terminal']['gateway_login_id'],
            'login_context'                     => $login_context,
        );

        $refund_request_content['message_hash'] = $this->getHashForRefundRequest($refund_request_content);

        return $refund_request_content;
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

        $status = VerifyResult::STATUS_MATCH;

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        if (($verify->match === true) and
            ($payment['received'] === false))
        {
            unset(
                $content['TxnAmount'],
                $content['BankID'],
                $content['ItemCode']);

            $payment->fill($content);
            $payment->saveOrFail();
        }

        return $status;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        // add content here

        $content = array(
            'wibmoTxnId'        => $payment['wibmoTxnId'],
            'dataPickupCode'    => $payment['dataPickUpCode'],
            'merTxnId'          => $input['payment']['id']);

        $this->addMerchantDetailsInTest($content);

        $content['merchantInfo']['merCountryCode'] = 'IN';
        $content['msgHash'] = $this->getHashForDataPickupRequest($content);

        $content = $this->postRequest($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

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
                'headers' => ['Content-Type' => 'multipart/form-data']);

            $response = $this->runRequestResponseFlow($request);

            $content = $response->body;

        } else {

            $request = array(
                'method'  => 'post',
                'url'     => 'https://' . $url,
                'content' => json_encode($content),
                'headers' => ['Content-Type' => 'application/json']);

            $response = $this->runRequestResponseFlow($request);

            $content = json_decode($response->body, true);

        }

        return $content;
    }

    protected function getUrl($type = null)
    {
        if (in_array($this->action, $this->acosaActions))
        {
            $this->domainType = 'acosa';
        }

        $url = parent::getUrl($type);

        return $url;
    }

    protected function verifySecureHash($input, $payment)
    {
        $hash = $input['gateway']['msgHash'];

        $content = array_merge($payment, $input['gateway']);

        $content['merAppId'] = $this->getMerchantAppId($input);
        $content['merAppData'] = '';
        $content['txnCurrency'] = '356';

        $generatedHash = $this->getHashForAuthorizeResponse($content);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                                    'Failed checksum verification');
        }
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

    protected function getHashForAuthorizeResponse($content)
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
            'resCode',
            'dataPickUpCode'
        );

        $content['wpay'] = 'wpay';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForLoginRequest($content){

        $fieldsInOrder = array(
            'pg_instance_id',
            'merchant_id',
            'perform',
            'login_id',
            'pgName',
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForRefundRequest(array $content){

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

    protected function addMerchantDetailsInTest(array & $content)
    {
        if ($this->mode === Mode::LIVE)
        {
            return;
        }

        $content['merchantInfo']['merId'] = $this->config['test_merchant_id'];
        $content['merchantInfo']['merAppId'] = $this->config['test_merchant_app_id'];
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

        $hash =  base64_encode(hash('sha256', $str, true));

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

    protected function runRequestResponseFlow(array $request)
    {
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
