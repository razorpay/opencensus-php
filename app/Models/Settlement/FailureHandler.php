<?php

namespace RZP\Models\Settlement;

use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Trace;
use RZP\Trace\TraceCode;

class FailureHandler
{
    protected $setl;

    protected $merchant;

    public function __construct($setl)
    {
        $app = \App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->setl = $setl;

        $this->merchant = $setl->merchant();
    }

    public function markFailed($reason = null)
    {
        $this->setl->setStatus(Status::FAILED);
        $this->setl->setFailureReason($reason);

        $this->repo->save($this->setl);

        $desc = 'Adjustment corresponding to failure of settlement: ' . $this->setl->getPublicId();

        if ($this->setl->adjustment() !== null)
        {
            $adj = $this->newAdjustmentEntity($desc);

            $adjTxn = (new Transaction\Core)->createFromAdjustment($adj);

            $this->repo->save($adjTxn);
            $this->repo->save($adj);
        }

        $this->holdMerchantFunds();

        $this->sendSettlementFailureNotification();

        Trace::error(TraceCode::SETTLEMENT_MERCHANT_SETL_FAILED);
    }

    protected function newAdjustmentEntity($desc)
    {
        $adj = new Adjustment\Entity;

        $adj->setAmount($this->setl->getAmount());
        $adj->setAttribute(Adjustment\Entity::CURRENCY, 'INR');
        $adj->setAttribute(Adjustment\Entity::DESCRIPTION, $desc);
        $adj->setAttribute(Adjustment\Entity::CHANNEL, $this->setl->getChannel());
        $adj->setAttribute(Adjustment\Entity::SETTLEMENT_ID, $this->setl->getId());

        $adj->merchant()->associate($this->setl->merchant);

        $this->repo->saveOrFail($adj);

        return $adj;
    }

    protected function holdMerchantFunds()
    {
        $this->merchant->setHoldFunds(true);

        $this->repo->saveOrFail($this->merchant);
    }

    protected function sendSettlementFailureNotification()
    {
        // TODO: Implementation to be added
    }
}
