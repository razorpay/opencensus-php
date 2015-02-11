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

        $adjTxn = (new Transaction\Core)->createFromAdjustment($adj);

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
}
