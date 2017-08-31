<?php

namespace RZP\Models\Settlement;

use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

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
            $adjData = $this->buildAdjustmentData($desc);

            (new Adjustment\Core)->createAdjustment($adjData, $this->merchant);
        }

        if ($holdMerchantFunds === true)
        {
            $this->holdMerchantFunds();
        }

        $this->sendSettlementFailureNotification();

        $this->trace->error(TraceCode::SETTLEMENT_MERCHANT_SETL_FAILED);
    }

    protected function buildAdjustmentData($desc)
    {
        $adjData = [
            Adjustment\Entity::AMOUNT           => $this->setl->getAmount(),
            Adjustment\Entity::CURRENCY         => 'INR',
            Adjustment\Entity::DESCRIPTION      => $desc,
            Adjustment\Entity::SETTLEMENT_ID    => $this->setl->getId()
        ];

        return $adjData;
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
