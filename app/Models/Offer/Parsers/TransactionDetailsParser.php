<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;

class TransactionDetailsParser extends BaseParser
{
    protected $properties = [
        Offer\Entity::PAYMENT_COUNT,
        Offer\Entity::MIN_AMOUNT
    ];

    protected $localSuffix = '.';

    const PAYMENT_STRING          = 'payment';

    const PAYMENT_LIMIT_STRING    = 'above';

    const CURRENCY_SYMBOL         = 'Rs';

    protected function getPaymentCountDescription()
    {
        $paymentCount = $this->offer->getPaymentCount();

        $description = [];

        if ($paymentCount !== null)
        {
            $description[] = 'for';

            if ($paymentCount === 1)
            {
                $description = array_merge($description, ['first', self::PAYMENT_STRING]);
            }
            else
            {
                $description = array_merge($description, ['first', $paymentCount, self::PAYMENT_STRING  . 's']);
            }
        }

        $description = implode($this->delimiter, $description);

        return $description;
    }

    protected function getMinAmountDescription()
    {
        $minAmount = ($this->offer->getMinAmount() / 100);

        $description = [];

        if ($minAmount > 0)
        {
            $description = [self::PAYMENT_LIMIT_STRING, self::CURRENCY_SYMBOL, $minAmount];
        }

        $description = implode($this->delimiter, $description);

        return $description;
    }
}
