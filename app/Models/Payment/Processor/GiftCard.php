<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;

class GiftCard
{
    const RAZORPAYWALLET    = 'razorpaywallet';
    const RAZORPAY_WALLET_GATEWAY = 'wallet_razorpaywallet';

    public static $fullName = array(
        self::RAZORPAYWALLET => 'razorpay_giftcard',
    );

    public static $emailRequiredGiftCards = array(
        self::RAZORPAYWALLET,
    );

    public static function exists($giftCard)
    {
        return (isset(self::$fullName[$giftCard]) === true);
    }

    /**
     * @throws BadRequestException
     */
    public static function validateExists($giftCard)
    {
        if (self::exists($giftCard) === false) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_GIFT_CARD_NOT_SUPPORTED,
                Payment\Entity::METHOD);
        }
    }

    public static function isEmailRequired(string $giftCard)
    {
        return (in_array($giftCard, self::$emailRequiredGiftCards) === true);
    }


    public static function getGiftCardNetworkNames()
    {
        return self::$fullName;
    }

    public static function getName($giftCard)
    {
        return self::$fullName[$giftCard];
    }
}
