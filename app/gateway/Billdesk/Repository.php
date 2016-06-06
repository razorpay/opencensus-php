<?php

namespace Gateway\Billdesk;

use EE\Exception;
use Gateway\Base;

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
    
    protected function findByGatewayRefundId($gatewayRefundId)
    {
        $repo = $this->repo;

        return $repo::where('refundId', '=', $gatewayRefundId)
            ->firstOrFail();
    }
}