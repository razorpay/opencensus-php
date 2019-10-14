<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Constants\Product;
use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    /**
     * PG balance types.
     */
    const PRIMARY        = 'primary';
    const COMMISSION     = 'commission';
    const FEE_CREDITS    = 'fee_credits';
    const REFUND_CREDITS = 'refund_credits';
    const AMOUNT_CREDITS = 'amount_credits';

    /**
     * Banking balance.
     */
    const BANKING        = 'banking';

    public static $pgBalances = [
        self::PRIMARY,
        self::FEE_CREDITS,
        self::REFUND_CREDITS,
        self::AMOUNT_CREDITS,
    ];

    public static $settleableBalanceTypes = [
        self::PRIMARY,
        self::COMMISSION,
    ];

    public static function exists(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function validateSettlementBalanceType($type)
    {
        if (in_array($type, self::$settleableBalanceTypes, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid balance type: ' . $type);
        }
    }

    public static function isSettleableBalanceType($balanceType): bool
    {
        if ($balanceType === null)
        {
            return true;
        }

        return in_array($balanceType, self::$settleableBalanceTypes, true);
    }

    public static function getTypeForProduct(string $product)
    {
        Product::validate($product);

        switch ($product)
        {
            case Product::PRIMARY:
                return self::PRIMARY;

            case Product::BANKING:
                return self::BANKING;
        }

        throw new BadRequestValidationFailureException('Not a valid product: ' . $product);
    }
}
