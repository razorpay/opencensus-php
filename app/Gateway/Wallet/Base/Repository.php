<?php

namespace Gateway\Wallet\Base;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Wallet';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID    => 'sometimes|string|min:14|max:18',
        Entity::WALLET        => 'sometimes|in:payumoney,payzapp'
    );

    public function fetchGatewayPaymentId2ByPaymentId($paymentId)
    {
        $repo = $this->repo;

        return $repo::where('payment_id' , '=', $paymentId)
                    ->lists('gateway_payment_id_2');
    }

    public function fetchWalletByPaymentId($paymentId)
    {
        $repo = $this->repo;

        return $repo::where('payment_id' , '=', $paymentId)
                    ->first();
    }

    public function findByGatewayRefundId($gatewayRefundId)
    {
        $repo = $this->repo;

        return $repo::where('gateway_refund_id', '=', $gatewayRefundId)
                    ->firstOrFail();
    }
}
