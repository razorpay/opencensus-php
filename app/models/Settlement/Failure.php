<?php

namespace Models\Settlement;

use Carbon\Carbon;
use Models\Adjustment;
use Models\Settlement;
use Models\Transaction;
use Trace;
use Trace\TraceCode;

class Failure
{
    protected $setl;

    protected $merchant;

    public function markFailed($setl, $reason)
    {
        $setl->setStatus(Status::FAILED);
        $setl->setFailureReason($reason);

        (new Repository)->save($setl);

        $desc = 'Adjustment corresponding to failure of settlement: ' . $setl->getPublicId();

        $adj = $this->newAdjustmentEntity($setl, $desc);

        $adjTxn = $this->newAdjustmentTransaction($adj);

        (new Transaction\Repository)->save($adjTxn);
        (new Adjustment\Repository)->save($adj);

        \Trace::error(TraceCode::SETTLEMENT_MERCHANT_SETL_FAILED);
    }

    protected function newAdjustmentEntity($setl, $desc)
    {
        $adj = new Adjustment\Entity;

        $adj->setAmount($setl->getAmount());
        $adj->setAttribute(Adjustment\Entity::CURRENCY, 'INR');
        $adj->setAttribute(Adjustment\Entity::DESCRIPTION, $desc);
        $adj->setAttribute(Adjustment\Entity::CHANNEL, $setl->getChannel());

        $adj->merchant()->associate($setl->merchant);

        (new Adjustment\Repository)->saveOrFail($adj);

        return $adj;
    }

    protected function newAdjustmentTransaction($adj)
    {
        $txn = new Transaction\Entity;

        $amount = $adj->getAmount();

        $debit = $credit = 0;

        if ($amount > 0)
            $credit = $amount;

        if ($amount < 0)
            $debit = -1 * $amount;

        $settledAt = Carbon::tomorrow('Asia/Kolkata')->timestamp;

        $values = array(
            Transaction\Entity::DEBIT           => $debit,
            Transaction\Entity::CREDIT          => $credit,
            Transaction\Entity::CURRENCY        => 'INR',
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::API_FEE         => 0,
            Transaction\Entity::RECONCILED_AT   => time(),
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::FEE             => 0,
            Transaction\Entity::AMOUNT          => abs($amount),
            Transaction\Entity::TYPE            => Transaction\Type::ADJUSTMENT,
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($adj->merchant);

        $txn->entity()->associate($adj);

        $adj->transaction()->associate($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    protected function updateBalances($txn)
    {
        return (new Transaction\Core)->updateBalances($txn);
    }
}
