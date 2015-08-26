<?php

namespace Gateway\Mobikwik;

use Constants\Mode;
use EE\Error;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Trace\Trace;
use Trace\TraceCode;
use Gateway\Mobikwik\Type;

class Gateway extends Base\Gateway
{
    protected $gateway = 'mobikwik';

    protected $sortRequestContent = false;

    public function authorize(array $input)
    {
        parent::authorize($input);
        $content = array(
            'email'       => $input['payment']['email'],
            'amount'      => $input['payment']['amount'] / 100,
            'cell'        => $input['payment']['contact'],
            'orderid'     => $input['payment']['id'],
//            'merchantname'  => $input['terminal']['gateway_terminal_id'],
            'mid'         => $input['terminal']['gateway_merchant_id'],
            'redirecturl' => $input['callbackUrl'],
        );

        if ($this->mode === Mode::TEST)
        {
            $this->addTerminalDetailsInTest($content);
        }

        $payment = $this->createGatewayPaymentEntity($content);
        $content['checksum'] = $this->getHashForAuthorizeRequest($content);

        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content,
        );

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifySecureHash($input['gateway']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['gateway']['orderid'], Action::AUTHORIZE);
        $input['received'] = 1;
        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $content['mid'] = $this->getMobikwikMerchantId($input['terminal']);
        $content['orderid'] = $input['payment']['id'];

        $content['checksum'] = $this->getHashForVerifyRequest(
            $content['mid'], $content['orderid']);

        $content = http_build_query($content);

        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);

        $response = $this->sendGatewayRequest($request);

        $content = (array)simplexml_load_string($response->body);

        if ($content['statuscode'] !== '0')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                'Payment verification failed with statuscode: ' . $content['statuscode']);
        }
        $this->verifySecureHashForQueryRequest($content);

    }

    public function refund(array $input)
    {
        parent::refund($input);
        $content = [];
        $content['mid'] = $this->getMobikwikMerchantId($input['terminal']);
        $this->addTestMerchantIdIfTestMode($content);


        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content['txid'] = $input['payment']['id'];
        $content['email'] = $payment['email'];
        $content['amount'] = (string) ($input['refund']['amount'] / 100);

        $content['checksum'] = $this->getHashForRefundRequest($content['mid'],
                                                              $content['txid'],
                                                              $content['email'],
                                                              $content['amount']);
        if($input['refund']['amount'] < $payment['amount'])
        {
            $content['ispartial'] = 'yes';
        }
        $refund = $this->createGatewayRefundEntity($content, $input);

        $content = http_build_query($content);
        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'post',
            'content' => $content);


        $response = $this->sendGatewayRequest($request);
        $content = (array)simplexml_load_string($response->body);
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

    protected function addTerminalDetailsInTest(array & $content)
    {
        $content['merchantname'] = 'TestMerchant';
        $content['mid'] = $this->getTestMerchantId();
    }

    protected function getTestMerchantId()
    {
        return 'MBK9002';
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

    protected function getHashForRefundRequest($mid, $orderId, $email, $amount)
    {

        $str = "'" . $mid . "''" . $orderId . "''" . $email . "''" . $amount . "'";

        return $this->getHashOfString($str);
    }

    protected function getHashForAuthorizeRequest($content)
    {

        $str = "'" . $content['cell'] . "''" . $content['email'] . "''" . $content['amount'] . "''" . $content['orderid'] . "''" . $content['redirecturl'] . "''" . $content['mid'] . "'";

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
        $code = (int)$input['gateway']['statuscode'];

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

    public function authorizeFailed(array $input)
    {
        $e = null;

        try
        {
            $this->verify($input);
        } catch (Exception\PaymentVerificationException $e)
        {
            ;
        }

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not');
        }

        $verify = $e->getVerifyObject();

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true)
        )
        {
            $payment = $verify->payment;
            $payment->fill($verify->verifyResponseContent);
            $payment->saveOrFail();
        } else
        {
            throw new Exception\LogicException(
                'Should not have reached here');
        }

        return true;
    }

}

