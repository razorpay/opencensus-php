<?php

namespace RZP\Reconciliator\NetbankingBob;

use RZP\Gateway\Netbanking\Bob;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_ID          = 'fldMerchRefNbr';
    const COLUMN_PAYMENT_AMOUNT      = 'Transaction Amount';
    const COLUMN_GATEWAY_PAYMENT_ID  = 'fldBankRefNbr';
    const COLUMN_BANK_ACCOUNT_NUMBER = 'AccountNo.';

    protected function getPaymentId($row)
    {
        return $row[self::COLUMN_PAYMENT_ID];
    }

    protected function getGatewayPayment($paymentId)
    {
        $status = [Bob\Status::SUCCESS];

        return $this->repo->netbanking
                    ->findByPaymentIdActionAndStatus(
                        $paymentId,
                        Action::AUTHORIZE,
                        $status
                    );
    }

    protected function getNbAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[self::COLUMN_BANK_ACCOUNT_NUMBER]
        ];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_GATEWAY_PAYMENT_ID];
    }
}
