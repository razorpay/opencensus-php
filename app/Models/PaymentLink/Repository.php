<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
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

    /**
     * Returns count of units purchased of which payments are still succeeding (i.e. created or authorized).
     * This method gets used in determining if enough slots are available to initiate a payment.
     *
     * @param  Entity $paymentLink
     * @return int
     */
    public function getSucceedingPaymentUnits(Entity $paymentLink): int
    {
        return $paymentLink->payments()
                           ->whereIn(
                               Payment\Entity::STATUS,
                               [
                                   Payment\Status::CREATED,
                                   Payment\Status::AUTHORIZED,
                               ])
                           ->get()
                           ->sum(function (Payment\Entity $p)
                              {
                                  return (int) ($p->getNotes()[Entity::UNITS] ?? 1);
                              });
    }
}
