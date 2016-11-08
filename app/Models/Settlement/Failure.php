<?php

namespace RZP\Models\Settlement;

use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Trace;
use RZP\Trace\TraceCode;

class Failure
{
    protected $setl;

    protected $merchant;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->repo = $app['repo'];
    }

    public function markFailed($setl, $reason = null)
    {
        $setl->setStatus(Status::FAILED);
        $setl->setFailureReason($reason);

        $this->repo->save($setl);

        $desc = 'Adjustment corresponding to failure of settlement: ' . $setl->getPublicId();

        $adj = $this->newAdjustmentEntity($setl, $desc);

        $adjTxn = (new Transaction\Core)->createFromAdjustment($adj);

        $this->repo->save($adjTxn);
        $this->repo->save($adj);

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

        $this->repo->saveOrFail($adj);

        return $adj;
    }
}
