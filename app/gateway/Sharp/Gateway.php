<?php

namespace Gateway\Sharp;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base;
use Gateway\Kotak;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'sharp';

    public function authorize(array $input)
    {
        parent::authorize($input);

        return;

        $request = array(
            'url' => $url,
            'method' => 'get'
        );

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);
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
