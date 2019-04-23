<?php

namespace RZP\Gateway\Isg;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'isg';

    public function fetchByBankReferenceNumber($transactionId)
    {
        return $this->newQuery()
                    ->where('bank_reference_no',  '=', $transactionId)
                    ->firstOrFail();
    }
    public function findSuccessfulRefundByRefundId($refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where(Entity::REFUND_ID, '=', $refundId)
                                ->where(Entity::ACTION, '=', Base\Action::REFUND)
                                ->where(Entity::STATUS_CODE, '=', Status::APPROVED)
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
