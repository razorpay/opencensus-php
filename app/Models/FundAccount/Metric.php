<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;

class Metric extends Base\Core
{
    const FUND_ACCOUNT_CREATE = 'fund_account_create';

    public static function pushCreateMetrics(Entity $fundAccount)
    {
        $dimensions = self::getDefaultDimensions($fundAccount);

        $extraDimensions = self::getMetricExtraDimensions($fundAccount);

        $dimensions = array_merge($dimensions, $extraDimensions);

        app('trace')->count(self::FUND_ACCOUNT_CREATE, $dimensions);
    }

    protected static function getDefaultDimensions(Entity $fundAccount, array $extra = []) :array
    {
        $dimensions = $extra + [
                Entity::SOURCE_TYPE     => $fundAccount->getSourceType(),
                Entity::ACCOUNT_TYPE    => $fundAccount->getAccountType(),
            ];

        return $dimensions;
    }

    protected static function getMetricExtraDimensions(Entity $fundAccount) :array
    {
        return [];
    }
}
