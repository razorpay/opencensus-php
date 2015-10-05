<?php

namespace Gateway\Wallet\Payzapp;

use Constants\Mode;
use EE\Error;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\Wallet\Base;
use Trace\Trace;
use Trace\TraceCode;
use Gateway\Mobikwik\Type;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_payzapp';

    protected $map = array(
        'custEmail'         => 'email',
        'custMobie'         => 'contact',
        'merId'             => 'gateway_merchant_id',
        'wibmoTxnId'        => 'gateway_payment_id',
        'pgTxnId'           => 'gateway_payment_id_2',
        'pgVoidTxnId'       => 'gateway_refund_id',
        'resCode'           => 'response_code',
        'resDesc'           => 'response_description',
        'pgStatusCode'      => 'status_code',
        'dataPickUpCode'    => 'reference1',
        'actionCode'        => 'reference2',
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
                'txnAmount'             => '100',
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

//        $payment = $this->createGatewayPaymentEntity($contentToSave);

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
                'request' => $request,
                'gateway' => 'wallet_payzapp',
                'payment_id' => $input['payment']['id'],
            ]);

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

        $this->($input['gateway']);

        assert ($input['gateway']['merTxnId'] === $input['payment']['id']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['gateway']['merTxnId'], Action::AUTHORIZE);

        $mappedPayment = $this->getReverseMappedAttributes($payment->toArray());

        $this->verifySecureHash($input['gateway'], $mappedPayment);

        $attrs = $this->getMappedAttributes($input['gateway']);
        $attrs['received'] = true;

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
        ;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPaymentCallbackResponse($input, $payment)
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

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                    $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        // add content here

        $this->addMerchantDetailsInTest($content);

        $content['merchantInfo']['merCountryCode'] = 'IN';
        $content['msgHash'] = $this->getHashForVerifyRequest($content);

        $content = $this->postRequest($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $verify->verifyResponse = $this->response;
        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function postRequest($content)
    {
        $request = array(
            'url' => $this->getUrl(),
            'method' => 'post',
            'content' => json_encode($request['content']));

        $response = $this->runRequestResponseFlow($request);
        $content = json_decode($response->body, true);

        return $content;
    }

    protected function verifySecureHash($input, $payment)
    {
        $hash = $input['msgHash'];

        $content = array_merge($payment, $input);

        $generatedHash = $this->getHashForAuthorizeResponse($content);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                                    'Failed checksum verification');
        }
    }

    protected function getHashForVerifyRequest($content)
    {
        $fieldsInOrder = array(
            'wpay',
            'merId',
            'merAppId',
            'merTxnId',
            'merAppData',
            'wibmoTxnId',
            'dataPickUpCode',
        );

        $content['wpay'] = 'wpay';

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
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

        $t =  base64_encode(hash('sha256', $str, true));

        return $t;
    }
}
