<?php

namespace RZP\Gateway\FirstData;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'first_data';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID             => 'sometimes|string|max:18',
        Entity::REFUND_ID              => 'sometimes|string|max:19',
        Entity::ACTION                 => 'sometimes|alpha|max:10',
        Entity::CAPS_PAYMENT_ID        => 'sometimes|alpha_num|size:14',
        Entity::GATEWAY_TRANSACTION_ID => 'sometimes|digits_between:1,20',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::REFUND_ID,
    ];

    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::CAPTURE)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId(string $refundId)
    {
        $refundActions = [Base\Action::REFUND, Base\Action::REVERSE];

        $failStates = [Status::FAILED, Status::VOIDED];

        $refundEntities =  $this->newQuery()
                                ->where(Entity::REFUND_ID, '=', $refundId)
                                ->whereIn(Entity::ACTION, $refundActions)
                                ->whereNotIn(Entity::STATUS, $failStates)
                                ->get();
        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //
        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id'       => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }

    public function findPaymentIdsBetween(int $start, int $end)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$start, $end])
                    ->where(Entity::ACTION, '=', Action::AUTHORIZE)
                    ->pluck(Entity::PAYMENT_ID);
    }
}
