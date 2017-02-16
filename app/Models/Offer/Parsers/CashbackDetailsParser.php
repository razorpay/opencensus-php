<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class CashbackDetailsParser extends BaseParser
{
    protected static $properties = [
        Offer\Entity::PERCENT_RATE,
        Offer\Entity::FLAT_CASHBACK,
        Offer\Entity::MAX_CASHBACK
    ];

    const CURRENCY_SYMBOL       = 'Rs';

    const PERCENT_SYMBOL        = '%';

    const CASHBACK_STRING       = 'cashback';

    const CASHBACK_LIMIT_STRING = 'upto';

    public static function getPercentRateDescription(Offer\Entity $offer)
    {
        $description = [];

        $percentRate = $offer->getPercentRate();

        if ($percentRate > 0)
        {
            $description = [$percentRate . static::PERCENT_SYMBOL, static::CASHBACK_STRING];
        }

        $description = implode(static::$delimiter, $description);

        return $description;
    }

    public static function getFlatCashbackDescription(Offer\Entity $offer)
    {
        $flatCashback = $offer->getFlatCashback();

        $description = [];

        if ($flatCashback > 0)
        {
            $description = [static::CURRENCY_SYMBOL, $flatCashback, static::CASHBACK_STRING];
        }

        $description = implode(static::$delimiter, $description);

        return $description;
    }

    public static function getMaxCashbackDescription(Offer\Entity $offer)
    {
        $maxCashback = $offer->getMaxCashback();

        $description = [];

        if ($maxCashback > 0)
        {
            $description = [static::CASHBACK_LIMIT_STRING, static::CURRENCY_SYMBOL, $maxCashback];
        }

        $description = implode(static::$delimiter, $description);

        return $description;
    }
}
