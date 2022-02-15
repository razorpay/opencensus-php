<?php

namespace RZP\Models\Partner\Commission\Invoice;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'commission_invoice';

    const EXPAND_EACH          = 'expand.*';

    protected $proxyFetchParamRules = [
        Entity::ID          => 'filled|string|size:14',
        Entity::BALANCE_ID  => 'filled|string|size:14',
        Entity::MERCHANT_ID => 'filled|string|size:14',
        Entity::STATUS      => 'filled|string|custom',
        self::EXPAND_EACH   => 'filled|string|in:line_items,line_items.taxes',
    ];

    public function validateStatus($attribute, $status)
    {
        Status::validateStatus($status);
    }

    public function fetchInvoices(string $merchantId, int $month, int $year): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->get();
    }

    public function fetchApprovedInvoices($ids = null, $limit = null, $afterId = null)
    {
        $timestamp = Carbon::createFromDate(2022, 1, 25, Timezone::IST)->startOfDay()->getTimestamp();

        $query = $this->newQuery()
                    ->where(Entity::UPDATED_AT, '<', $timestamp)
                    ->whereIn(Entity::STATUS, [Status::APPROVED])
                    ->orderBy(Entity::ID);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where(Entity::ID, '>', $afterId);
        }

        if (empty($ids) === false)
        {
            $query->whereIn(Entity::ID, $ids);
        }

        return $query->get();
    }
}
