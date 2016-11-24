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

    public function __construct($setl)
    {
        parent::__construct();

        $this->setl = $setl;

        $this->merchant = $setl->merchant;
    }

    public function process($holdMerchantFunds)
    {
        if ($this->setl->getStatus() !== Status::FAILED)
        {
            $this->processSettlementSuccess();
        }
        else
        {
            $this->processSettlementFailure($holdMerchantFunds);
        }
    }

    protected function processSettlementSuccess()
    {
    }

    /**
     *  creates adjustment and its transaction and sets merchant funds on hold
     */
    protected function processSettlementFailure($holdMerchantFunds)
    {
        $desc = 'Adjustment for failed settlement';

        if ($this->setl->adjustment === null)
        {
            $adj = $this->newAdjustmentEntity($desc);

            $adjTxn = (new Transaction\Core)->createFromAdjustment($adj);

            $this->repo->saveOrFail($adjTxn);
        }

        if ($holdMerchantFunds === true)
        {
            $this->holdMerchantFunds();
        }

        $this->sendSettlementFailureNotification();

        $this->trace->error(TraceCode::SETTLEMENT_MERCHANT_SETL_FAILED);
    }

    protected function newAdjustmentEntity($desc)
    {
        $input = [
            Adjustment\Entity::AMOUNT      => $this->setl->getAmount(),
            Adjustment\Entity::CURRENCY    => 'INR',
            Adjustment\Entity::DESCRIPTION => $desc,
        ];

        $adj = (new Adjustment\Entity)->build($input);

        $adj->setChannel($this->setl->getChannel());

        $adj->settlement()->associate($this->setl);

        $adj->merchant()->associate($this->merchant);

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
