<?php

namespace Gateway\Wallet\Base;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Wallet';

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
}
