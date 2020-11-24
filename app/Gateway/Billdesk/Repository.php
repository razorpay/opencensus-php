<?php

namespace RZP\Gateway\Billdesk;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;

use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'billdesk';

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

    public function getSuccessfulRefundRecordForThePayment($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('ProcessStatus', '=', QueryStatus::Y)
                    ->where('RequestType', '=', '0410')
                    ->where('action', '=', Base\Action::REFUND)
                    ->where('received', '=', '1')
                    ->get();
    }

    public function findRefundByRefundId($refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where('refund_id', '=', $refundId)
                                ->whereNull('ErrorCode')
                                ->get();

        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //

        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple successful refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id' => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }
}
