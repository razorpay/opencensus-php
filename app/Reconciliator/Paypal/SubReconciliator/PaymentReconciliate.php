<?php

namespace RZP\Reconciliator\Paypal\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Mozart\WalletPaypal\ReconFields;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_PAYMENT_AMOUNT = ReconFields::AMOUNT;

    protected function getPaymentId(array $row)
    {
        return $row[ReconFields::PAY_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::TRANSACTION_ID] ?? null;
    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = $this->payment->getCurrency();

        $reconCurrency = $row[ReconFields::CURRENCY] ?? null;

        if (($expectedCurrency !== $reconCurrency) and ( empty($reconCurrency) !== true))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->mozart->findByPaymentIdAndAction($paymentId, Action::CAPTURE);
    }

    protected function getAccountDetails($row)
    {
        return [BaseReconciliate::ACCOUNT_NUMBER => $row[ReconFields::PAYPAL_MERCHANT_ID]];
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbReferenceNumber = $data['CaptureId'] ?? null;

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
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);
        }

        return;
    }

    protected function getPaymentEntityAmount()
    {
        return $this->payment->getAmount();
    }
}
