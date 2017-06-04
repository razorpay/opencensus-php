<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function fetch(string $id): array
    {
        $reversal = $this->repo
                         ->reversal
                         ->findByPublicIdAndMerchant($id, $this->merchant);

        return $reversal->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $merchantId = $this->merchant->getId();

        $reversals = $this->repo->reversal->fetch($input, $merchantId);

        return $reversals->toArrayPublic();
    }
}
