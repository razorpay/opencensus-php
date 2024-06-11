<?php
namespace RZP\Reconciliator\IciciDebitEmi\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base;
use App;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::SaleAmount;

    const ICICI_DEBIT_EMI       = 'icici_debit_emi';

    public function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::TrackId] ?? null;
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
            if(isset($rows[$curr][ReconciliationFields::TrackId]) and $rows[$curr][ReconciliationFields::TrackId]!="" )
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
