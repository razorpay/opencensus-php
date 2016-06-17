<?php

namespace Gateway\Cybersource;

use EE\Exception;
use Gateway\Cybersource\Payment;
use Gateway\Cybersource;
//use Gateway\Cybersource\Payment\Action;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Cybersource';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'auth'                          => 'sometimes|string|max:6',
        'gateway_transaction_id'        => 'sometimes|numeric|digits:16',
        'ref'                           => 'sometimes|numeric|digits:12');

    public function retrieveByPaymentIdAndStatus($id, $status)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                  ->where('status', '=', $status)
                  ->firstOrFail();
    }

    public function retrieveByPaymentId($id)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $id)
                  ->firstOrFail();
    }
}
