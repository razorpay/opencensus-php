<?php

namespace RZP\Reconciliator\NetbankingBob\SubReconciliator;

use RZP\Gateway\Netbanking\Bob;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_ID          = 'fldMerchRefNbr';
    const COLUMN_GATEWAY_PAYMENT_ID  = 'fldBankRefNbr';
    const COLUMN_BANK_ACCOUNT_NUMBER = 'AccountNo.';
    const COLUMN_PAYMENT_AMOUNT      = 'Transaction Amount';

    const BLACKLISTED_COLUMNS = [];

    const PII_COLUMNS = [
        self::COLUMN_BANK_ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID];
    }

    public function getGatewayPayment($paymentId)
    {
        $status = [Bob\Status::SUCCESS];

        return $this->repo
                    ->netbanking
                    ->findByPaymentIdActionAndStatus(
                        $paymentId,
                        Action::AUTHORIZE,
                        $status
                    );
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => trim($row[self::COLUMN_BANK_ACCOUNT_NUMBER])
        ];
    }

    protected function getReferenceNumber($row)
    {
        $referenceNumber = null;

        //
        // The MIS files have reference number as `0087520168`
        // but in DB, we store them without leading zeroes.
        // hence removing them before matching with db value.
        //
        if (empty($row[self::COLUMN_GATEWAY_PAYMENT_ID]) === false)
        {
            $referenceNumber = ltrim($row[self::COLUMN_GATEWAY_PAYMENT_ID], '0');
        }

        return $referenceNumber;
    }
}
