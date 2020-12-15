<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Method;
use RZP\Jobs\NbPlusRecon\NetbankingRecon;
use RZP\Services\NbPlus\Netbanking as NetbankingService;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

trait NetbankingReconTrait
{
    protected function nbPlusPaymentServiceNetbankingDispatch(array $rowDetails)
    {
        $customerId          = null;
        $debitAccountNumber  = null;
        $creditAccountNumber = null;

        if (isset($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true)
        {
            $debitAccountNumber  = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][BaseReconciliate::ACCOUNT_NUMBER] ?? null;
            $creditAccountNumber = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][BaseReconciliate::CREDIT_ACCOUNT_NUMBER] ?? null;
        }

        if (isset($rowDetails[BaseReconciliate::CUSTOMER_DETAILS]) === true)
        {
            $customerId = $rowDetails[BaseReconciliate::CUSTOMER_DETAILS][Base\Reconciliate::CUSTOMER_ID] ?? null;
        }

        $data = [
            'payment_id' => $this->payment->getId(),
            'recon_file_data' => [
                NetbankingService::GATEWAY_TRANSACTION_ID => $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID] ?? null,
                NetbankingService::BANK_TRANSACTION_ID    => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null,
                NetbankingService::BANK_ACCOUNT_NUMBER    => $debitAccountNumber,
                NetbankingService::CREDIT_ACCOUNT_NUMBER  => $creditAccountNumber,
                NetbankingService::CUSTOMER_ID            => $customerId
            ],
            'attributes' => self::NETBANKING_ATTRIBUTES,
            'method'     => Method::NETBANKING,
            'mode'       => $this->mode,
            'gateway'    => $this->gateway,
            'batch_id'   => $this->batchId,
        ];

        NetbankingRecon::dispatch($data);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'  => Base\InfoCode::RECON_NBPLUS_JOB_DISPATCH,
                'payment_id' => $this->payment->getId(),
            ]
        );
    }
}
