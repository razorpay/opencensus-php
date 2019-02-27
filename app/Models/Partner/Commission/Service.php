<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    /**
     * Get per transaction commission list for a merchant
     *
     * @param array $input
     *
     * @return array
     */
    public function list(array $input): array
    {
        $commissions = $this->core()->list($this->merchant, $input);

        return $commissions->toArrayPublic();
    }
}
