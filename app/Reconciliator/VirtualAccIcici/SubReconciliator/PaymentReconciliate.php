<?php

namespace RZP\Reconciliator\VirtualAccIcici\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\BankTransfer;
use RZP\Reconciliator\Base\SubReconciliator;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::AMOUNT;

    const BLACKLISTED_COLUMNS = [
        ReconciliationFields::REMITTER_NAME,
    ];

    protected function getPaymentId(array $row)
    {
        $utr = $row[ReconciliationFields::UTR];

        if (empty($utr) === true)
        {
            $this->trace->info(
                TraceCode::RECON_ALERT,
                [
                    'message'       => 'UTR not present in recon file',
                    'payee_account' => $row[ReconciliationFields::VAN] ?? null,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        $payeeAccount = $row[ReconciliationFields::VAN];

        $amount = $row[self::COLUMN_PAYMENT_AMOUNT];

        $bankTransfer = $this->repo->bank_transfer->findByUtrAndPayeeAccountAndAmount($utr, $payeeAccount, (int)($amount * 100));

        if ($bankTransfer === null)
        {
            $this->alertUnexpectedBankTransferIfApplicable($utr, $payeeAccount);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        return $bankTransfer->getPaymentId();
    }

    protected function alertUnexpectedBankTransferIfApplicable($utr, $payeeAccount)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_UNEXPECTED,
            [
                'message'       => 'Unexpected bank transfer',
                'info_code'     => Base\InfoCode::PAYMENT_ABSENT,
                'utr'           => $utr,
                'payee_account' => $payeeAccount,
                'gateway'       => $this->gateway,
                'batch_id'      => $this->batchId,
            ]);
    }

    /**
     * No gateway payment entity for bank transfer payments,
     * but bank_transfer entity is logically equivalent
     *
     * @param $paymentId
     * @return BankTransfer\Entity
     */
    public function getGatewayPayment($paymentId)
    {
        return $this->repo->bank_transfer->findByPaymentId($paymentId);
    }
}
