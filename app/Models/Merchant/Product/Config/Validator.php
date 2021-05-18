<?php

namespace RZP\Models\Merchant\Product\Config;

use RZP\Base;
use RZP\Exception;
use Razorpay\IFSC\IFSC;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Product\Util;

class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code';

    protected static $pgRules             = [
        Util\Constants::SETTLEMENTS     => 'sometimes|array',
        Util\Constants::CHECKOUT        => 'sometimes|array',
        Util\Constants::PAYMENT_CAPTURE => 'sometimes|array',
        Util\Constants::NOTIFICATIONS   => 'sometimes|array'
    ];

    protected static $notificationsRules  = [
        Util\Constants::WHATSAPP => 'sometimes|boolean',
        Util\Constants::SMS      => 'sometimes|boolean',
    ];

    protected static $checkoutRules       = [
        Merchant\Entity::BRAND_COLOR          => 'sometimes|regex:(^#[0-9a-fA-F]{6}$)',
        Util\Constants::FLASH_CHECKOUT        => 'sometimes|boolean',
        Merchant\Entity::DEFAULT_REFUND_SPEED => 'sometimes|filled|string|in:normal,optimum'
    ];

    protected static $paymentCaptureRules = [
        Util\Constants::MODE                    => 'required|string|in:automatic,manual',
        Util\Constants::AUTOMATIC_EXPIRY_PERIOD => 'sometimes|integer',
        Util\Constants::MANUAL_EXPIRY_PERIOD    => 'sometimes|integer',
        Util\Constants::REFUND_SPEED            => 'required|string|in:normal,optimum'
    ];

    protected static $settlementsRules    = [
        Util\Constants::NAME           => 'sometimes|regex:/^[a-zA-Z0-9\s]+$/|min:4|max:120',
        Util\Constants::IFSC_CODE      => 'sometimes|alpha_num|max:11|custom',
        Util\Constants::ACCOUNT_NUMBER => 'sometimes|regex:/^[a-zA-Z0-9]+$/|between:5,20|custom',
    ];

    protected static $pgValidators = [
        'notifications',
        'payment_capture',
        'settlements',
        'checkout',
    ];

    public function validateAccountNumber($attribute, $bankAccountNumber)
    {
        if (\RZP\Models\BankAccount\Validator::isBlacklistedAccountNumber($bankAccountNumber))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT);
        }
    }

    public function validateIfscCode($attribute, $value)
    {
        if (IFSC::validate($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_IFSC_CODE_MESSAGE);
        }
    }

    protected function validateNotifications(array $input)
    {
        if(isset($input[Util\Constants::NOTIFICATIONS]) === false)
        {
            return;
        }

        $notificationInput = $input[Util\Constants::NOTIFICATIONS];

        $this->validateInput('notifications', $notificationInput);
    }

    protected function validateCheckout(array $input)
    {
        if(isset($input[Util\Constants::CHECKOUT]) === false)
        {
            return;
        }

        $checkoutInput = $input[Util\Constants::CHECKOUT];

        $this->validateInput('checkout', $checkoutInput);
    }

    protected function validatePaymentCapture(array $input)
    {
        if(isset($input[Util\Constants::PAYMENT_CAPTURE]) === false)
        {
            return;
        }

        $paymentCaptureInput = $input[Util\Constants::PAYMENT_CAPTURE];

        $this->validateInput('payment_capture', $paymentCaptureInput);
    }

    protected function validateSettlements(array $input)
    {
        if(isset($input[Util\Constants::SETTLEMENTS]) === false)
        {
            return;
        }

        $settlementsInput = $input[Util\Constants::SETTLEMENTS];

        $this->validateInput('settlements', $settlementsInput);
    }

}
