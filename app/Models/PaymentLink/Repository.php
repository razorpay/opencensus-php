<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Timezone;

class Repository extends Base\Repository
{
    protected $entity = 'payment_link';

    /**
     * Gets all ACTIVE status payment links which are past EXPIRE_BY.
     * Payment links which are in INACTIVE status are not affected.
     *
     * @return Base\PublicCollection
     */
    public function getActiveAndPastExpireByPaymentLinks(): Base\PublicCollection
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->get();
    }
}
