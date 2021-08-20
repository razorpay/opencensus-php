<?php

namespace RZP\Gateway\Hitachi;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'hitachi';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::PAYMENT_ID => 'sometimes|string|max:18',
        Entity::ACTION     => 'sometimes|alpha|max:10',
        Entity::REFUND_ID  => 'sometimes|string|max:19',
        Entity::REQUEST_ID => 'sometimes|string|max:14',
        Entity::RRN        => 'sometimes|string|max:12',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::REFUND_ID,
    ];

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId($refundId)
    {
        $refundEntities = $this->newQuery()
                               ->where(Entity::REFUND_ID, '=', $refundId)
                               ->where(Entity::ACTION, '=', Base\Action::REFUND)
                               ->where(Entity::RESPONSE_CODE, '=', Status::SUCCESS_CODE)
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

    public function getByRrn(string $rrn)
    {
        return $this->newQuery()
                    ->where(Entity::RRN, $rrn)
                    ->first();
    }
}
