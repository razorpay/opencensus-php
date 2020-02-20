<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Merchant\Account;
use RZP\Models\Transaction;

class Transfer extends Base
{
    use TransactionTrait;

    public function createToAccount(array $attributes = [])
    {
        if (isset($attributes['account']) === true)
        {
            $account = $attributes['account'];

            unset($attributes['account']);
        }
        else
        {
            $account = $this->fixtures->create('merchant:marketplace_account', ['balance' => 250000]);
        }

        $defaultValues = [
            'to_type'                   => 'merchant',
            'to_id'                     => $account->getId(),
            'recipient_settlement_id'   => null
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $transfer = $this->build('transfer', $attributes);

        list($txn, $feeSplit) = $this->createTransactionOnTransfer($transfer);

        $txn->saveOrFail();

        (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

        $transfer->saveOrFail();

        $transferPayment = $this->fixtures->create(
            'payment:method_transfer',
            [
                'amount'        => $transfer->getAmount(),
                'transfer_id'   => $transfer->getId(),
                'merchant_id'   => $transfer->getToId(),
                'on_hold'       => $transfer->getOnHold(),
                'on_hold_until' => $transfer->getOnHoldUntil(),
                'captured_at'   => $transfer->getCreatedAt(),
                'created_at'    => $transfer->getCreatedAt(),
                'updated_at'    => $transfer->getCreatedAt(),
            ]);

        return $transfer;
    }

    public function createToCustomer(array $attributes = [])
    {

    }

    public function editProcessedAt(string $processedAt, string $id)
    {
        $this->fixtures->edit('transfer', $id, ['processed_at' => $processedAt]);
    }
}
