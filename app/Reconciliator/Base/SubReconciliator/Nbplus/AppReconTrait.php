<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use Queue;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Services\NbPlus\AppMethod as AppMethodService;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait AppReconTrait
{
    protected function nbPlusPaymentServiceAppMethodDispatch(array $rowDetails)
    {
        $data = [
            'payment_id'                                   => $this->payment->getId(),
            AppMethodService::GATEWAY_REFERENCE_NUMBER     => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null,
            AppMethodService::PROVIDER_REFERENCE_NUMBER    => $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID] ?? null,
        ];

        $this->dispatchAppMethodDataToNbplusServiceQueue($data);
    }

    protected function dispatchAppMethodDataToNbplusServiceQueue($data)
    {
        $pushData['entity_name'] = Method::APP;
        $pushData['recon_data']  = $data;

        $queueName = $this->app['config']->get('queue.payment_nbplus_api_reconciliation.' . $this->mode);

        Queue::pushRaw(json_encode($pushData), $queueName);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'  => Base\InfoCode::RECON_NBPLUS_QUEUE_DISPATCH,
                'queue'      => $queueName,
                'payment_id' => $data['payment_id'],
                'batch_id'   => $this->batchId,
                'gateway'    => $this->gateway
            ]
        );
    }
}
