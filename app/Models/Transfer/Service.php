<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Reversal;
use RZP\Models\Payment;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function fetch(string $id) : array
    {
        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant);

        return $transfer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $transfers = $this->repo->transfer->fetch($input, $merchantId);

        return $transfers->toArrayPublic();
    }

    public function create(array $input) : array
    {
        $transfer = $this->core->createForMerchant($input, $this->merchant);

        return $transfer->toArrayPublic();
    }

    public function reverse(string $id, array $input) : array
    {
        $reversal = (new Reversal\Core)->reverse($id, $input, $this->merchant);

        return $reversal->toArrayPublic();
    }

    public function edit(string $id, array $input) : array
    {
        $transfer = $this->core->edit($id, $input, $this->merchant);

        return $transfer->toArrayPublic();
    }
}
