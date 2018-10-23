<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
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
    protected static $createRules = [
        Entity::AMOUNT          => 'required_with:currency,settings.allow_multiple_units|nullable|mysql_unsigned_int|min:100|custom',
        Entity::CURRENCY        => 'required_with:amount|nullable|in:INR',
        Entity::EXPIRE_BY       => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE   => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::RECEIPT         => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE           => 'required|filled|string|max:40',
        Entity::DESCRIPTION     => 'sometimes|string|max:2048|nullable',
        Entity::NOTES           => 'sometimes|notes',
        Entity::SLUG            => 'filled|alpha_num|min:4|max:30',
        Entity::SUPPORT_CONTACT => 'filled|string|min:8|max:255',
        Entity::SUPPORT_EMAIL   => 'filled|email',
        Entity::TERMS           => 'filled|string|min:5|max:2048',
        Entity::SETTINGS        => 'filled|array|custom',

        Entity::SETTINGS . '.' . Entity::UDF_SCHEMA                   => 'nullable|json',
        Entity::SETTINGS . '.' . Entity::ALLOW_MULTIPLE_UNITS         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::ALLOW_SOCIAL_SHARE           => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_REDIRECT_URL => 'nullable|url',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_MESSAGE      => 'nullable|string|min:5|max:2048',
    ];

    protected static $editRules = [
        Entity::AMOUNT          => 'required_with:currency,settings.allow_multiple_units|nullable|mysql_unsigned_int|min:100|custom',
        Entity::CURRENCY        => 'required_with:amount|nullable|in:INR',
        Entity::EXPIRE_BY       => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE   => 'sometimes|mysql_unsigned_int|min:1|nullable|custom',
        Entity::RECEIPT         => 'sometimes|string|min:1|max:40|nullable',
        Entity::TITLE           => 'filled|string|max:40',
        Entity::DESCRIPTION     => 'sometimes|string|max:2048|nullable',
        Entity::NOTES           => 'sometimes|notes',
        Entity::SLUG            => 'filled|alpha_num|min:4|max:30',
        Entity::SUPPORT_CONTACT => 'filled|string|min:8|max:255',
        Entity::SUPPORT_EMAIL   => 'filled|email',
        Entity::TERMS           => 'filled|string|min:5|max:2048',
        Entity::SETTINGS        => 'filled|array|custom',

        Entity::SETTINGS . '.' . Entity::UDF_SCHEMA                   => 'nullable|json',
        Entity::SETTINGS . '.' . Entity::ALLOW_MULTIPLE_UNITS         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::ALLOW_SOCIAL_SHARE           => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_REDIRECT_URL => 'nullable|url',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_MESSAGE      => 'nullable|string|min:5|max:2048',
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
                'Amount exceeds maximum payment amount allowed',
                Entity::AMOUNT,
                [
                    Entity::ID                          => $paymentLink->getId(),
                    Entity::AMOUNT                      => $amount,
                    Merchant\Entity::MAX_PAYMENT_AMOUNT => $maxAmountAllowed,
                ]);
        }
    }

    /**
     * Settings should have defined set of keys and it's udf_schema's value should be a valid JSON schema.
     * @param  string $attribute
     * @param  array  $value
     */
    public function validateSettings(string $attribute, array $value)
    {
        $extraSettingsKeys = array_values(array_diff(array_keys($value), Entity::SETTINGS_KEYS));
        if (empty($extraSettingsKeys) === false)
        {
            throw new BadRequestValidationFailureException(
                'Extra settings keys must not be sent - ' . implode(', ', $extraSettingsKeys) . '.');
        }

        // Additionally, validates udf schema
        $udfSchema = json_decode($value[Entity::UDF_SCHEMA] ?? '{}', true);
        $this->validateInput('udfSchema', [Entity::UDF_SCHEMA => $udfSchema]);
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
        $timesPayable = $paymentLink->getTimesPayable();
        $expireBy     = $paymentLink->getExpireBy();

        if ($timesPayable !== null)
        {
            $this->validateTimesPayableForActivation($timesPayable);
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
     * @throws BadRequestException
     */
    public function validatePaymentAmount(Payment\Entity $payment)
    {
        $errorMsg                = null;
        $paymentLink             = $this->entity;
        $paymentAmount           = $payment->getAdjustedAmountWrtCustFeeBearer();
        $paymentAmountWithoutFee = $payment->getAmount() - $payment->getFee();
        $paymentLinkAmount       = $paymentLink->getAmount();
        $allowMultipleUnits      = (bool) $paymentLink->getSettingsScalarElseNull(Entity::ALLOW_MULTIPLE_UNITS);

        if ($paymentLinkAmount === null)
        {
            return;
        }

        // If payment for multiple units are not allowed, both amount should be same.
        if (($allowMultipleUnits === false) and ($paymentLinkAmount !== $paymentAmount))
        {
            $errorMsg = 'Payment amount provided does not match amount expected for the payment link.';
        }
        // Else if payment for multiple amounts is allowed and payment.notes.units must(if exists) must contain valid integer value.
        else if ($allowMultipleUnits === true)
        {
            $paymentUnits = filter_var($payment->getNotes()[Entity::UNITS] ?? '1', FILTER_VALIDATE_INT);

            if (($paymentUnits === false) or ($paymentUnits < 1))
            {
                $errorMsg = 'Payment notes must contain units which is numeric and greater than equal to 1.';
            }
            else if ($paymentAmountWithoutFee !== ($paymentUnits * $paymentLinkAmount))
            {
                $errorMsg = 'Payment amount should be multiple of units and per payment link\'s amount.';
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
}
