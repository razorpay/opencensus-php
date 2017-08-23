<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Dispute\Reason\Entity as Reason;
use RZP\Models\Transaction\Entity as Transaction;

class Dispute extends Base
{
    use TransactionTrait;

    public function create(array $attributes = [])
    {
        $payment = $this->fixtures->create('payment:captured', ['disputed' => 1]);

        $reason = $this->fixtures->create('dispute_reason');

        $defaultValues = $this->getDefaultAttributes($payment, $reason);

        $attributes = array_merge($defaultValues, $attributes);

        $dispute = $this->createEntity('dispute', $attributes);

        if ($dispute->isClosed() === true)
        {
            $payment = $dispute->payment;

            $this->fixtures->edit('payment', $payment->getId(), [Payment::DISPUTED => 0]);
        }

        //
        // TODO: Uncomment the following lines after dispute deduct PR is merged.
        // https://github.com/razorpay/api/pull/4324
        // Also ensure to remove the skip on test - SettlementTest::testSettlementWithDispute()
        //

        //$txn = $this->createTransactionOnDispute($dispute);
        //
        //$txn->setAttribute(Transaction::SETTLED_AT, $dispute->getCreatedAt());
        //
        //$txn->saveOrFail();

        return $dispute;
    }

    protected function getDefaultAttributes(
        Payment $payment,
        Reason $reason): array
    {
        return [
           'amount'          => $payment->getAmount(),
           'payment_id'      => $payment->getId(),
           'merchant_id'     => $payment->getMerchantId(),
           'reason_id'       => $reason->getId(),
        ];
    }
}
