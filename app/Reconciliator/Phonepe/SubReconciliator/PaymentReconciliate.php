<?php

namespace RZP\Reconciliator\Phonepe\SubReconciliator;

use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Gateway\Mozart\WalletPhonepe\ReconFields;
use RZP\Reconciliator\Base\SubReconciliator\NbPlus;

class PaymentReconciliate extends NbPlus\NbPlusServiceRecon
{
    protected function getPaymentId(array $row)
    {
        return $row[ReconFields::RZP_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::PHONEPE_ID] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->mozart->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

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
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax and GST into paise
        $serviceTax = 0;

        foreach (ReconFields::TAXES as $tax)
        {
            if (array_key_exists($tax, $row) === false)
            {
                $this->reportMissingColumn($row, $tax);

                continue;
            }

            $serviceTax += abs(Helper::getIntegerFormattedAmount($row[$tax]));
        }

        return $serviceTax;
    }

    protected function getGatewayFee($row)
    {
        $fee = 0;

        if (array_key_exists(ReconFields::FEE, $row) === false)
        {
            $this->reportMissingColumn($row, ReconFields::FEE);
        }
        else
        {
            $fee += abs(Helper::getIntegerFormattedAmount($row[ReconFields::FEE]));
        }

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return round($fee);
    }


    protected function getReconPaymentAmount(array $row)
    {
        if (isset($row[ReconFields::AMOUNT]) === false)
        {
            $this->reportMissingColumn($row, ReconFields::AMOUNT);

            return null;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconFields::AMOUNT]);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbReferenceNumber = $data['providerReferenceId'] ?? null;

        //
        // Sometimes we have db reference number saved as string 'null'.
        // (we encountered few cases in Atom). We don't want to raise data
        // mismatch alert in such cases. so adding a check to compare
        // string 'null'
        //
        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== 'null') and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $data['providerReferenceId'] = $referenceNumber;

        $raw = json_encode($data);

        $gatewayPayment->setRaw($raw);
    }

    protected function getArn($row)
    {
        return $row[ReconFields::BANK_REFERENCE_NO] ?? null;
    }
}
