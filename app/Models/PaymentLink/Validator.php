<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use App;
use RZP\Base;
use RZP\Models\Currency\Core as CurrencyCore;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Models\Merchant;
use RZP\Models\LineItem;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PaymentLink\Template\UdfSchema;

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
        Entity::CURRENCY        => 'filled|string|currency|custom',
        Entity::EXPIRE_BY       => 'sometimes|epoch|nullable|custom',
        Entity::TIMES_PAYABLE   => 'sometimes|mysql_unsigned_int|min:1|nullable',
        Entity::RECEIPT         => 'string|min:3|max:40|nullable',
        Entity::TITLE           => 'required|string|min:3|max:40',
        Entity::DESCRIPTION     => 'string|max:65535|nullable|utf8', // 65535 bytes is size of mysql's text data type.
        Entity::NOTES           => 'sometimes|notes',
        Entity::SLUG            => 'filled|min:4|max:30', // need to call validate slug separately for regex validation
        Entity::SUPPORT_CONTACT => 'nullable|contact_syntax',
        Entity::SUPPORT_EMAIL   => 'nullable|email',
        Entity::TERMS           => 'nullable|string|min:5|max:2048',
        Entity::SETTINGS        => 'nullable|array',
        Entity::TEMPLATE_TYPE   => 'sometimes|string|max:24',
        Entity::VIEW_TYPE       => 'sometimes|string|custom',

        Entity::SETTINGS . '.' . Entity::THEME                                => 'nullable|string|in:light,dark',
        Entity::SETTINGS . '.' . Entity::UDF_SCHEMA                           => 'nullable|json',
        Entity::SETTINGS . '.' . Entity::ALLOW_MULTIPLE_UNITS                 => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::ALLOW_SOCIAL_SHARE                   => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_REDIRECT_URL         => 'nullable|url',
        Entity::SETTINGS . '.' . Entity::PAYMENT_SUCCESS_MESSAGE              => 'nullable|string|min:5|max:2048',
        Entity::SETTINGS . '.' . Entity::CHECKOUT_OPTIONS                     => 'array',
        Entity::SETTINGS . '.' . Entity::PAYMENT_BUTTON_LABEL                 => 'string|max:20',
        Entity::SETTINGS . '.' . Entity::PP_BUTTON_DISABLE_BRANDING           => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_BUTTON_THEME                      => 'nullable|string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_BUTTON_TEXT                       => 'string|max:20',
        Entity::SETTINGS . '.' . Entity::PAYMENT_BUTTON_TEMPLATE_TYPE         => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_FB_PIXEL_TRACKING_ID              => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_GA_PIXEL_TRACKING_ID              => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_ADD_TO_CART_ENABLED      => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_INITIATE_PAYMENT_ENABLED => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_PAYMENT_COMPLETE         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::GOAL_TRACKER                         => 'nullable|array',
        Entity::SETTINGS . '.' . Entity::PARTNER_WEBHOOK_SETTINGS             => 'nullable|array',
        Entity::PAYMENT_PAGE_ITEMS => 'required|sequential_array|min:1',
    ];

    protected static $editRules = [
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
        Entity::SETTINGS . '.' . Entity::PAYMENT_BUTTON_LABEL         => 'string|max:20',
        Entity::SETTINGS . '.' . Entity::PP_BUTTON_DISABLE_BRANDING   => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_BUTTON_THEME              => 'nullable|string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_BUTTON_TEXT               => 'string|max:16',
        Entity::SETTINGS . '.' . Entity::PP_FB_PIXEL_TRACKING_ID              => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_GA_PIXEL_TRACKING_ID              => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_ADD_TO_CART_ENABLED      => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_INITIATE_PAYMENT_ENABLED => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_PAYMENT_COMPLETE         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::GOAL_TRACKER                         => 'nullable|array',
        Entity::SETTINGS . '.' . Entity::PARTNER_WEBHOOK_SETTINGS             => 'nullable|array',

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
        'images.*'   => 'required|mimes:jpg,jpeg,png,gif,bmp,svg|max:2048',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|mysql_unsigned_int|min_amount'
    ];

    protected static $createSubscriptionRules = [
        Entity::PAYMENT_PAGE_ITEM_ID => 'required|string|size:18',
        Entity::NOTES       => 'sometimes|notes',
    ];

    protected static $createValidators = [
        Entity::SETTINGS,
        Entity::PAYMENT_PAGE_ITEMS,
        Entity::GOAL_TRACKER,
        Entity::PARTNER_WEBHOOK_SETTINGS,
    ];

    protected static $editValidators = [
        Entity::SETTINGS,
        'min_amount', // Since currency will not be available in edit PP sending currency from custom func.
        Entity::GOAL_TRACKER,
        Entity::PARTNER_WEBHOOK_SETTINGS,
    ];

    protected static $createOrderRules = [
        Entity::LINE_ITEMS  => 'array|custom|min:1|max:25',
        Entity::NOTES       => 'sometimes|notes',
    ];

    protected static $createOrderLineItemRules = [
        Entity::PAYMENT_PAGE_ITEM_ID => 'required|public_id',
        LineItem\Entity::AMOUNT      => 'required|mysql_unsigned_int|custom',
        LineItem\Entity::QUANTITY    => 'sometimes|integer|min:1',
    ];

    protected static $setMerchantDetailsRules = [
        Entity::TEXT_80G_12A    => 'sometimes|string|max:2048',
        Entity::IMAGE_URL_80G   => 'sometimes|nullable|string|max:512',
    ];

    protected static $setInvoiceDetailsRules = [
        Entity::RECEIPT_ENABLE       => 'sometimes|boolean',
        Entity::SELECTED_INPUT_FIELD => 'sometimes|string|custom',
        Entity::CUSTOM_SERIAL_NUMBER => 'sometimes|boolean',
        Entity::ENABLE_80G_DETAILS   => 'sometimes|boolean',
    ];

    protected static $saveReceiptRules = [
       Invoice\Entity::RECEIPT => 'required|string|min:1|max:40',
    ];

    protected static $saveReceiptIfPresentRules = [
        Invoice\Entity::RECEIPT => 'sometimes|string|min:1|max:40',
    ];

    protected static $createPaymentHandleRules = [
        Entity::TITLE           => 'required|string|min:3|max:40',
        Entity::SLUG            => 'required|min:4|max:30',
        Entity::CURRENCY        => 'filled|string|currency',
    ];

    protected static $updatePaymentHandleRules = [
        Entity::SLUG            => 'required',
    ];

    /**
     * Rules for settings.goal_tracker.
     * @var string[]
     */
    protected static $goalTrackerRules = [
        Entity::TRACKER_TYPE    => 'required|string|custom',
        Entity::GOAL_IS_ACTIVE  => 'required|string|in:0,1',
        Entity::META_DATA       => 'required|array',

        Entity::META_DATA.'.'.Entity::AVALIABLE_UNITS           => 'required_if:'.Entity::META_DATA.'.'.Entity::DISPLAY_AVAILABLE_UNITS.',1',
        Entity::META_DATA.'.'.Entity::DISPLAY_AVAILABLE_UNITS   => 'required_if:'.Entity::TRACKER_TYPE.','.DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
        Entity::META_DATA.'.'.Entity::DISPLAY_SOLD_UNITS        => 'required_if:'.Entity::TRACKER_TYPE.','.DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
        Entity::META_DATA.'.'.Entity::GOAL_AMOUNT               => 'required_if:'.Entity::TRACKER_TYPE.','.DonationGoalTrackerType::DONATION_AMOUNT_BASED,
        Entity::META_DATA.'.'.Entity::GOAL_END_TIMESTAMP        => 'required_if:'.Entity::META_DATA.'.'.Entity::DISPLAY_DAYS_LEFT.',1',
        Entity::META_DATA.'.'.Entity::DISPLAY_DAYS_LEFT         => 'required',
        Entity::META_DATA.'.'.Entity::DISPLAY_SUPPORTER_COUNT   => 'required',
    ];

    /**
     * Rules to validate goal tracker meta data
     * @var string[]
     */
    protected static $metaDataRules = [
        Entity::GOAL_END_TIMESTAMP      => 'epoch',
        Entity::AVALIABLE_UNITS         => 'numeric|min:1|max:4294967295',
        Entity::DISPLAY_AVAILABLE_UNITS => 'string|in:0,1',
        Entity::DISPLAY_SOLD_UNITS      => 'string|in:0,1',
        Entity::GOAL_AMOUNT             => 'mysql_unsigned_int|min_amount',
        Entity::DISPLAY_DAYS_LEFT       => 'string|in:0,1',
        Entity::DISPLAY_SUPPORTER_COUNT => 'string|in:0,1',
    ];

    /**
     * Rules to validate partner webhook settings
     * @var string[]
     */
    protected static $partnerWebhookSettingsRules = [
        Entity::PARTNER_SHIPROCKET  => 'string|in:0,1'
    ];

    /**
     * Rules to validate the payment handle encryption
     * @var string
     */
    protected static $encryptAmountForPaymentHandleRules = [
        Entity::AMOUNT => 'required|mysql_unsigned_int'
    ];

    /**
     * @var array fields for shiprocket
     */
    protected static $partnerShiprocketUdfs = [
        Entity::NAME,
        Entity::CITY,
        Entity::STATE,
        Entity::PINCODE,
        Entity::ADDRESS,
    ];

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\BadRequestException
     */
    public function validateLineItems(string $attribute, $value)
    {
        if(is_array($value) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                null,
                null,
                'line items must be array');
        }

        if(empty($value) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                null,
                null,
                'Please select an amount to pay.');
        }

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
            if (isset($lineItem[Entity::AMOUNT]) === true && filter_var($lineItem[Entity::AMOUNT], FILTER_VALIDATE_INT) === false)
            {
                throw new BadRequestValidationFailureException(
                    trans("validation.mysql_unsigned_int", ['attribute' => Entity::AMOUNT]),
                    $attribute. "." . Entity::AMOUNT,
                    [
                        $attribute. "." . Entity::AMOUNT => $lineItem[Entity::AMOUNT]
                    ]);
            }

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

        if ($valid !== 1 )
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

        $baseAmount = $amount;

        if(isset($paymentLink['currency']) === true)
        {
            $currency = $paymentLink['currency'];

            if ($currency != Currency::INR)
            {
                $baseAmount = (new CurrencyCore)->getBaseAmount($amount, $currency);
            }
        }

        if ($baseAmount > $maxAmountAllowed)
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

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateGoalTracker(array $input)
    {
        $tracker = array_get($input, Entity::SETTINGS . '.' . Entity::GOAL_TRACKER, []);

        if (count($tracker) <= 0) {
            return;
        }

        $this->validateInput('goalTracker', $tracker);

        $metadata = array_get($tracker, Entity::META_DATA, []);
        if (count($metadata) <= 0) {
            return;
        }

        $endTimeStamp = array_get($metadata, Entity::GOAL_END_TIMESTAMP);

        $this->validateInput('metaData', $metadata);

        if ($tracker[Entity::GOAL_IS_ACTIVE] === '1' && empty($endTimeStamp) === false)
        {
            $this->validateGoalEndTimestamp($endTimeStamp);
        }
    }

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateGoalEndTimestamp($value)
    {
        $now    = Carbon::now(Timezone::IST);
        $future = $now->copy()->addMinutes(30);
        if ($now->getTimestamp() > $value)
        {
            $message = Entity::GOAL_END_TIMESTAMP. ' should be at least ' . $future->diffForHumans($now) . ' current time.';

            throw new BadRequestValidationFailureException($message);
        }
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

    public function validateSlugUnique($slug)
    {
        $app          = App::getFacadeRoot();

        $gimli        = $app['elfin']->driver('gimli');

        $slugMetadata = $gimli->expandAndGetMetadata($slug);

        if ($slugMetadata !== null)
        {
            throw new BadRequestValidationFailureException(
                'Slug already taken.'
            );
        }
    }

    // custom validations as these error messages will be displayed directly on  mobile app
    public function isValidPaymentHandle($slug)
    {
        if(strlen($slug) < 4)
        {
            throw new BadRequestValidationFailureException(
                'Enter at least 4 characters'
            );
        }

        if(strlen($slug) > 30)
        {
            throw new BadRequestValidationFailureException(
            'Enter up to 30 characters only'
            );
        }

        if($slug[0] !== '@')
        {
            throw new BadRequestValidationFailureException(
                'Name must contain @ at beginning'
            );
        }

        $valid = preg_match('/^@+[A-Za-z0-9-_]+$/', $slug);

        if($valid !== 1)
        {
            throw new BadRequestValidationFailureException(
                'Enter only alphabets (a-z), numbers (0-9), hyphen (-) and underscore (_)'
            );
        }
    }

    public function validatePaymentHandleCreatedForMerchant(Merchant\Entity $merchant)
    {
        $merchantSetting = Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)->all();

        $handlePageId = array_get($merchantSetting, ENTITY::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID);

        if (empty($handlePageId) === false)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle already created for this merchant',
                null,
                null);
        }
    }

    public function validatePaymentHandleCreation(array $input,Merchant\Entity $merchant )
    {
        $this->isValidPaymentHandle($input[Entity::SLUG]);

        $this->validatePaymentHandleCreatedForMerchant($merchant);

        $this->validateSlugUnique($input[Entity::SLUG]);
    }

    public function validatePaymentHandleUpdation(array $input)
    {
        $this->validateSlugUnique($input[Entity::SLUG]);

        $this->isValidPaymentHandle($input[Entity::SLUG]);
    }

    public function validatePaymentHandleSuggestionCount($count)
    {
        if($count > 10 or $count < 1)
            {
                throw new BadRequestValidationFailureException(
                    'value of count should be atleast 1 and atmost 10');
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

    public function validatePaymentHandleExistsForMerchant(Merchant\Entity $merchant)
    {
        $merchantSetting = Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)->all();

        if (!empty($merchantSetting) === true && !empty($merchantSetting[ENTITY::DEFAULT_PAYMENT_HANDLE]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle already exists for this merchant',
                null,
                null);
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

    public function validateCurrency(string $attribute, string $currency)
    {
        $paymentLink = $this->entity;

        $merchant = $paymentLink->merchant;

        // Non International accounts should not create PL in other currencies.
        if ((($merchant->convertOnApi() === null) and
            ($currency !== Currency::INR)) or
            (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false))
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

    public function validateSetMerchantDetails(array $input)
    {
        $this->validateInput('setMerchantDetails', $input);
    }

    public function validateSetInvoiceDetails(array $input)
    {
        $this->validateInput('setInvoiceDetails', $input);
    }

    public function validateSelectedUdfField(string $attribute,string $value)
    {
        $udfSchemaClass = new UdfSchema($this->entity);

        $udfSchemaJson = $udfSchemaClass->getSchema();

        $udfSchema = json_decode($udfSchemaJson);

        foreach ($udfSchema as $udf)
        {
            if($udf->name === $value)
            {
                return;
            }
        }
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        null,
        null,
        'Input field not present');
    }

    public function validateViewType(string $attribute,string $value)
    {
        ViewType::checkViewType($value);
    }

    public function validatePageViewable(Entity $paymentLink)
    {
        if ($paymentLink->merchant->isSuspended() === true)
        {
            $app = App::getFacadeRoot();

            $merchantDetails = $paymentLink->getMerchantSupportDetails();

            $orgBrandingDetails = $paymentLink->getMerchantOrgBrandingDetails();

            $data['merchant'] = $merchantDetails;

            $data['org'] = $orgBrandingDetails;

            $data['entity'] = [Entity::ID => $paymentLink->getPublicId()];

            $data['mode'] = $app['rzp.mode'];

            $data['view_type'] = $paymentLink->getViewType();

            throw new BadRequestValidationFailureException("This account is suspended", null, $data);
        }
    }

    /**
     * Validates tracker type
     * @param  string $attribute
     * @param  string $value
     * @throws BadRequestValidationFailureException
     */
    public function validateTrackerType(string $attribute, string $value)
    {
        DonationGoalTrackerType::checkType($value);
    }

    public function validatePaymentHandleAndHost(Entity $paymentLink, string $host)
    {
        if(($paymentLink->getViewType() === ViewType::PAYMENT_HANDLE)
        and ($host !== config('app.payment_handle_domain')))
        {
            $data[Entity::VIEW_TYPE] = $paymentLink->getViewType();

            throw new BadRequestValidationFailureException("Handle cannot be opened on this url", null, $data);
        }
    }

    /**
     * @param array $input
     *
     * @return void
     * @throws \RZP\Exception\BadRequestException
     */
    public function validatePartnerWebhookSettings(array $input)
    {
        $partnerWebhooksettings = array_get($input, Entity::SETTINGS . '.' . Entity::PARTNER_WEBHOOK_SETTINGS, []);

        if (count($partnerWebhooksettings) <= 0) {
            return;
        }

        $this->validateInput(camel_case(strtolower(Entity::PARTNER_WEBHOOK_SETTINGS)), $partnerWebhooksettings);

        $udfSchemaStr = array_get($input, Entity::SETTINGS . '.' . Entity::UDF_SCHEMA, "{}");

        $this->validatePartnerSpecificUdfs($partnerWebhooksettings, $udfSchemaStr);
    }

    /**
     * @param array  $partnerWebhooksettings
     * @param string $udfSchemaStr
     *
     * @return void
     * @throws \RZP\Exception\BadRequestException
     */
    public function validatePartnerSpecificUdfs(array $partnerWebhooksettings, string $udfSchemaStr)
    {
        $parsedUdfSchema = json_decode($udfSchemaStr, true);

        $udfSchemaNames = collect($parsedUdfSchema)
            ->reduce(function ($carrier, $item) {
                $carrier[] = $item['name'];

                return $carrier;
            }, []);

        foreach ($partnerWebhooksettings as $partner => $enabledString)
        {
            if ($enabledString !== "1")
            {
                continue;
            }

            $partnerFields = $this->getPartnerSpecificFields($partner);

            if (empty($partnerFields) === true)
            {
                continue;
            }

            $commonArray = array_intersect($udfSchemaNames, $partnerFields);

            if (count($commonArray) !== count($partnerFields))
            {
                $message = implode(", ", $partnerFields)
                    . ' Fields should be added for '
                    . str_replace("_", " ", $partner);

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                    null,
                    null,
                    $message);
            }
        }
    }

    /**
     * @param string $partner
     *
     * @return array
     */
    private function getPartnerSpecificFields(string $partner): array
    {
        $names = [];

        $partnerVar = camel_case(strtolower($partner)) . "Udfs";

        if (isset(static::$$partnerVar) !== true)
        {
            return $names;
        }

        return static::$$partnerVar;
    }
}
