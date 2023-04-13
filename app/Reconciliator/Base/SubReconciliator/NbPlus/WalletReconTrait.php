<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use Queue;
use Monolog\Logger;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Services\NbPlus\Wallet;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait WalletReconTrait
{
    public function nbPlusPaymentServiceWalletDispatch(array $rowDetails)
    {
        $pushData = [
            'entity_name' => Method::WALLET,
            'recon_data' => [
                'payment_id' => $this->payment->getId(),
                Wallet::WALLET_TRANSACTION_ID => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null
            ]
        ];

        $this->dispatchToNbplusServiceWalletQueue($pushData);
    }

    public function dispatchToNbplusServiceWalletQueue($pushData): void
    {
        $queueName = $this->app['config']->get('queue.payment_nbplus_api_reconciliation.' . $this->mode);

        try
        {
            $this->app['queue']->connection('sqs')->pushRaw(json_encode($pushData), $queueName);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::PAYMENT_RECON_QUEUE_NBPLUS_PUSH_FAILURE,
                [
                    'queueName'  => $queueName,
                    'payment_id' => $pushData['recon_data']['payment_id'],
                    'gateway'    => $this->gateway,
                    'batch_id'   => $this->batchId
                ]
            );
        }

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
