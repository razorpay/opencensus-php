<?php

namespace Gateway\Mobikwik;

use Constants\Mode;
use EE\Error;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Trace\Trace;
use Trace\TraceCode;
use Gateway\Mobikwik\Type;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

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
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request' => $request,
                'gateway' => 'mobikwik',
                'payment_id' => $input['payment']['id'],
            ]);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);
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
        $this->response = $response;
        $content = (array)simplexml_load_string($response->body);

        $this->verifySecureHashForQueryRequest($content);

        unset($content['checksum']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'request' => $verify->verifyResponseBody,
                'gateway' => 'mobikwik',
                'payment_id' => $input['payment']['id'],
            ]);

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
        $content = $verify->verifyResponseContent;


        $status = VerifyResult::STATUS_MATCH;

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;
            // Could be the case where the transaction didn't even hit mobikwik
            if (($payment['received'] === false) and
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
            //Gateway success , api success
            if ($payment['statuscode'] === Status::SUCCESS)
            {
                $verify->apiSuccess = true;
            }
            else if ($payment['statuscode'] !== Status::SUCCESS)
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }

        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        if (($verify->match === true) and
            ($payment['received'] === false))
        {
            $payment->fill($content);
            $payment->saveOrFail();
        }

        return $status;
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
                                                              $content['amount'],
                                                              $content['email']);
        if($input['refund']['amount'] < $input['payment']['amount'])
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

    protected function getHashForRefundRequest($mid, $orderId, $amount, $email)
    {

        $str = "'" . $mid . "''" . $orderId . "''" . $amount . "''" . $email . "'";

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

}

