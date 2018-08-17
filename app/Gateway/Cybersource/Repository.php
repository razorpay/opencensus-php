<?php

namespace RZP\Gateway\Cybersource;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'cybersource';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID   => 'sometimes|string|size:14',
        Entity::REFUND_ID    => 'sometimes|string|size:14',
        Entity::RECEIVED     => 'sometimes|boolean',
        Entity::REF          => 'sometimes|string',
        Entity::CAPTURE_REF  => 'sometimes|string'
    );

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }

    public function findSuccessfulCapturedEntity($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::CAPTURE)
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->first();
    }

    public function findSuccessfulTxnByActionAndRef(string $action, $ref)
    {
        return $this->newQuery()
                    ->where(Entity::REF, '=', $ref)
                    ->where(Entity::ACTION, '=', $action)
                    ->where(Entity::REASON_CODE, '=', Result::SUCCESS)
                    ->first();
    }

    public function findSuccessfulRefundByRefundId($refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where(Entity::REFUND_ID, '=', $refundId)
                                ->where(Entity::ACTION, '=', Cybersource\Action::REFUND)
                                ->where(Entity::REASON_CODE, '=', Result::SUCCESS)
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
