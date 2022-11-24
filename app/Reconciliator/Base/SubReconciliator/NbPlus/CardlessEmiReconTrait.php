<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use Queue;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Services\NbPlus\CardlessEmi as CardlessEmiService;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait CardlessEmiReconTrait
{
    protected function nbPlusPaymentServiceCardlessEmiDispatch(array $rowDetails)
    {
        $data = [
            'payment_id' => $this->payment->getId(),
            CardlessEmiService::GATEWAY_REFERENCE_NUMBER     => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null,
            CardlessEmiService::PROVIDER_REFERENCE_NUMBER    => $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID] ?? null,
        ];

        $this->dispatchCardlessEmiDataToNbplusServiceQueue($data);
    }

    protected function dispatchCardlessEmiDataToNbplusServiceQueue($data)
    {
        $pushData['entity_name'] = Method::CARDLESS_EMI;
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
