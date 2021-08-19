<?php

namespace RZP\Reconciliator\NetbankingPnb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Pnb\ReconFields;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_AMOUNT = ReconFields::AMOUNT;

    const BLACKLISTED_COLUMNS = [];

    const PII_COLUMNS = [
        ReconFields::ACCOUNT_NO,
    ];

    protected function getPaymentId(array $row)
    {
        if (empty($row[ReconFields::PAYMENT_ID]) === false)
        {
            return trim($row[ReconFields::PAYMENT_ID]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[ReconFields::BANK_PAYMENT_ID]) === true)
        {
            $referenceNumber = $row[ReconFields::BANK_PAYMENT_ID];

            return $referenceNumber;
        }

        return null;
    }

    protected function getArn($row)
    {
        if (isset($row[ReconFields::BANK_PAYMENT_ID]) === true)
        {
            $referenceNumber = $row[ReconFields::BANK_PAYMENT_ID];

            return $referenceNumber;
        }

        return null;
    }

    protected function getGatewayPaymentDate($row)
    {
        $date = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($row[ReconFields::DATE],'DD-MM-YYYY');

        return  $date;
    }

    protected function getAccountDetails($row)
    {
        return [
            BaseReconciliate::ACCOUNT_NUMBER => $row[ReconFields::ACCOUNT_NO],
        ];
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => $this->getReferenceNumber($row),
            'acquirer'           =>       [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
