<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use Queue;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Services\NbPlus\Emandate as Emandate;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait EmandateReconTrait
{
    protected function nbPlusPaymentServiceEmandateDispatch(array $rowDetails)
    {
        $pushData = [
            'entity_name' => Method::EMANDATE,
            'recon_data'  => [
                'payment_id'                => $this->payment->getId(),
                Emandate::BANK_REFERENCE_ID => $rowDetails[BaseReconciliate::REFERENCE_NUMBER],
                Entity::RECURRING_TYPE      => $this->payment->getRecurringType(),
                'batch_id'                  => $this->batchId,
            ]
        ];

        $queueName = $this->app['config']->get('queue.payment_nbplus_api_reconciliation.' . $this->mode);

        Queue::pushRaw(json_encode($pushData), $queueName);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'  => Base\InfoCode::RECON_NBPLUS_QUEUE_DISPATCH,
                'queue'      => $queueName,
                'payment_id' => $pushData['recon_data']['payment_id'],
                'batch_id'   => $this->batchId,
                'gateway'    => $this->gateway
            ]
        );
    }
}
