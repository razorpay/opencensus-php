<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class TransactionDetailsParser extends BaseParser
{
    protected static $properties = [
        Offer\Entity::PAYMENT_COUNT,
        Offer\Entity::MIN_AMOUNT
    ];

    protected static $localSuffix = '.';

    const PAYMENT_STRING          = 'payment';

    const PAYMENT_LIMIT_STRING    = 'above';

    const CURRENCY_SYMBOL         = 'Rs';

    public static function getPaymentCountDescription(Offer\Entity $offer)
    {
        $paymentCount = $offer->getPaymentCount();

        $description = [];

        if ($paymentCount !== null)
        {
            $description[] = 'for';

            if ($paymentCount === 1)
            {
                $description = array_merge($description, ['first', static::PAYMENT_STRING]);
            }
            else
            {
                $description = array_merge($description, ['first', $paymentCount, static::PAYMENT_STRING  . 's']);
            }
        }

        $description = implode(static::$delimiter, $description);

        return $description;
    }

    public static function getMinAmountDescription(Offer\Entity $offer)
    {
        $minAmount = $offer->getMinAmount();

        $description = [];

        if ($minAmount > 0)
        {
            $description = [static::PAYMENT_LIMIT_STRING, static::CURRENCY_SYMBOL, $minAmount];
        }

        $description = implode(static::$delimiter, $description);

        return $description;
    }
}
