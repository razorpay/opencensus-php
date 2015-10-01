<?php

namespace Gateway\Wallet\Payzapp;

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
    protected $gateway = 'wallet_payzapp';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = array(
            'merchantInfo' => array(
                'merId'                 => $input['terminal']['gateway_merchant_id'],
                'merAppId'              => $input['terminal']['gateway_terminal_id'],
                'merCountryCode'        => 'IN',
                'merName'               => '',
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

        $contentToSave = [];

        foreach ($content as $category => $info)
        {
            foreach ($info as $key => $value)
            {
                $contentToSave[$key] = $value;
            }
        }

        $payment = $this->createGatewayPaymentEntity($contentToSave);

        $content['msgHash'] = $this->getHashForAuthorizeRequest($contentToSave);

        $request = array(
            'url'     => $this->getUrl($this->action),
            'method'  => 'ajax',
            'content' => $content,
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

        return $this->getHashOfArray($content);
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "|");

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $hash = $secret = $this->getSecret();

        $str = $str . '|'.$secret.'|';

        return base64_encode(sha1($str));
    }
}

