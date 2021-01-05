<?php

namespace RZP\Reconciliator\NetbankingCanara\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Reconciliator\NetbankingCanara\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{

    const BLACKLISTED_COLUMNS = [];

    const PII_COLUMNS = [
        Constants::CUSTOMER_ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[Constants::COLUMN_PAYMENT_ID] ?? null;

        if ($paymentId === Constants::HEADER_MERCHANTREFRENCE)
        {
            //
            // This is the header row, ignore this.
            // Note : Sometimes we get the first line as header and sometimes
            // the file starts with data on the first line itself. So we can't
            // skip the first line. Handling both the cases here.
            //
            $this->setFailUnprocessedRow(false);

            return null;
        }

        return $paymentId;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::COLUMN_BANK_PAYMENT_ID] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
            Action::AUTHORIZE);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Constants::COLUMN_PAYMENT_DATE];
    }

    protected function getAccountDetails($row)
    {
        return [Base\Reconciliate::ACCOUNT_NUMBER => $row[Constants::CUSTOMER_ACCOUNT_NUMBER]];
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
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::COLUMN_PAYMENT_AMOUNT]);
    }
}


