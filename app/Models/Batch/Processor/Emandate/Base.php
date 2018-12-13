<?php

namespace RZP\Models\Batch\Processor\Emandate;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Batch\Processor\Base as BaseProcessor;

abstract class Base extends BaseProcessor
{
    protected function reconcileEntity($entity)
    {
        if ($this->shouldReconcileEntity($entity) === true)
        {
            try
            {
                $this->markEntityReconciled($entity);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::EMANDATE_RECON_ROW_FAILED,
                    [
                        'entity_id'  => $entity->getId(),
                    ]);
            }
        }
    }

    protected function shouldReconcileEntity($entity)
    {
        if ($entity->getTransactionId() === null)
        {
            $this->trace->critical(TraceCode::EMANDATE_RECON_ROW_FAILED, [
                'entity_id'  => $entity->getId(),
                'message'    => 'transaction missing for entity'
            ]);

            return false;
        }

        return true;
    }

    protected function markEntityReconciled($entity)
    {
        $transaction = $entity->transaction;

        if ($transaction->isReconciled() === true)
        {
            return;
        }

        $time = Carbon::now(Timezone::IST)->getTimestamp();

        $transaction->setReconciledAt($time);

        $this->repo->saveOrFail($transaction);
    }
}
