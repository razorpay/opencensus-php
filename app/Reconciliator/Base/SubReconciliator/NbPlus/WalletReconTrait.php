<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use Queue;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Services\NbPlus\Wallet as Wallet;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait WalletReconTrait
{
    protected function nbPlusPaymentServiceWalletDispatch(array $rowDetails)
    {
        $pushData = [
            'entity_name' => Method::WALLET,
            'recon_data'  => [
                'payment_id'                => $this->payment->getId(),
                Wallet::WALLET_TRANSACTION_ID => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null
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
