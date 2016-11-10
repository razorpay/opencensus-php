<?php

namespace RZP\Models\Settlement;

use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Handler extends Base\Core
{
    protected $setl;

    protected $merchant;

    protected $status;

    protected $failureReason;

    protected $reconciledAt;

    public function __construct($setl, $status, $failureReason)
    {
        parent::__construct();

        $this->setl = $setl;

        $this->merchant = $setl->merchant;

        $this->status = $status;

        $this->failureReason = $failureReason;
    }

    public function process($reconciledAt)
    {
        $this->reconciledAt = $reconciledAt;

        if ($this->status !== Settlement\Status::FAILED)
        {
            $this->processSettlementSuccess();
        }
        else
        {
            $this->processSettlementFailure();
        }

        $this->repo->saveOrFail($this->setl);

        $this->setl->transaction->setReconciledAt($this->reconciledAt);

        $this->repo->transaction->save($this->setl->transaction);

        return $this->setl;
    }

    protected function processSettlementSuccess()
    {
        $this->setl->setStatus($this->status);

        $this->setl->setFailureReason($this->failureReason);
    }

    protected function processSettlementFailure()
    {
        $this->setl->setStatus(Status::FAILED);

        $this->setl->setFailureReason($this->failureReason);

        $desc = 'Adjustment for failed settlement: ' . $this->setl->getPublicId();

        if ($this->setl->adjustment() !== null)
        {
            $adj = $this->newAdjustmentEntity($desc);

            $adjTxn = (new Transaction\Core)->createFromAdjustment($adj);

            $this->repo->save($adjTxn);
        }

        $this->holdMerchantFunds();

        $this->sendSettlementFailureNotification();

        $this->trace->error(TraceCode::SETTLEMENT_MERCHANT_SETL_FAILED);
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
        // TODO: Merchant mailer notification to be added
    }
}
