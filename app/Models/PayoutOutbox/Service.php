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
    public function undoPayout($id): array {
        $this->trace->info(TraceCode::DELETE_PAYOUT_OUTBOX_REQUEST, ['id' => $id]);

        $outboxPayout = $this->repo->payout_outbox->fetchPayoutInOutboxById($id, $this->merchant['id'], $this->user['id']);

        if ($outboxPayout == null || $outboxPayout->trashed()) {
            $this->trace->info(TraceCode::BAD_REQUEST_PAYOUT_DOES_NOT_EXIST_IN_OUTBOX, ['id' => $id]);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $this->repo->deleteOrFail($outboxPayout);

        return $outboxPayout->toArrayDeleted();
    }

    public function resumePayout($id) {
        $this->trace->info(TraceCode::RESUME_PAYOUT_CREATION_REQUEST, ['id' => $id]);

        $outboxPayout = $this->repo->payout_outbox->fetchPayoutInOutboxById($id, $this->merchant['id'], $this->user['id']);

        if ($outboxPayout == null || $outboxPayout->trashed()) {
            $this->trace->info(TraceCode::BAD_REQUEST_PAYOUT_DOES_NOT_EXIST_IN_OUTBOX, ['id' => $id]);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $payout = $this->core()->resumePayout($id);

        return $payout->toArrayPublic();
    }

    public function getOrphanedPayoutsFromOutbox() {
        $orphanPayouts = $this->repo->payout_outbox->getOrphanedPayoutsFromOutbox();

        $this->trace->info(TraceCode::GET_ORPHAN_PAYOUTS_FROM_OUTBOX);

        if (sizeof($orphanPayouts) != 0) {
            $listOfIds = [];
            foreach ($orphanPayouts as $orphanPayout) {
                array_push($listOfIds, $orphanPayout);
            }
            // TODO: Setup Sumo alert on this trace code
            $this->trace->info(TraceCode::ORPHAN_PAYOUTS_FROM_OUTBOX, $listOfIds);
        }

        return ['orphaned_payout_count' => sizeof($orphanPayouts)];
    }

    public function deleteOrphanedPayouts($input) {
        try {
            $payoutIds = $input['ids'];

            $this->trace->info(TraceCode::DELETE_ORPHAN_PAYOUTS_FROM_OUTBOX, $payoutIds);

            $this->repo->payout_outbox->deleteOrphanedPayouts($payoutIds);
        } catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::EXCEPTION_DELETE_ORPHAN_PAYOUTS_FROM_OUTBOX);
            return ['success' => false];
        }
        return ['success' => true];
    }

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
