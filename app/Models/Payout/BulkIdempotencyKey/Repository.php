<?php

namespace RZP\Models\Payout\BulkIdempotencyKey;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\IdempotencyKey\Entity;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BULK_IDEMPOTENCY_KEYS;

    public function getPayoutServiceBulkIdempotencyKey(string $merchantId, string $idempotencyKey)
    {
        $tableName = Table::BULK_IDEMPOTENCY_KEYS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }


        $tableResult =  \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where merchant_id = '$merchantId' and idempotency_key = '$idempotencyKey'");

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_GET,
            $tableResult
        );

        return $tableResult;
    }

    public function insertBulkIdempotencyKeyIntoPayoutServiceDB($data)
    {
        $tableName = Table::BULK_IDEMPOTENCY_KEYS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_CREATE,
            $data
        );

        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
             ->from($tableName)
             ->insert($data);
    }

    public function updatePayoutIDForBulkIKeyIntoPayoutServiceDB(string $idempotencyKey, string $merchantID, $data)
    {
        $tableName = Table::BULK_IDEMPOTENCY_KEYS;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_UPDATE,
            $data
        );

        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
             ->from($tableName)
             ->where(Entity::IDEMPOTENCY_KEY, $idempotencyKey)
             ->where(Entity::MERCHANT_ID, $merchantID)
             ->update($data);
    }
}
