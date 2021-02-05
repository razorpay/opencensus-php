<?php

namespace RZP\Models\Partner\Commission\Invoice;

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
}
