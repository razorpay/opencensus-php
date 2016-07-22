<?php

namespace RZP\Gateway\Ebs;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'ebs';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'TxnReferenceNo'                => 'sometimes|max:50',
        'received'                      => 'sometimes|in:0,1',
        'AuthStatus'                    => 'sometimes|max:5',
        'RefStatus'                     => 'sometimes|max:5',
        'RefundId'                      => 'sometimes|string',
        'BankReferenceNo'               => 'sometimes|string',
    );

    public function findByGatewayRefundId($gatewayRefundId)
    {
        $repo = $this->repo;

        return $repo::where('refundId', '=', $gatewayRefundId)
                    ->firstOrFail();
    }

    public function findByEbsPaymentIdAndActionOrFail($paymentId, $action)
    {
        return $this->newQuery()
            ->where('ebs_payment_id', '=', $paymentId)
            ->where('action', '=', $action)
            ->firstOrFail();
    }
}
