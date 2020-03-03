<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payout_link';


    /**
     * SELECT count(*)
     * FROM   payout_links
     * WHERE  payout_links.merchant_id = '10000000000000'
     *        and status = 'issued'
     */
    public function getPayoutLinkByStatus(string $merchantId, string $status)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::STATUS, $status)
                    ->count();
    }

    /**
     * SELECT status, count(*)
     * FROM   payout_links
     * WHERE  payout_links.merchant_id = '10000000000000'
     * GroupBy (status)
     */
    public function getPayoutLinkByMerchant(string $merchantId)
    {
        $collection = $this->newQuery()
                           ->selectRaw(Entity::STATUS  . ', COUNT(*) AS count')
                           ->where(Entity::MERCHANT_ID, $merchantId)
                           ->groupBy(Entity::STATUS)
                           ->pluck('count', Entity::STATUS);

        return array_map('intval', $collection->all());
    }
}
