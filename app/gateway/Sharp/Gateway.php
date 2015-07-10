<?php

namespace Gateway\Sharp;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'sharp';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $baseUrl = \Http\Route::getUrlWithPublicAuth('mock_sharp_payment');

        $content = array(
            'action' => 'authorize',
            'amount' => $input['payment']['amount'],
            'method' => $input['payment']['method'],
            'payment_id' => $input['payment']['id'],
            'callback_url' => $input['callbackUrl'],
        );

        $url = $baseUrl . '&' . http_build_query($content);

        $request = array(
            'url' => $url,
            'method' => 'get'
        );

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ((isset($input['gateway']['status']) === false) or
            ($input['gateway']['status'] !== 'authorized'))
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['TxnRefNo']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    public function setMode($mode)
    {
        assert ($mode === Mode::TEST);

        parent::setMode($mode);
    }
}
