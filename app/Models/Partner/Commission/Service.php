<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Models\Base\Repository as BaseRepository;

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

    public function fetch(string $id): array
    {
        $partner = $this->merchant;

        $input = [
            BaseRepository::EXPAND => [Entity::SOURCE_MERCHANT]
        ];

        //
        // findByPublicIdAndMerchant() function here, filters by partner_id.
        // Refer Commission\Entity::scopeMerchantId() for more details.
        //
        $commission = $this->repo->commission->findByPublicIdAndMerchant($id, $partner, $input);

        return $commission->toArrayPublic();
    }
}
