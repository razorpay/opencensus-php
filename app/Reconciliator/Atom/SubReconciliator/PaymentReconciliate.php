<?php

namespace RZP\Reconciliator\Atom\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use Razorpay\Spine\Exception\DbQueryException;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_ID          = 'merchant_txn_id';
    const COLUMN_AMOUNT              = 'gross_txn_amount';
    const COLUMN_BANK_REFERENCE_NO   = 'bank_ref_no';
    const COLUMN_ATOM_TRANSACTION_ID = 'atom_txn_id';
    const COLUMN_TRANSACTION_CHARGES = 'txn_charges';
    const COLUMN_SERVICE_TAX         = ['gst_18', 'service_tax'];
    const COLUMN_SETTLED_AT          = 'settlement_date';

    const SETTLEMENT_DATE_FORMAT = [
        'd/m/y',
        'd-M-Y h:i:s',
    ];

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        $gatewayPayment = null;

        try
        {
            $gatewayPayment = $this->repo->atom->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
        }
        catch (DbQueryException $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'info_code'     => Base\InfoCode::GATEWAY_PAYMENT_ABSENT,
                    'payment_id'    => $this->payment->getId(),
                    'gateway'       => $this->gateway,
                ]
            );
        }

        return $gatewayPayment;
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (isset($row[self::COLUMN_AMOUNT]) === true)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
        }

        return null;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $gst = null;

        //
        // Sometimes they send the column name as 'gst_18' and sometimes
        // 'service_tax' in the MIS file, so we need to check which one is
        // set in the row
        //
        $gstColumn = array_first(self::COLUMN_SERVICE_TAX,
            function ($col) use ($row)
            {
                return (isset($row[$col]) === true);
            });

        if ($gstColumn !== null)
        {
            $gst = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[$gstColumn]);
        }
        else
        {
            //
            // This will return back `null` for service tax.
            // Since it's `null`, it'll mark the row as unreconciled.
            //
            $this->reportMissingColumn($row, self::COLUMN_SERVICE_TAX[0]);
        }

        return $gst;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_BANK_REFERENCE_NO] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return trim($row[self::COLUMN_ATOM_TRANSACTION_ID]) ?? null;
    }

    /**
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayFee($row)
    {
        $fee = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_TRANSACTION_CHARGES]);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return round($fee);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        foreach (self::SETTLEMENT_DATE_FORMAT as $dateFormat)
        {
            try
            {
                if (strpos($columnSettledAt, 'done on ') !== false)
                {
                    $columnSettledAt = str_replace('done on ', '', $columnSettledAt);
                }

                $gatewaySettledAt = Carbon::createFromFormat($dateFormat, $columnSettledAt, Timezone::IST)->getTimestamp();
            }
            catch (\Exception $ex)
            {
                continue;
            }
        }

        return $gatewaySettledAt;
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        // Overriding this because atom entity has gatewaypaymentid not gatewaytransactionid
        $dbGatewayTransactionId = $gatewayPayment->getGatewayPaymentId();

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayTransactionId);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => $this->getGatewayTransactionId($row),
            'acquirer'           =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
