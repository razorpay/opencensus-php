<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use Queue;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Services\NbPlus\Netbanking as NetbankingService;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait NetbankingReconTrait
{
    protected function nbPlusPaymentServiceNetbankingDispatch(array $rowDetails): void
    {
        $customerId          = null;
        $debitAccountNumber  = null;
        $creditAccountNumber = null;
        $verificationId      = null;

        if (isset($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true)
        {
            $debitAccountNumber  = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][BaseReconciliate::ACCOUNT_NUMBER] ?? null;
            $creditAccountNumber = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][BaseReconciliate::CREDIT_ACCOUNT_NUMBER] ?? null;
        }

        if (isset($rowDetails[BaseReconciliate::CUSTOMER_DETAILS]) === true)
        {
            $customerId = $rowDetails[BaseReconciliate::CUSTOMER_DETAILS][Base\Reconciliate::CUSTOMER_ID] ?? null;
        }

        if (isset($rowDetails[BaseReconciliate::GATEWAY_UNIQUE_ID]) === true)
        {
            $verificationId = $rowDetails[BaseReconciliate::GATEWAY_UNIQUE_ID];
        }

        $data = [
            'payment_id' => $this->payment->getId(),
            NetbankingService::GATEWAY_TRANSACTION_ID => $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID] ?? null,
            NetbankingService::BANK_TRANSACTION_ID    => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null,
            NetbankingService::BANK_ACCOUNT_NUMBER    => $debitAccountNumber,
            NetbankingService::VERIFICATION_ID        => $verificationId,
            NetbankingService::ADDITIONAL_DATA        => [
                NetbankingService::CREDIT_ACCOUNT_NUMBER  => $creditAccountNumber,
                NetbankingService::CUSTOMER_ID            => $customerId,
            ]
        ];

        $this->dispatchToNbplusServiceQueue($data);
    }

    public function dispatchToNbplusServiceQueue($data): void
    {
        $pushData['entity_name'] = Method::NETBANKING;
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
