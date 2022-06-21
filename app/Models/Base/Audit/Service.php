<?php

namespace RZP\Models\Base\Audit;

use Cache;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{

    public function createAuditInfoPartition()
    {
        try
        {
            $this->repo->audit_info->createPartition();

            $this->repo->audit_info->dropPartition();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            // duplicate partition name error
            if (($e->getCode() === 'HY000') and (in_array(1517, $e->errorInfo) === true))
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::AUDIT_INFO_DUPLICATE_PARTITION_ERROR);

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'Duplicate partition name');
            }

            $this->trace->traceException($e, Trace::ERROR, TraceCode::AUDIT_INFO_PARTITION_ERROR);

            return ['success' => false];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::AUDIT_INFO_PARTITION_ERROR);

            return ['success' => false];
        }

        $this->trace->info(TraceCode::AUDIT_INFO_PARTITION_SUCCESS, []);

        return ['success' => true];
    }

    public function getMerchantAuditInfo($merchant_id, $input)
    {
        $core = new Core();

        $timeStamp = $input["timeStamp"] ?? Carbon::now()->getTimestamp();

        $limit = $input["limit"] ?? 20;

        return $core->getMerchantAuditInfo($merchant_id, $timeStamp, $limit);
    }

    public function getAuditInfo($entity, $merchant_id, $input)
    {
        $core = new Core();

        $timeStamp = $input["timeStamp"] ?? Carbon::now()->getTimestamp();

        $limit = $input["limit"] ?? 20;

        return $core->getAuditInfo($entity, $merchant_id, $timeStamp, $limit);
    }
}
