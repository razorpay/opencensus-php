<?php

namespace RZP\Gateway\Billdesk;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Billdesk';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'TxnReferenceNo'                => 'sometimes|max:50',
        'received'                      => 'sometimes|in:0,1',
        'AuthStatus' 					=> 'sometimes|max:5',
        'RefStatus' 	 				=> 'sometimes|max:5',
        'RefundId'                      => 'sometimes|string',
        'BankReferenceNo'               => 'sometimes|string',
    );

    public function findByGatewayRefundId($gatewayRefundId)
    {
        return $this->newQuery()
                    ->where('refundId', '=', $gatewayRefundId)
                    ->firstOrFail();
    }

    public function findByRefundId($refundId)
    {
        return $this->newQuery()
                    ->where(Entity::REFUND_ID, '=', $refundId)
                    ->first();
    }
}