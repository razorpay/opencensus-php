<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class CashbackDetailsParser extends BaseParser
{
    protected $properties = [
        Offer\Entity::PERCENT_RATE,
        Offer\Entity::FLAT_CASHBACK,
        Offer\Entity::MAX_CASHBACK
    ];

    const CURRENCY_SYMBOL       = 'Rs';

    const PERCENT_SYMBOL        = '%';

    const CASHBACK_STRING       = 'cashback';

    const CASHBACK_LIMIT_STRING = 'upto';

    protected function getPercentRateDescription()
    {
        $description = [];

        $percentRate = ($this->offer->getPercentRate() / 100);

        if ($percentRate > 0)
        {
            $description = [$percentRate . self::PERCENT_SYMBOL, self::CASHBACK_STRING];
        }

        $description = implode($this->delimiter, $description);

        return $description;
    }

    protected function getFlatCashbackDescription()
    {
        $flatCashback = ($this->offer->getFlatCashback() / 100);

        $description = [];

        if ($flatCashback > 0)
        {
            $description = [self::CURRENCY_SYMBOL, $flatCashback, self::CASHBACK_STRING];
        }

        $description = implode($this->delimiter, $description);

        return $description;
    }

    protected function getMaxCashbackDescription()
    {
        $maxCashback = ($this->offer->getMaxCashback() / 100);

        $description = [];

        if ($maxCashback > 0)
        {
            $description = [self::CASHBACK_LIMIT_STRING, self::CURRENCY_SYMBOL, $maxCashback];
        }

        $description = implode($this->delimiter, $description);

        return $description;
    }
}
