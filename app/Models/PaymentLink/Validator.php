<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\LineItem;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\PaymentLink
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    const MAX_ALLOWED_PAYMENT_PAGE_ITEMS = 25;

    protected static $createRules = [
        Entity::AMOUNT          => 'sometimes|nullable|mysql_unsigned_int|min_amount|custom',
        Entity::CURRENCY        => 'filled|string|currency|custom',
        Entity::EXPIRE_BY       => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE   => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::RECEIPT         => 'string|min:3|max:40|nullable',
        Entity::TITLE           => 'required|string|min:3|max:40',
        Entity::DESCRIPTION     => 'string|max:65535|nullable|utf8', // 65535 bytes is size of mysql's text data type.
        Entity::NOTES           => 'sometimes|notes',
        Entity::SLUG            => 'filled|min:4|max:30|custom',
        Entity::SUPPORT_CONTACT => 'nullable|string|min:8|max:255',
        Entity::SUPPORT_EMAIL   => 'nullable|email',
        Entity::TERMS           => 'nullable|string|min:5|max:2048',
        Entity::SETTINGS        => 'nullable|array',
        Entity::TEMPLATE_TYPE   => 'sometimes|string|max:24',

        Entity::SETTINGS . '.' . Entity::THEME                        => 'nullable|string|in:light,dark',
        Entity::SETTINGS . '.' . Entity::UDF_SCHEMA                   => 'nullable|json',
        Entity::SETTINGS . '.' . Entity::ALLOW_MULTIPLE_UNITS         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::ALLOW_SOCIAL_SHARE           => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_REDIRECT_URL => 'nullable|url',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_MESSAGE      => 'nullable|string|min:5|max:2048',
        Entity::SETTINGS . '.' . Entity::CHECKOUT_OPTIONS             => 'array',
        Entity::SETTINGS . '.' . Entity::PAYMENT_BUTTON_LABEL         => 'string|max:16',

        Entity::PAYMENT_PAGE_ITEMS => 'sometimes|sequential_array|min:1',
    ];

    protected static $editRules = [
        Entity::AMOUNT          => 'nullable|mysql_unsigned_int|custom',
        Entity::EXPIRE_BY       => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE   => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT         => 'string|min:3|max:40|nullable',
        Entity::TITLE           => 'string|min:3|max:40',
        Entity::DESCRIPTION     => 'string|max:65535|nullable|utf8', // 65535 bytes is size of mysql's text data type.
        Entity::NOTES           => 'sometimes|notes',
        Entity::SLUG            => 'filled|min:4|max:30|custom',
        Entity::SUPPORT_CONTACT => 'nullable|string|min:8|max:255',
        Entity::SUPPORT_EMAIL   => 'nullable|email',
        Entity::TERMS           => 'nullable|string|min:5|max:2048',
        Entity::SETTINGS        => 'nullable|array',

        Entity::SETTINGS . '.' . Entity::THEME                        => 'nullable|string|in:light,dark',
        Entity::SETTINGS . '.' . Entity::UDF_SCHEMA                   => 'nullable|json',
        Entity::SETTINGS . '.' . Entity::ALLOW_MULTIPLE_UNITS         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::ALLOW_SOCIAL_SHARE           => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_REDIRECT_URL => 'nullable|url',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_MESSAGE      => 'nullable|string|min:5|max:2048',
        Entity::SETTINGS . '.' . Entity::CHECKOUT_OPTIONS             => 'array',
        Entity::SETTINGS . '.' . Entity::PAYMENT_BUTTON_LABEL         => 'string|max:16',

        Entity::PAYMENT_PAGE_ITEMS => 'sometimes|sequential_array|min:1|max:25',
    ];

    protected static $sendNotificationRules = [
        'emails'     => 'required_without:contacts|filled|array|size:1',
        'emails.*'   => 'required|email|max:255',
        'contacts'   => 'required_without:emails|filled|array|size:1',
        'contacts.*' => 'required|contact_syntax|digits_between:8,11',
    ];

    /**
     * Rules for settings.udf_schema.
     * @var array
     */
    protected static $udfSchemaRules = [
        'udf_schema'         => 'array|max:15',
        'udf_schema.*.name'  => 'required|string|max:255',
        'udf_schema.*.type'  => 'required|string|in:string,number',
        'udf_schema.*.title' => 'required|string|max:255',
        // Additional optional parameters are left intentionally, for now at least.
        // This is because there are keys conditioned to type.
    ];

    protected static $uploadImagesRules = [
        'images'     => 'required|array|min:1|max:5',
        'images.*'   => 'required|image|max:2048',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected static $createValidators = [
        Entity::SETTINGS,
        Entity::PAYMENT_PAGE_ITEMS,
    ];

    protected static $editValidators = [
        Entity::SETTINGS,
        'min_amount', // Since currency will not be available in edit PP sending currency from custom func.
    ];

    protected static $createOrderRules = [
        Entity::LINE_ITEMS  => 'required|array|min:1|max:25|custom'
    ];

    protected static $createOrderLineItemRules = [
        Entity::PAYMENT_PAGE_ITEM_ID => 'required|public_id',
        LineItem\Entity::AMOUNT      => 'required|mysql_unsigned_int|custom',
        LineItem\Entity::QUANTITY    => 'sometimes|integer|min:1',
    ];

    public function validateLineItems(string $attribute, array $value)
    {
        $totalAmount = 0;

        $paymentPageItemMandatoryIds = $this->entity
                                            ->paymentPageItems()
                                            ->get()
                                            ->where(PaymentPageItem\Entity::MANDATORY, true)
                                            ->pluck(Entity::ID);

        $paymentPageGivenIds = array_column($value, Entity::PAYMENT_PAGE_ITEM_ID);

        foreach ($paymentPageItemMandatoryIds as $itemMandatoryId)
        {
            $itemMandatoryId = PaymentPageItem\Entity::getSignedId($itemMandatoryId);

            if (in_array($itemMandatoryId, $paymentPageGivenIds) === false)
            {
                throw new BadRequestValidationFailureException(
                    $itemMandatoryId . ' is mandatory payment page item, should be ordered'
                );
            }
        }

        $PPItemId = [];

        foreach ($value as $lineItem)
        {
            $this->validateInput('create_order_line_item', $lineItem);

            if (isset($PPItemId[$lineItem[Entity::PAYMENT_PAGE_ITEM_ID]]) === true)
            {
                throw new BadRequestValidationFailureException(
                    'all payment page item id should be unique'
                );
            }

            $PPItemId[$lineItem[Entity::PAYMENT_PAGE_ITEM_ID]] = true;

            $totalAmount += $lineItem[LineItem\Entity::AMOUNT] *
                ($lineItem[LineItem\Entity::QUANTITY] ?? 1);
        }

        $this->validateAmount('total_amount', $totalAmount);
    }

    /**
     * Validates user provided slug value, allows alpha numeric, _ and - chars.
     * @param  string $attribute
     * @param  string $value
     * @throws BadRequestValidationFailureException
     */
    public function validateSlug(string $attribute, string $value)
    {
        $valid = preg_match('/^[A-Za-z0-9-_]+$/', $value);

        if ($valid !== 1)
        {
            throw new BadRequestValidationFailureException(
                'slug must only contain alpha numeric, _ and - characters',
                Entity::SLUG,
                compact('value'));
        }
    }

    public function validateExpireBy(string $attribute, int $value)
    {
        $now         = Carbon::now(Timezone::IST);
        $minExpireBy = $now->copy()->addSeconds(Entity::MIN_EXPIRY_SECS);

        if ($value < $minExpireBy->getTimestamp())
        {
            $message = 'expire_by should be at least ' . $minExpireBy->diffForHumans($now) . ' current time.';

            throw new BadRequestValidationFailureException($message);
        }
    }


    /**
     * Validates attribute for edit operation. Note that in edit we allow making of times_payable equal to number of
     * times_paid already and while doing so payment link goes to inactive status.
     *
     * @param string   $attribute
     * @param int|null $value
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTimesPayable(string $attribute, int $value = null)
    {
        $paymentLink = $this->entity;

        if (($value !== null) and ($value < $paymentLink->getTimesPaid()))
        {
            throw new BadRequestValidationFailureException(
                'Times payable should be greater than or equal to the number of payments already made',
                Entity::TIMES_PAYABLE,
                [
                    Entity::TIMES_PAYABLE => $value,
                ]);
        }
    }

    /**
     * @param  string   $attribute
     * @param  int|null $amount
     * @throws BadRequestValidationFailureException
     */
    public function validateAmount(string $attribute, int $amount = null)
    {
        $paymentLink = $this->entity;

        if ($amount === null)
        {
            return;
        }

        // If amount is set, validate that it doesn't exceeds max payment amount allowed for merchant
        $maxAmountAllowed = $paymentLink->merchant->getMaxPaymentAmount();

        if ($amount > $maxAmountAllowed)
        {
            throw new BadRequestValidationFailureException(
                $attribute . ' exceeds maximum payment amount allowed',
                $attribute,
                [
                    Entity::ID                          => $paymentLink->getId(),
                    $attribute                          => $amount,
                    Merchant\Entity::MAX_PAYMENT_AMOUNT => $maxAmountAllowed,
                ]);
        }
    }

    public function validateSettings(array $input)
    {
        $settings = $input[Entity::SETTINGS] ?? null;

        if (empty($settings) === true)
        {
            return;
        }

        $extraSettingsKeys = array_values(array_diff(array_keys($settings), Entity::SETTINGS_KEYS));
        if (empty($extraSettingsKeys) === false)
        {
            throw new BadRequestValidationFailureException(
                'Extra settings keys must not be sent - ' . implode(', ', $extraSettingsKeys) . '.',
                Entity::SETTINGS);
        }

        // setting.allow_multiple_units should only be set when amount is sent or exist(for edit requests).
        $amount = $input[Entity::AMOUNT] ?? $this->entity->getAmount();
        $allowMultipleUnits = $settings[Entity::ALLOW_MULTIPLE_UNITS] ?? null;
        if ((empty($allowMultipleUnits) === false) and (empty($amount) === true))
        {
            throw new BadRequestValidationFailureException(
                'amount is required with settings.allow_multiple_units.');
        }

        // Additionally, validates UDF schema
        $udfSchema = json_decode($settings[Entity::UDF_SCHEMA] ?? '{}', true);
        $this->validateInput('udfSchema', [Entity::UDF_SCHEMA => $udfSchema]);
    }

    public function validatePaymentPageItems(array $input)
    {
        if (isset($input[Entity::PAYMENT_PAGE_ITEMS]) === false)
        {
            return;
        }

        if (count($input[Entity::PAYMENT_PAGE_ITEMS]) > self::MAX_ALLOWED_PAYMENT_PAGE_ITEMS)
        {
            throw new BadRequestValidationFailureException(
                'The total number of payment page items may not be greater than ' .
                self::MAX_ALLOWED_PAYMENT_PAGE_ITEMS
            );
        }
    }

    /**
     * Validate times_payable attribute for activation. For activation(unlike edit), it must be greater than times_paid
     *
     * @param int|null $value
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateTimesPayableForActivation(int $value = null)
    {
        $paymentLink = $this->entity;

        if (($value !== null) and ($value <= $paymentLink->getTimesPaid()))
        {
            throw new BadRequestValidationFailureException(
                'Times payable should be greater than the number of payments already made',
                Entity::TIMES_PAYABLE,
                [
                    Entity::TIMES_PAYABLE => $value,
                ]);
        }
    }

    public function validateActivateOperation()
    {
        /** @var Entity $paymentLink */
        $paymentLink = $this->entity;

        if ($paymentLink->isActive() === true)
        {
            $message = 'Payment link cannot be activated as it is already active';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateDeactivateOperation()
    {
        /** @var Entity $paymentLink */
        $paymentLink = $this->entity;

        if ($paymentLink->isInactive() === true)
        {
            $message = 'Payment link cannot be deactivated as it is already inactive';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Validates that payment link's attributes are holding values that confirms to active state requirements.
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateShouldActivationBeAllowed()
    {
        $paymentLink  = $this->entity;
        $expireBy     = $paymentLink->getExpireBy();

        if ($paymentLink->getVersion() !== Version::V2)
        {
            $timesPayable = $paymentLink->getTimesPayable();

            if ($timesPayable !== null)
            {
                $this->validateTimesPayableForActivation($timesPayable);
            }
        }
        else if ($paymentLink->isTimesPayableExhausted() === true)
        {
            throw new BadRequestValidationFailureException(
                'at least one of the payment page item\'s stock should be left to activate payment page'
            );
        }

        if ($expireBy !== null)
        {
            $this->validateExpireBy(Entity::EXPIRE_BY, $expireBy);
        }
    }

    public function validateSendNotification(array $input)
    {
        if ($this->entity->isInactive() === true)
        {
            throw new BadRequestValidationFailureException('Payment link is not active.');
        }

        $this->validateInput('sendNotification', $input);
    }

    /**
     * @param  Payment\Entity $payment
     *
     * @throws BadRequestValidationFailureException
     */
    public function validatePaymentAmount(Payment\Entity $payment)
    {
        $errorMsg                = null;
        $paymentLink             = $this->entity;
        $paymentAmount           = $payment->getAdjustedAmountWrtCustFeeBearer();
        // When the merchant is not customer fee bearer the fee will be calcualted at the time of capture so now the
        // fee will be zero in case of merchant fee bearer.
        $paymentAmountWithoutFee = $payment->getAmount() - $payment->getFee();
        $paymentLinkAmount       = $paymentLink->getAmount();
        $allowMultipleUnits      = (bool) $paymentLink->getSettingsScalarElseNull(Entity::ALLOW_MULTIPLE_UNITS);

        if ($paymentLinkAmount === null)
        {
            return;
        }

        // If payment for multiple units are not allowed, both amount should be same.
        if (($allowMultipleUnits === false) and
            ($paymentLinkAmount !== $paymentAmount) and
            ($payment->hasOrder() === false))
        {
            $errorMsg = 'Payment amount provided does not match amount expected for the payment link.';
        }
        // Else if payment for multiple amounts is allowed and payment.notes.units must(if exists) must
        // contain valid integer value.
        else if (($allowMultipleUnits === true) and
            ($payment->hasOrder() === false))
        {
            $paymentUnits = filter_var($payment->getNotes()[Entity::UNITS] ?? '1', FILTER_VALIDATE_INT);

            if (($paymentUnits === false) or ($paymentUnits < 1))
            {
                $errorMsg = 'Payment notes must contain units which is numeric and greater than equal to 1.';
            }
            else if ($paymentAmountWithoutFee !== ($paymentUnits * $paymentLinkAmount))
            {
                $errorMsg = 'Payment amount should be multiple of number of units and payment link\'s unit amount.';
            }
        }

        if ($errorMsg != null)
        {
            throw new BadRequestValidationFailureException(
                $errorMsg,
                Entity::AMOUNT,
                compact('paymentAmount', 'paymentLinkAmount', 'allowMultipleUnits'));
        }
    }

    public function validateCurrency(string $attribute, string $currency)
    {
        $paymentLink = $this->entity;

        $international = $paymentLink->merchant->isInternational();

        // Non International accounts should not create PL in other currencies.
        if (($international !== true) and ($currency !== Currency::INR))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED,
                null,
                [
                    'currency' => $currency
                ]);
        }
    }

    public function validatePaymentCurrency(Payment\Entity $payment)
    {
        $currency = $payment->getCurrency();

        if ($this->entity->getCurrency() !== $currency)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_LINK_CURRENCY_MISMATCH);
        }
    }

    public function validateMinAmount(array $input)
    {
        if (empty($input[Entity::AMOUNT]) === false)
        {
            $currency = $this->entity->getCurrency();

            $inputAmount = [
                Entity::AMOUNT   => $input[Entity::AMOUNT],
                Entity::CURRENCY => $currency,
            ];

            $this->validateInputValues('min_amount_check', $inputAmount);
        }
    }

    public function validatePaymentLinkToCreateOrder()
    {
        if ($this->entity->getStatus() !== Status::ACTIVE)
        {
            $message = 'order cannot be created for payment page which is not active';

            throw new BadRequestValidationFailureException($message);
        }
    }
}
