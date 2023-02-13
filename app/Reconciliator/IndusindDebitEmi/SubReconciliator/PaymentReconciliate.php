<?php

namespace RZP\Reconciliator\IndusindDebitEmi\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::LOAN_AMOUNT;
    const BLACKLISTED_COLUMNS = [];

    public function getPaymentId(array $row)
    {
        $paymentId = $row[ReconciliationFields::EMI_ID] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }

    protected function getGatewayTransactionId(array $row)
    {
        $ibl_txn_ref_number = $row[ReconciliationFields::IBL_TXN_REF_NUMBER] ?? null;

        return trim(str_replace("'", '', $ibl_txn_ref_number));
    }

    protected function getGatewayReferenceId1(array $row)
    {
        return $row[ReconciliationFields::IBL_LOAN_E_AGREEMENT_NUMBER] ?? null;
    }
}
