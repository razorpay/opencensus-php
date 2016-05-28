<?php

namespace Gateway\Mobikwik;

use Constants\Mode;
use EE\Error;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
// use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Trace\Trace;
use Trace\TraceCode;
use Gateway\Mobikwik\Type;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = 'mobikwik';

    protected $sortRequestContent = false;

    protected $canRunOtpFlow = true;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway']['type'])) and
            ($input['gateway']['type'] === 'otp'))
        {
            return $this->callbackOtpSubmit($input);
        }


        return $this->callbackNormalFlow($input);
    }

    protected function callbackNormalFlow(array $input)
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $this->verifySecureHash($input['gateway']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
                            $input['gateway']['orderid'], Action::AUTHORIZE);

        $input['gateway']['received'] = 1;

        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => 'mobikwik',
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifyPaymentCallbackResponse($input);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);
        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content' => $content,
                'gateway' => 'mobikwik',
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifySecureHashForQueryRequest($content);

        unset($content['checksum']);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;

            // Could be the case where the transaction didn't even hit mobikwik
            if (($payment === null) and
                (($input['payment']['status'] === 'failed') or
                 ($input['payment']['status'] === 'created')))
            {
                $verify->apiSuccess = false;
            }
            else if (($payment['received'] === false) and
                     (($payment['statuscode'] === null) or
                      ($payment['statuscode'] !== Status::SUCCESS)))
            {
                $verify->apiSuccess = false;
            }
            else if ($payment['statuscode'] === Status::SUCCESS)
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = true;
            }
        }
        else if ($content['statuscode'] === Status::SUCCESS)
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

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    protected function saveVerifyContentIfNeeded($payment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if ($payment === null)
        {
            $walletAttributes = $this->getWalletContentFromVerify($payment, $content);

            $payment = $this->createGatewayPaymentEntity($walletAttributes);
        }
        else if ($payment['received'] === false)
        {
            $payment->fill($content);
            $payment->saveOrFail();
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestContentArray($input);

        $refund = $this->createGatewayRefundEntity($content, $input);

        $content = http_build_query($content);
        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $content['received'] = 1;
        $refund->fill($content)->saveOrFail();

        if ($content['statuscode'] !== '0')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content['statuscode'],
                $content['statusmessage']);
        }
    }

    public function checkExistingUser($input)
    {
        $this->action($input, Action::CHECK_USER);

        $content = $this->getCheckExistingUserRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $content['received'] = 1;

        $code = $content['statuscode'];

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['statuscode'],
                $content['statusdescription']);
        }
    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $content = array(
            'amount'    => $input['payment']['amount'] / 100,
            'cell'      => $this->getFormattedContact($input['payment']['contact']),
            'merchantname' => $input['merchant']['billing_label'],
            'mid'       => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'   => MessageCode::OTP_GENERATE,
            'tokentype' => '0',
        );

        $content['checksum'] = $this->getHashOfArray($content);
        $content['merchantAlias'] = $input['merchant']['billing_label'];

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['statuscode'];

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['statuscode'],
                $content['statusdescription']);
        }
    }

    public function callbackOtpSubmit($input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $content = array(
            'amount'        => (string) ($input['payment']['amount'] / 100),
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'comment'       => 'Order id - ' . $input['payment']['public_id'],
            'merchantname'  => $input['merchant']['billing_label'],
            'mid'           => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'       => MessageCode::OTP_SUBMIT,
            'orderid'       => $input['payment']['id'],
            'otp'           => $input['gateway']['otp'],
            'txntype'       => 'debit',
        );

        $content['checksum'] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $responseArray = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $responseArray);

        $code = $responseArray['statuscode'];

        if ($responseArray['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $responseArray['statuscode'],
                $responseArray['statusdescription']);
        }

        $content['email'] = $input['payment']['email'];
        $content['received'] = true;

        if (isset($responseArray['statuscode']))
        {
            $content['statuscode'] = $responseArray['statuscode'];
        }

        if (isset($responseArray['statusdescription']))
        {
            $content['statusmessage'] = $responseArray['statusdescription'];
        }

        $this->action = Action::AUTHORIZE;

        $this->createGatewayPaymentEntity($content);
    }

    protected function getAuthorizeRequestContent($input)
    {
        $content = array(
            'email'         => $input['payment']['email'],
            'amount'        => $input['payment']['amount'] / 100,
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'orderid'       => $input['payment']['id'],
            'merchantname'  => $input['merchant']['billing_label'],
            'mid'           => $input['terminal']['gateway_merchant_id'],
            'redirecturl'   => $input['callbackUrl'],
        );

        if ($this->mode === Mode::TEST)
        {
            $this->addTerminalDetailsInTest($content);
        }

        $payment = $this->createGatewayPaymentEntity($content);
        $content['checksum'] = $this->getHashForAuthorizeRequest($content);
        $content['merchantAlias'] = $input['merchant']['billing_label'];

        return $content;
    }

    protected function getRefundRequestContentArray($input)
    {
        $content = [];

        $content['mid'] = $this->getMobikwikMerchantId($input['terminal']);

        $this->addTestMerchantIdIfTestMode($content);

        // $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
        //                         $input['payment']['id'], Action::AUTHORIZE);

        $content['txid'] = $input['payment']['id'];
        $content['email'] = $input['payment']['email'];
        $content['amount'] = (string) ($input['refund']['amount'] / 100);

        $content['checksum'] = $this->getHashForRefundRequest(
                                        $content['mid'],
                                        $content['txid'],
                                        $content['amount'],
                                        $content['email']);

        if ($input['refund']['amount'] < $input['payment']['amount'])
        {
            $content['ispartial'] = 'yes';
        }

        return $content;
    }

    protected function getCheckExistingUserRequestContent($input)
    {
        $content = array(
            'action'        => 'existingusercheck',
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'merchantname'  => 'Razorpay',
            'mid'           => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'       => '500',
        );

        $content['checksum'] = $this->getHashForCheckExistingUserRequest($content);

        return $content;
    }

    protected function getVerifyRequestArray($input)
    {
        $content['mid'] = $this->getMobikwikMerchantId($input['terminal']);

        $content['orderid'] = $input['payment']['id'];

        $content['checksum'] = $this->getHashForVerifyRequest(
                                    $content['mid'], $content['orderid']);

        $content = http_build_query($content);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    protected function addTerminalDetailsInTest(array & $content)
    {
        $content['merchantname'] = 'TestMerchant';
        $content['mid'] = $this->getTestMerchantId();
    }

    protected function getMobikwikMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getPaymentHash($content)
    {
        $fieldsInOrder = array(
            'cell',
            'email',
            'amount',
            'orderid',
            'redirecturl',
            'mid');

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($content);
    }

    protected function verifySecureHash($content)
    {
        $fieldsInOrder = array(
            'statuscode',
            'orderid',
            'amount',
            'statusmessage',
            'mid',
            'refid'
        );

        $hash = $content['checksum'];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generatedHash = $this->getHashOfArray($content);
        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function verifySecureHashForQueryRequest($content)
    {
        $str = "'" . $content['statuscode'] . "'" .
            "'" . $content['orderid'] . "'" .
            "'" . $content['refid'] . "'" .
            "'" . $content['amount'] . "'" .
            "'" . $content['statusmessage'] . "'" .
            "'" . $content['ordertype'] . "'";

        $generatedHash = $this->getHashOfString($str);

        $hash = $content['checksum'];

        if ($generatedHash !== $hash)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::BAD_REQUEST_ERROR);
        }
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function getHashForVerifyRequest($mid, $orderId)
    {
        $str = "'" . $mid . "''" . $orderId . "'";

        return $this->getHashOfString($str);
    }

    protected function getStringToHash($content, $glue = '')
    {
        return "'" . parent::getStringToHash($content, "''") . "'";
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "''");

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return strtolower(hash_hmac('sha256', $str, $secret, false));
    }

    protected function getHashForRefundRequest($mid, $orderId, $amount, $email)
    {
        $str = "'" . $mid . "''" . $orderId . "''" . $amount . "''" . $email . "'";

        return $this->getHashOfString($str);
    }

    protected function getHashForAuthorizeRequest($content)
    {
        $str = "'" .
            $content['cell']        . "''" .
            $content['email']       . "''" .
            $content['amount']      . "''" .
            $content['orderid']     . "''" .
            $content['redirecturl'] . "''" .
            $content['mid'] . "'";

        return $this->getHashOfString($str);
    }

    protected function getHashForCheckExistingUserRequest($content)
    {
        $str = "'" .
            $content['action']          . "''" .
            $content['cell']            . "''" .
            $content['merchantname']    . "''" .
            $content['mid']             . "''" .
            $content['msgcode'] . "'";

        return $this->getHashOfString($str);
    }

    protected function addTestMerchantIdIfTestMode(array & $content)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['mid'] = $this->getTestMerchantId();
        }
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr['txntype'] = Type::SALE;
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['orderid']);
        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayRefundEntity($attributes, $input)
    {
        $attributes['refund_id'] = $input['refund']['id'];
        $attributes['payment_id'] = $input['payment']['id'];

        $refund = $this->createGatewayEntity($attributes);

        return $refund;
    }


    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];
        $code = (int) $input['gateway']['statuscode'];

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $input['gateway']['statuscode'],
                $input['gateway']['statusmessage']);
        }
    }

    protected function getUrlDomain()
    {
        if ($this->mode === Mode::LIVE)
        {
            $apiDomainActionList = array(
                Action::CHECK_USER,
                Action::OTP_GENERATE,
                Action::OTP_SUBMIT);

            if (in_array($this->action, $apiDomainActionList))
            {
                $this->domainType = 'api';
            }
        }

        return parent::getUrlDomain();
    }

    protected function xmlToArray($xml)
    {
        $e = null;
        $res = null;

        try
        {
            $res = simplexml_load_string($xml);
        }
        catch (\Exception $e)
        {
            $res = false;
        }

        if ($res === false)
        {
            $this->trace->error(
                TraceCode::GATEWAY_REFUND_ERROR,
                ['xml' => $xml]);

            throw new Exception\RuntimeException(
                'Failed to convert xml to array',
                ['xml' => $xml],
                $e);
        }

        return (array) $res;
    }


    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = array(
            'mid'      => $this->getMobikwikMerchantId($this->input['terminal']),
            'amount'   => (string) ($this->input['payment']['amount'] / 100),
            'email'    => $this->input['payment']['email'],
            'cell'     => $this->getFormattedContact($this->input['payment']['contact']),
            'msgcode'  => MessageCode::OTP_SUBMIT,
            'orderid'  => $this->input['payment']['id'],
            'received' => true
        );

        if (isset($content['statuscode']))
        {
            $contentToSave['statuscode'] = $content['statuscode'];
        }

        if (isset($responseArray['statusmessage']))
        {
            $contentToSave['statusmessage'] = $content['statusmessage'];
        }

        return $contentToSave;
    }

    protected function getFormattedContact($contact)
    {
        return substr($contact, -10);
    }
}
