<?php

namespace RZP\Models\Checkout\Order;

use Carbon\Carbon;
use RZP\Base\Validator as BaseValidator;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends BaseValidator
{
    /** @var int Minimum expiration time. Set to 2 minutes. */
    public const MIN_EXPIRE_AT_DIFF = 120;

    /** @var int Maximum expiration time. Set to 30 minutes. */
    public const MAX_EXPIRE_AT_DIFF = 1800;

    /**
     * @var string[]
     *
     * @see ExtendedValidations::validateContactSyntax()
     * @see self::validateDescription()
     * @see self::validateExpireAt()
     * @see self::validateName()
     */
    public static $createRules = [
        '_' => 'sometimes|array',
        Entity::ACCOUNT_ID => 'sometimes|string|alpha_num|size:14',
        Entity::AMOUNT => 'required|int|min_amount',
        Entity::AUTH_LINK_ID => 'sometimes|string|alpha_num|size:14',
        Entity::CHECKOUT_ID => 'required|string|alpha_num|size:14',
        Entity::CUSTOMER_ID => 'sometimes|string|alpha_num|size:14',
        Entity::CONTACT => 'sometimes|contact_syntax',
        Entity::CURRENCY => 'sometimes|size:3',
        Entity::DESCRIPTION => 'sometimes|string|max:255|utf8',
        Entity::EMAIL => 'sometimes|email',
        Entity::EXPIRE_AT => 'sometimes|epoch|custom',
        Entity::INVOICE_ID => 'sometimes|string|alpha_num|size:14',
        Entity::IP => 'required|ip',
        Entity::METHOD => 'sometimes|in:upi',
        Entity::NAME => 'sometimes|string|custom',
        Entity::NOTES => 'filled|notes',
        Entity::OFFER_ID => 'sometimes|string|alpha_num|size:14',
        Entity::ORDER_ID => 'sometimes|required_with:invoice_id|string|alpha_num|size:14',
        Entity::PAYMENT_LINK_ID => 'sometimes|string|alpha_num|size:14',
        Entity::RECEIVER_TYPE => 'required|in:qr_code',
        Entity::SIGNATURE => 'sometimes|string',
        Entity::UPI => 'sometimes|array',
        Entity::UPI . '.flow' => 'sometimes_if:method,upi|in:intent',
        Entity::USER_AGENT => 'required|string',
    ];

    public static $closeRules = [
        'close_reason'  => 'required|string|not_in:paid',
    ];

    public function validateExpireAt(string $attribute, int $expireAt): bool
    {
        $now = Carbon::now(Timezone::IST);

        $minExpireAt = $now->copy()->addSeconds(self::MIN_EXPIRE_AT_DIFF);
        $maxExpireAt = $now->copy()->addSeconds(self::MAX_EXPIRE_AT_DIFF);

        if (($expireAt < $minExpireAt->getTimestamp()) || ($expireAt > $maxExpireAt->getTimestamp())) {
            $message = 'expire_at should be at least ' . $minExpireAt->diffForHumans($now) .
                ' and should not be greater than ' . $maxExpireAt->diffForHumans($now);

            throw new BadRequestValidationFailureException($message, $attribute);
        }

        return true;
    }

    public function validateName(string $attribute, string $value): bool
    {
        if (is_valid_utf8($value) === false)
        {
            $message = 'Only plain text characters are allowed';

            throw new BadRequestValidationFailureException($message, $attribute);
        }

        return true;
    }
}
