<?php

namespace RZP\Models\PayoutOutbox;

use Cache;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createPayoutOutboxPartition() {
        try
        {
            $this->repo->payout_outbox->createPartition();

            $this->repo->payout_outbox->dropPartition();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            // duplicate partition name error
            if (($e->getCode() === 'HY000') and (in_array(1517, $e->errorInfo) === true))
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYOUT_OUTBOX_DUPLICATE_PARTITION_ERROR);

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'Duplicate partition name');
            }

            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYOUT_OUTBOX_PARTITION_ERROR);

            return ['success' => false];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYOUT_OUTBOX_PARTITION_ERROR);

            return ['success' => false];
        }

        $this->trace->info(TraceCode::PAYOUT_OUTBOX_PARTITION_SUCCESS, []);

        return ['success' => true];
    }
}
