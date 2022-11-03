<?php

namespace RZP\Models\Payout\DualWrite;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\IdempotencyKey\Entity;
use RZP\Models\Payout\Entity as PayoutEntity;

class IdempotencyKey extends Base
{
    public function dualWritePSPayoutIdempotencyKey(string $id)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_IDEMPOTENCY_KEY_INIT,
            ['payout_id' => $id]
        );

        $psPayoutIdempotencyKey = $this->getAPIPayoutIdempotencyKeyFromPayoutService($id);

        if (empty($psPayoutIdempotencyKey) === true)
        {
            return null;
        }

        /** @var Entity $apiIdempotencyKey */
        $apiIdempotencyKey = $this->repo->idempotency_key
            ->findByIdempotencyKeyAndSourceTypeAndMerchantId($psPayoutIdempotencyKey->getIdempotencyKey(),
                                                             PayoutEntity::PAYOUT,
                                                             $psPayoutIdempotencyKey->getMerchantId());

        if (empty($apiIdempotencyKey) === true)
        {
            $this->trace->error(
                TraceCode::PAYOUT_SERVICE_IDEM_KEY_NOT_FOUND_IN_API,
                [
                    Entity::IDEMPOTENCY_KEY => $psPayoutIdempotencyKey->toArray(),
                ]);

            return null;
        }

        if (empty($apiIdempotencyKey->getSourceId()) === false)
        {
            if ($apiIdempotencyKey->getSourceId() !== $psPayoutIdempotencyKey->getSourceId())
            {
                $this->trace->error(
                    TraceCode::PAYOUT_SERVICE_IDEM_KEY_NOT_FOUND_IN_API,
                    [
                        'api_' . Entity::IDEMPOTENCY_KEY => $apiIdempotencyKey->toArray(),
                        'ps' . Entity::IDEMPOTENCY_KEY   => $psPayoutIdempotencyKey->toArray(),
                    ]);
            }

            return null;
        }

        $apiIdempotencyKey->setSourceId($psPayoutIdempotencyKey->getSourceId());

        $this->repo->idempotency_key->saveOrFail($apiIdempotencyKey);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_IDEMPOTENCY_KEY_DONE,
            [
                'payout_id'          => $id,
                'idempotency_key_id' => $apiIdempotencyKey->getId(),
            ]
        );

        return $apiIdempotencyKey;
    }

    public function getAPIPayoutIdempotencyKeyFromPayoutService(string $payoutId)
    {
        $payoutServiceIdempotencyKey = $this->repo->idempotency_key
            ->getPayoutServiceIdempotencyKeyForSourceTypePayout($payoutId);

        if (count($payoutServiceIdempotencyKey) === 0)
        {
            return null;
        }

        $psPayoutIdempotencyKey = $payoutServiceIdempotencyKey[0];

        // converts the stdClass object into associative array.
        $this->attributes = get_object_vars($psPayoutIdempotencyKey);

        $this->processModifications();

        $entity = new Entity;

        $entity->setRawAttributes($this->attributes, true);

        // Explicitly setting the connection.
        $entity->setConnection($this->mode);

        return $entity;
    }
}
