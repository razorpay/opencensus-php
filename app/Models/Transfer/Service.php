<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Models\Reversal;

class Service extends Base\Service
{
    public function fetch(string $id) : array
    {
        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant);

        return $transfer->toArrayPublic();
    }

    public function create(array $input) : array
    {
        $transfer = (new Core)->createForMerchant($input);

        return $transfer->toArrayPublic();
    }

    public function reversal(string $id, array $input) : array
    {
        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant);

        $reversal = (new Reversal\Core)->createForTransferReversal($transfer, $input);

        return $reversal->toArrayPublic();
    }
}
