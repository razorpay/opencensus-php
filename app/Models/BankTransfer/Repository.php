<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER;

    public function findByUtr($utr)
    {
        return $this->newQuery()
                    ->where(Entity::UTR, '=', $utr)
                    ->first();
    }

    public function findByPayment(Payment\Entity $payment)
    {
        return $this->findByPaymentId($payment->getId());
    }

    public function findByPaymentId(string $paymentId)
    {
        $bankTransfer = $this->newQuery()
                             ->where(Entity::PAYMENT_ID, '=', $paymentId)
                             ->with('payerBankAccount')
                             ->firstOrFail();

        return $bankTransfer;
    }
}
