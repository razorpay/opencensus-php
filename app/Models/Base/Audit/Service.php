<?php

namespace RZP\Models\Base\Audit;

use Cache;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function createAuditInfoPartition()
    {
        try
        {
            $this->repo->audit_info->managePartitions();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            return ['success' => false];
        }

        return ['success' => true];
    }

    public function getMerchantAuditInfo($merchant_id, $input)
    {
        $orgId = $this->auth->getOrgId();

        $merchant = $this->repo->merchant->findByIdAndOrgId($merchant_id, $orgId);

        $core = new Core();

        $startTime = $input["start_time"] ?? Carbon::today()->subDays(7)->getTimestamp();

        $endTime = $input["end_time"] ?? Carbon::now()->getTimestamp();

        return $core->getAuditInfoV2($merchant, $startTime, $endTime);
    }

    public function getAuditInfo($entity, $merchant_id, $input)
    {
        $core = new Core();

        $timeStamp = $input["timeStamp"] ?? Carbon::now()->getTimestamp();

        $limit = $input["limit"] ?? 20;

        return $core->getAuditInfo($entity, $merchant_id, $timeStamp, $limit);
    }
}
