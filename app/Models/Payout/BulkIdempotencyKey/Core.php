<?php

namespace RZP\Models\Payout\BulkIdempotencyKey;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\IdempotencyKey\Entity;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\BulkIdempotencyKey\Repository as bulkIdempotencyKeyRepository;

class Core extends Base
{
    public function getBulkIdempotencyKeyFromPayoutServiceLast24Hours(string $merchantId, string $idempotencyKey)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_INIT,
            ['merchant_id' => $merchantId, 'idempotency_key' => $idempotencyKey]
        );

        $payoutServiceBulkIdempotencyKey = (new bulkIdempotencyKeyRepository)->getPayoutServiceBulkIdempotencyKey($merchantId, $idempotencyKey);

        if (count($payoutServiceBulkIdempotencyKey) === 0)
        {
            return null;
        }

        $psPayoutBulkIdempotencyKey = $payoutServiceBulkIdempotencyKey[0];

        // converts the stdClass object into associative array.
        return get_object_vars($psPayoutBulkIdempotencyKey);
    }

    public function setPayoutServiceBulkIdempotencyKey(
        string $merchantId,
        string $idempotencyKey,
        string $batchId,
        array $sourceInfo = null): void
    {
        $data = ([
            Entity::ID              => Entity::generateUniqueId(),
            Entity::MERCHANT_ID     => $merchantId,
            Entity::IDEMPOTENCY_KEY => $idempotencyKey,
            'batch_id'              => $batchId,
            Entity::CREATED_AT      => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::UPDATED_AT      => Carbon::now(Timezone::IST)->getTimestamp(),
        ]);

        if ($sourceInfo !== null) {
            $data += $sourceInfo;
        }

        (new bulkIdempotencyKeyRepository)->insertBulkIdempotencyKeyIntoPayoutServiceDB($data);
    }

    public function upsertBulkIdempotencyKeyIntoPayoutServiceDB(string $idempotencyKey, string $merchantID, string $sourceID, string$sourceType): void
    {
        $data = ([
            Entity::SOURCE_ID       => $sourceID,
            Entity::SOURCE_TYPE     => $sourceType,
            Entity::UPDATED_AT      => Carbon::now(Timezone::IST)->getTimestamp(),
        ]);

        (new bulkIdempotencyKeyRepository)->updatePayoutIDForBulkIKeyIntoPayoutServiceDB($idempotencyKey, $merchantID, $data);
    }
}
