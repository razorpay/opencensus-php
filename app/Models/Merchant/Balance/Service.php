<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    /**
     * @param Merchant\Entity $merchant
     * @param string          $balanceType
     * @param string          $mode
     *
     * @return Entity
     */
    public function create(Merchant\Entity $merchant, string $balanceType, string $mode): Entity
    {
        // Evey balance we create will start with 0 balance. if needed we can extend this.
        $input = [
            Entity::TYPE     => $balanceType,
            Entity::BALANCE  => 0,
            Entity::CURRENCY => Currency::INR,
        ];

        return $this->core()->create($merchant, $input, $mode);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string          $balanceType
     * @param null            $mode
     *
     * @return Entity
     */
    public function createOrFetchBalance(Merchant\Entity $merchant, string $balanceType, $mode = null): Entity
    {
        $mode = $mode ?? $this->auth->getLiveConnection();

        $balance = $this->repo->balance->getMerchantBalanceByType($merchant, $balanceType, $mode);

        if ($balance === null)
        {
            $balance = $this->create($merchant, $balanceType, $mode);
        }

        return $balance;
    }
}

