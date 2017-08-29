<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;
use RZP\Models\Payout;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Payout\Core;
    }

    public function fetch(string $id) : array
    {
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);

        return $payout->toArrayPublic();
    }

    public function fetchMultiple(array $input) : array
    {
        $payouts = $this->repo->payout->fetch($input, $this->merchant->getId());

        return $payouts->toArrayPublic();
    }

    public function create(array $input) : array
    {
        $payout = $this->core->directPayout($input, $this->merchant);

        return $payout->toArrayPublic();
    }

    public function initiatePayouts(array $input, $channel = null)
    {
        $data = (new Payout\Core)->initiatePayouts($input, $channel);

        return $data;
    }

    public function merchantPayout(array $input)
    {
        (new Validator)->validateInput('merchant', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $payout = (new Payout\Core)->merchantPayout($input, $merchant);

        return $payout;
    }
}
