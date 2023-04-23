<?php

namespace RZP\Constants;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Product
 *
 * Razorpay product lines
 *
 * @package RZP\Constants
 */
final class Product
{
    /**
     * Primary payment gateway product
     */
    const PRIMARY = 'primary';

    /**
     * RazorpayX - Business Banking 😎
     */
    const BANKING = 'banking';

    /**
     * Capital Product
     */
    const CAPITAL = 'capital';

    /**
     * Issuing Product
     */
    const ISSUING = 'issuing';

    const VALID_SUBMERCHANT_PRODUCTS =
        [
            self::PRIMARY,
            self::BANKING,
            self::CAPITAL
        ];

    /**
     * @param string $product
     *
     * @return bool
     */
    public static function isValid(string $product): bool
    {
        $key = __CLASS__ . '::' . strtoupper($product);

        return ((defined($key) === true) and (constant($key) === $product));
    }

    /**
     * @param string $product
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $product)
    {
        if (self::isValid($product) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid product: ' . $product);
        }
    }

    public static function isProductBanking(string $product): bool
    {
        return (self::BANKING === $product);
    }
}
