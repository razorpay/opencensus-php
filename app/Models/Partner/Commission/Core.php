<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Partner\Config as PartnerConfig;

class Core extends Base\Core
{
    public function build(
        Base\PublicEntity $source,
        Merchant\Entity $partner,
        PartnerConfig\Entity $partnerConfig,
        array $input = [],
        Transaction\Entity $txn = null): Entity
    {
        $commission = new Entity;

        $commission->build($input);

        $commission->source()->associate($source);

        $commission->partner()->associate($partner);

        $commission->partnerConfig()->associate($partnerConfig);

        $commission->transaction()->associate($txn);

        return $commission;
    }

    public function createFromCapturedPayment(Payment\Entity $payment)
    {
        $calculator = new Calculator($payment);

        if ($calculator->shouldCreateCommission() === false)
        {
            return false;
        }

        $calculator->calculate();

        $calculator->saveCommission();
    }
}
