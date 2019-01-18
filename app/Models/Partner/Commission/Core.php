<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Partner\Config as PartnerConfig;

class Core extends Base\Core
{
    public function create(
        Base\PublicEntity $source,
        Merchant\Entity $partner,
        PartnerConfig\Entity $partnerConfig,
        array $input,
        Transaction\Entity $txn = null): Entity
    {
        $commission = new Entity;

        $commission->build($input);

        $commission->source()->associate($source);

        $commission->partner()->associate($partner);

        $commission->partnerConfig()->associate($partnerConfig);

        if (empty($txn) === false)
        {
            $commission->transaction()->associate($txn);
        }

        $this->repo->saveOrFail($commission);

        return $commission;
    }
}
