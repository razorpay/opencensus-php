<?php

namespace Gateway\Wallet\Payumoney;

use Constants\Mode;
use EE\Error;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Wallet\Payumoney\Action;
use Gateway\Base\AuthorizeFailed;
use Gateway\Base\Verify;
use Gateway\Base\VerifyResult;
use Gateway\Wallet\Base;
use Models\Payment\Core;
use Trace\Trace;
use Trace\TraceCode;
use Carbon\Carbon;
use View;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_payumoney';

    protected $sortRequestContent = false;

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
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp'))
        {
            $this->callbackOtpSubmit($input);

            return $this->useWallet($input);
        }
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
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

        if ($content['status'] !== Status::SUCCESS)
        {
            $verify->apiSuccess = false;
        }
        else
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

        if ($content['status'] === Status::SUCCESS)
        {
            $wallet = $this->getWalletContentFromVerify($payment, $content);

            $payment->fill($wallet);
            $payment->saveOrFail();
        }

        return $verify->status;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

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

    public function checkExistingUser($input)
    {

    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::REGISTER_USER);

        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['status'];

        if ($code !== Status::SUCCESS)
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['status'],
                $content['message']);
        }
    }

    public function callbackOtpSubmit($input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['status'];

        if (($content['status'] !== Status::SUCCESS) or
            (isset($content['result']['body']['access_token']) === false))
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                $content['status'],
                $content['message']);
        }

        $this->accessToken = $content['result']['body']['access_token'];
    }

    public function useWallet($input)
    {
        $this->action($input, Action::AUTHORIZE);

        $request = $this->getUseWalletRequestArray($input);

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
            'mobile'   => $input['payment']['contact'],
            'status'   => $content['status'],
            'amount'   => $input['payment']['amount'],
            'txnId'    => $content['result'],
            'message'  => $content['message'],
            'received' => true
        );

        $this->createGatewayPaymentEntity($contentToSave);
    }

    protected function getRefundRequestArray($input)
    {
        $content = [];

        $wallet = $this->getRepo()->fetchWalletByPaymentId($input['payment']['id']);

        $content =  array(
            'merchantKey'   => $this->getMerchantId($input['terminal']),
            'paymentId'     => $wallet['gateway_payment_id'],
            'refundAmount'  => (string) ($input['refund']['amount'] / 100)
        );

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => $this->getAuthHeader($input['terminal'])
        );

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getUseWalletRequestArray($input)
    {
        $content = array(
            'key'                   => $this->getMerchantId($input['terminal']),
            'totalAmount'           => (string) ($input['payment']['amount'] / 100),
            'client_id'             => $this->getClientId($input['terminal']),
            'merchantTransactionId' => $input['payment']['id'],
        );

        $content['hash'] = $this->getHashForUseWallet($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = array(
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken
        );

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
            'received'              =>  1,
            'contact'               =>  $input['payment']['contact'],
            'gateway_merchant_id'   =>  $this->getMerchantId($input['terminal']),
            'refund_id'             =>  $input['refund']['id'],
            'response_code'         =>  '',
            'response_description'  =>  $response['message'],
            'status_code'           =>  $response['status'],
            'error_message'         =>  '',
            'gateway_refund_id'     =>  $response['result']
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
            'mobile'    => $input['payment']['contact'],
            'client_id' => $this->getClientId($input['terminal'])
        );

        $content['hash'] = $this->getHashForRegisterUser($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $content = array(
            'email'         => $input['payment']['email'],
            'mobile'        => $input['payment']['contact'],
            'client_id'     => $this->getClientId($input['terminal']),
            'otp'           => $input['gateway']['otp']
        );

        $content['hash'] = $this->getHashForVerifyUser($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

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

        $content['key']     = $this->getMerchantId($this->input['terminal']);

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

    protected function getHashForUseWallet($content)
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
        return strtolower(hash('sha512', $str, false));
    }

    protected function getWalletContentFromVerify($payment, array $response)
    {
        $content = $response['result'][0];

        $status = $content['status'] === 'success' ? Status::SUCCESS : Status::FAILURE;

        $wallet = array(
            'gateway_payment_id'    => $content['paymentId'],
            'status'                => $status
        );

        if (isset($payment['payment_id']) === false)
        {
            $wallet['payment_id'] = $content['merchantTransactionId'];
        }

        if (isset($payment['amount']) === false)
        {
            $wallet['amount'] = $content['amount'];
        }

        return $wallet;
    }
}

