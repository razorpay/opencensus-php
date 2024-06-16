<?php

namespace RZP\Reconciliator\IciciDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use App;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_REFUND_AMOUNT = ReconciliationFields::RefundAmount;

    const ICICI_DEBIT_EMI       = 'icici_debit_emi';

    public function getRefundId(array $row)
    {
        $refundId = null;

        $paymentId = $row[ReconciliationFields::TrackId] ?? null;

        $refundAmount = $this->getReconRefundAmount($row);

        $refunds = $this->repo->refund->findForPaymentAndAmount($paymentId, $refundAmount);

        if (count($refunds) === 1)
        {
            $refundId = $refunds[0]['id'];
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => Base\InfoCode::RECON_UNIQUE_REFUND_NOT_FOUND,
                    'payment_id'            => $paymentId,
                    'refund_amount'         => $refundAmount,
                    'refund_count'          => count($refunds),
                    'gateway'               => $this->gateway,
                    'batch_id'              => $this->batchId,
                ]);
        }

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[ReconciliationFields::TrackId] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }
    protected function preProcess(array & $rows)
    {
        // Check if $rows is not empty and if the last index's SerialNumber is empty
        if (!empty($rows) && empty($rows[count($rows) - 1][ReconciliationFields::SerialNumber])) {
            // Remove the last index from $rows
            array_pop($rows);
        }


        for($curr = 0; $curr < count($rows); $curr = $curr + 1)
        {
            if(isset($rows[$curr][ReconciliationFields::TrackId]))
            {
                $verificationFields = [
                    'gateway' => self::ICICI_DEBIT_EMI,
                    'gateway_transaction_id' => $rows[$curr][ReconciliationFields::TrackId],
                    'action' => 'loan_booking'
                ];

                $response = App::getFacadeRoot()['card.payments']->fetchPaymentIdFromEmiGatewayReferenceIds($verificationFields);

                if (empty($response) === false)
                {
                    $rows[$curr][ReconciliationFields::TrackId] = $response;
                }
            }
        }
    }

}
