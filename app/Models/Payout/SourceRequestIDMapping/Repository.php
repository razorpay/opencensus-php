<?php

namespace RZP\Models\Payout\SourceRequestIDMapping;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::PAYOUT;
    
    // Add trace constants since they're not in TraceCode yet
    private const PAYOUT_SOURCE_REQUEST_ID_GET = 'payout.source_request_id.get';
    private const PAYOUT_SOURCE_REQUEST_ID_CREATE = 'payout.source_request_id.create';

    public function getPayoutBySourceRequestId($requestId)
    {
        $tableName = 'source_request_id_mapping';

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $tableResult = \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where source_type = 'aws_trace_id' and source_id = '$requestId'");

        $this->trace->info(
            self::PAYOUT_SOURCE_REQUEST_ID_GET,
            $tableResult
        );

        return $tableResult;
    }

    public function insertSourceRequestIdMapping($data)
    {
        $tableName = 'source_request_id_mapping';

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        $this->trace->info(
            self::PAYOUT_SOURCE_REQUEST_ID_CREATE,
            $data
        );

        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
             ->from($tableName)
             ->insert($data);
    }
} 