<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Dispute extends Base
{
    public function create(array $attributes = [])
    {
        $payment = $this->fixtures->create('payment:captured', ['disputed' => 1]);

        $reason = $this->fixtures->create('dispute_reason');

        $defaultValues = [
           'amount'          => $payment->getAmount(),
           'phase'           => \RZP\Models\Dispute\Phase::CHARGEBACK,
           'raised_on'       => '946684800',
           'expires_on'      => '1246684800',
           'deduct_at_onset' => true,
           'currency'        => \RZP\Models\Currency\Currency::INR,
           'status'          => \RZP\Models\Dispute\Status::OPEN,
           'payment_id'      => $payment->getId(),
           'merchant_id'     => $payment->getMerchantId(),
           'reason_id'       => $reason->id
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $dispute = $this->createEntity('dispute', $attributes);

        return $dispute;
    }
}
