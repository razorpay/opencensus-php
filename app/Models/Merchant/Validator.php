<?php

namespace RZP\Models\Merchant;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class Validator extends Base\Validator
{
    // Maximum image size - 1M.
    const MAXIMAGESIZE = 1024 * 1024;

    const EXTENSIONMIMEMAP = [
        "jpeg"  => "image/jpeg",
        "jpg"   => "image/jpeg",
        "png"   => "image/png",
    ];

    protected static $createRules = [
        Entity::ID                          => 'sometimes|alpha_num|size:14|unique:merchants',
        Entity::NAME                        => 'sometimes|string|max:200',
        Entity::EMAIL                       => 'required|email',
        Entity::ORG_ID                      => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                      => 'sometimes|array',
        Entity::ADMINS                      => 'sometimes|array',
        Entity::COUPON_CODE                 => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME                        => 'sometimes|string|max:200',
        Entity::HOLD_FUNDS                  => 'sometimes|in:0,1',
        Entity::WEBSITE                     => 'sometimes|url|max:255',
        Entity::CATEGORY                    => 'sometimes|numeric|digits:4',
        Entity::CATEGORY2                   => 'sometimes|string|max:30|custom',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::BILLING_LABEL               => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|array',
        Entity::RECEIPT_EMAIL_ENABLED       => 'sometimes|boolean',
        Entity::LINKED_ACCOUNT_KYC          => 'sometimes|boolean',
        Entity::CHANNEL                     => 'sometimes|string|max:32|custom',
        Entity::RISK_RATING                 => 'sometimes|min:0|max:5',
        Entity::RISK_THRESHOLD              => 'sometimes|integer|min:0|max:20',
        Entity::FEE_BEARER                  => 'sometimes|in:customer,platform',
        Entity::FEE_MODEL                   => 'sometimes|in:prepaid,postpaid',
        Entity::REFUND_SOURCE               => 'sometimes|string|max:32|in:balance,credits',
        Entity::MAX_PAYMENT_AMOUNT          => 'sometimes|integer',
        // max: 5 days (don't change max value without consult), min:60 minutes
        Entity::AUTO_REFUND_DELAY           => 'sometimes|string|custom',
        Entity::AUTO_CAPTURE_LATE_AUTH      => 'sometimes|boolean',
        Entity::CONVERT_CURRENCY            => 'sometimes|nullable|boolean',
        Entity::ORG_ID                      => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                      => 'sometimes|array',
        Entity::ADMINS                      => 'sometimes|array',
        Entity::WHITELISTED_IPS_LIVE        => 'sometimes|array|max:5',
        Entity::WHITELISTED_IPS_LIVE . '.*' => 'required_with:' . Entity::WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::WHITELISTED_IPS_TEST        => 'sometimes|array|max:5',
        Entity::WHITELISTED_IPS_TEST . '.*' => 'required_with:' . Entity::WHITELISTED_IPS_TEST . '|ipv4',
    ];

    protected static $uniqueEmailRules = [
        Entity::EMAIL                       => 'required|email|unique:merchants'
    ];

    protected static $editCreditsRules = [
        Balance\Entity::AMOUNT_CREDITS      => 'required|integer|min:0|max:50000000'
    ];

    protected static $editEmailRules = [
        Entity::EMAIL                       => 'required|email|unique:merchants'
    ];

    protected static $editNameRules = [
        Entity::NAME                        => 'required|min:4|string|max:200',
    ];

    protected static $editConfigRules = [
        Entity::BRAND_COLOR              => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Entity::TRANSACTION_REPORT_EMAIL => 'sometimes|array',
        Entity::LOGO_URL                 => 'sometimes|max:2000',
        Entity::INVOICE_LABEL_FIELD      => 'sometimes|filled|string|max:50|in:business_name,business_dba',
        Entity::AUTO_CAPTURE_LATE_AUTH   => 'sometimes|boolean',
        Entity::HANDLE                   => 'sometimes|nullable|min:3|max:4|custom|unique:merchants,handle,null',
        MerchantDetail::GSTIN            => 'sometimes|nullable|string|size:15',
        MerchantDetail::P_GSTIN          => 'sometimes|nullable|string',
    ];

    protected static $actionRules = [
        Entity::ACTION                      => 'required|custom'
    ];

    protected static $bulkTagRules = [
        'action'         => 'required|string|filled|max:10|in:insert,delete',
        'name'           => 'required|string|filled',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|max:14'
    ];

    protected static $oauthMailRules = [
        'client_id'    => 'required|alpha_num|size:14',
        'user_id'      => 'required|alpha_num|size:14',
        'merchant_id'  => 'required|alpha_num|size:14'
    ];

    protected static $featureRules = [
        'features'                   => 'required|array',
        'optout_reason'              => 'sometimes|string|max:200',
        Feature\Entity::SHOULD_SYNC  => 'sometimes|boolean',
    ];

    protected static $addTagsRules = [
        'tags'   => 'required|array',
        'tags.*' => 'required|string',
    ];

    protected static $updateMerchantsBulkRules = [
        'merchant_ids' => 'required|sequential_array',
        'attributes'   => 'sometimes|associative_array',
        'action'       => 'sometimes',
    ];

    protected static $keyAccessRules = [
        Entity::HAS_KEY_ACCESS => 'required|boolean',
    ];

    protected static $updateChannelRules = [
        'channel'       => 'required|string|max:32|custom',
        'merchant_ids'  => 'required|array'
    ];

    protected static $updateBankAccountRules = [
        'bank_account'   => 'required|array',
        'merchant_ids'   => 'required|array'
    ];

    protected static $createBatchRules = [
        'type'        => 'required|string|max:50',
        'data'        => 'required|array'
    ];

    protected static $payoutMailRules = [
        'content'               => 'required|array',
        'content.*.merchant_id' => 'required',
        'content.*.email'       => 'required',
    ];

    protected static $irctcRules = [
        'refund'     => 'sometimes|filled|file|mimes:txt|max:1024',
        'settlement' => 'sometimes|filled|file|mimes:txt|max:1024',
    ];

    protected static $createSubMerchantUserRules = [
        'merchant_id'           => 'required|alpha_num|size:14',
        // TODO: Remove the following 2 lines after dashboard changes. These don't get used.
        'password'              => 'sometimes|between:7,50|confirmed|numbers|letters',
        'password_confirmation' => 'sometimes|between:7,50',
        Entity::EMAIL           => 'required|email',
    ];

    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
    ];

    protected static $featureValidators = [
        'visible_features',
        'uneditable_features',
    ];

    protected static $createSubMerchantUserValidators = [
        'sub_merchant_owner',
    ];

    protected static $editEmailValidators = [
        'is_test_account',
    ];

    protected static $keyAccessValidators = [
        'key_access',
    ];

    protected function validateIsTestAccount(array $input)
    {
        $merchant = $this->entity;

        $isTestAccount = (new Account)->isTestAccount($merchant->getId());

        if ($isTestAccount === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT);
        }

    }

    /**
     * validates if the user who is attempting to create a submerchant user is the owner  or not.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateSubMerchantOwner(array $input)
    {
        $app = App::getFacadeRoot();

        $dashboardHeaders = $app['basicauth']->getDashboardHeaders();

        if ($dashboardHeaders['user_role'] !== 'owner')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBUSER_CREATION_NOT_ALLOWED);
        }
    }

    /**
     * Throws an exception if a merchant tries to enable an uneditable
     * feature for live mode, or via the should_sync flag
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateUneditableFeatures(array $input)
    {
        $requestedFeatures = array_keys($input['features']);

        $shouldSync = (bool) ($input[Feature\Entity::SHOULD_SYNC] ?? false);

        $uneditableFeatures = array_values(array_intersect($requestedFeatures,
            Feature\Constants::PRODUCT_FEATURES));

        if ((count($uneditableFeatures) > 0) and (
            ($this->isLiveMode() === true) or
            ($shouldSync === true)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
                Feature\Entity::NAMES,
                ['features' => $uneditableFeatures, 'should_sync' => $shouldSync]);
        }
    }

    protected function validateChannel($attribute, $channel)
    {
        if (Settlement\Channel::exists($channel) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid channel name: ' . $channel);
        }
    }

    public function validateKeyAccess(array $input)
    {
        $merchant = $this->entity;

        if (empty($input[Entity::HAS_KEY_ACCESS]) === true)
        {
            return;
        }

        if (empty($merchant->merchantDetail->getWebsite()) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Key access cannot be granted with out website details');
        }
    }

    protected function validateHandle($attribute, $handle)
    {
        if ($handle !== null)
        {
            if ($handle !== strtoupper($handle))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_HANDLE_UPPERCASE_ONLY);
            }
        }
    }

    public function validateLogo($imageDetails)
    {
        $fileSize = $imageDetails['size'];
        $width    = $imageDetails['width'];
        $height   = $imageDetails['height'];

        // File size should not be more than 1M.
        if ($fileSize > self::MAXIMAGESIZE)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG);
        }

        // The image should be square
        if ($width !== $height)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE
            );
        }

        // The minimum dimensions should be 256*256
        if ($width < 256)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL
            );
        }
    }

    public function validateImage($mimeType, $extension)
    {
        $acceptedMimeArray = self::EXTENSIONMIMEMAP;

        // Checks if extension is defined in the array and if the extension and mime type match.
        if ((!isset($acceptedMimeArray[$extension])) or
            ($acceptedMimeArray[$extension] !== $mimeType))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE
            );
        }
    }

    public function validateCategory2($attribute, $value)
    {
        $category = $value;

        if (Terminal\Category::isMerchantCategoryValid($category) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category : '.$category.' invalid for merchant',
                Entity::CATEGORY2
            );
        }
    }

    public function validateMerchantForMarketplaceTransfer($account, $mode)
    {
        if (($account === null) or
            ($account->isLinkedAccount() === false) or
            ($account->getParentId() !== $this->entity->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID,
                'transfers.account'
            );
        }

        if (($mode === Mode::LIVE) and
            ($account->isActivated() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED
            );
        }
    }

    /**
     * Submerchant creation without providing an email explicitly is only allowed if
     * 1. The partner is of type fully-managed
     * 2. The partner is of type aggregator and has the feature allowing optional emails
     * 3. The merchant is not a partner but has aggregator feature (for backward compatibility)
     *
     * @param  array $input
     * @param  bool  $linkedAccount
     *
     * @throws Exception\BadRequestException
     */
    public function validateSubMerchantInput(array $input, bool $linkedAccount)
    {
        if (empty($input['email']) === true)
        {
            $this->validateEmptyEmailFlow($input, $linkedAccount);
        }
        else
        {
            $this->validateInput('unique_email', array_only($input, 'email'));
        }

        $this->validateInput('edit_name', array_only($input, 'name'));
    }

    protected function validateEmptyEmailFlow(array $input, bool $linkedAccount)
    {
        /** @var Entity $merchant */
        $merchant = $this->entity;

        //
        // Allow empty email if
        // 1. Marketplace is making linked account create request
        // 2. Else, a. Is a partner that is allowed optional email
        //          b. Is not a partner but has aggregator feature.
        //
        if (($merchant->isMarketplace() and $linkedAccount) === true)
        {
            return;
        }

        if ($merchant->isPartner() === true)
        {
            if (($merchant->isFullyManagedPartner() === false) and
                ($merchant->isOptionalEmailAllowedAggregator() === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED);
            }
        }
        else
        {
            if ($merchant->hasAggregatorFeature() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED);
            }
        }
    }

    protected function validateCsvEmail($input)
    {
        if (empty($input[Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            return;
        }

        $emails = $input[Entity::TRANSACTION_REPORT_EMAIL];

        foreach ($emails as $email)
        {
            $email = trim($email); // Remove whitespace
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided transaction report email is invalid: $email",
                    Entity::TRANSACTION_REPORT_EMAIL
                );
            }
        }
    }

    public function validateBeforeActivate()
    {
        $merchant = $this->entity;

        if ($merchant->merchantDetail->isSubmitted() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ACTIVATION_FORM_NOT_SUBMITTED);
        }

        if ($merchant->isActivated() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        if ($merchant->merchantDetail->isArchived() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_UNARCHIVE_BEFORE_ACTIVATION);
        }

        // Don't validate these rest of the attributes for Marketplace accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $attributes = [
            Entity::CATEGORY,
            Entity::BILLING_LABEL,
            Entity::TRANSACTION_REPORT_EMAIL
        ];

        $website = $merchant->merchantDetail->getWebsite();

        // If the merchant has submitted activation form with website/app data
        // i.e merchant will have access to keys.
        // or if website is not null [this check to be removed later]
        if (($merchant->getHasKeyAccess() === true) or
            (isset($website) === true))
        {
            $attributes[] = Entity::WEBSITE;
        }

        foreach ($attributes as $attribute)
        {
            $value = $merchant->getAttribute($attribute);

            if (empty($value) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Please set value for attribute: ' . $attribute);
            }
        }
    }

    public function validateVisibleFeatures(array $input)
    {
        $featureNames = array_keys($input['features']);

        $visibleFeatures = array_keys(Feature\Constants::$visibleFeaturesMap);

        foreach ($featureNames as $feature)
        {
            if (in_array($feature, $visibleFeatures, true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                    'feature',
                    [$feature]);
            }
        }
    }

    protected function validateAutoRefundDelay($attribute, $autoRefundDelayPeriod)
    {
        if ($autoRefundDelayPeriod === null)
        {
            return;
        }

        $autoRefundDelay = explode(' ', $autoRefundDelayPeriod);

        $min = $max = null;
        $time = $autoRefundDelay[0];
        $duration = $autoRefundDelay[1];

        if (filter_var($time, FILTER_VALIDATE_INT) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Auto refund delay time period should be an integer', $attribute, $time);
        }

        switch ($duration)
        {
            case 'mins':
                $min = (int) (Entity::MIN_AUTO_REFUND_DELAY / 60);
                $max = (int) (Entity::MAX_AUTO_REFUND_DELAY / 60);
                break;

            case 'hours':
                $min = (int) (ceil(Entity::MIN_AUTO_REFUND_DELAY / 3600));
                $max = (int) (ceil(Entity::MAX_AUTO_REFUND_DELAY / 3600));
                break;

            case 'days':
                $min = (int) (ceil(Entity::MIN_AUTO_REFUND_DELAY / 86400));
                $max = (int) (ceil(Entity::MAX_AUTO_REFUND_DELAY / 86400));
                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Auto refund delay should be in mins, hours or days', $attribute, $duration);
        }

        if (($time < $min) or ($time > $max))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Auto refund delay should be between ' . $min . ' and ' . $max . ' ' . $duration);
        }
    }

    protected function validateMerchantDetailExists($merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS);
        }
    }

    protected function validateAction($attribute, $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED);
        }

        $validator = 'validate' .studly_case($action);

        if (method_exists($this, $validator))
        {
            $this->$validator();
        }
    }

    protected function validateArchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED);
        }

        $this->validateMerchantDetailExists($merchant);
    }

    protected function validateUnarchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ARCHIVED);
        }
    }

    protected function validateSuspend()
    {
        $merchant = $this->entity;

        if ($merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED);
        }
    }

    protected function validateUnsuspend()
    {
        $merchant = $this->entity;

        if ($merchant->isSuspended() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_SUSPENDED);
        }
    }

    protected function validateHoldFunds()
    {
        $merchant = $this->entity;

        if ($merchant->getHoldFunds() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ALREADY_ON_HOLD);
        }
    }

    protected function validateReleaseFunds()
    {
        $merchant = $this->entity;

        if ($merchant->getHoldFunds() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ALREADY_RELEASED);
        }
    }

    protected function validateEnableReceiptEmails()
    {
        $merchant = $this->entity;

        if ($merchant->isReceiptEmailsEnabled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECEIPT_EMAILS_ALREADY_ENABLED);
        }
    }

    protected function validateDisableReceiptEmails()
    {
        $merchant = $this->entity;

        if ($merchant->isReceiptEmailsEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECEIPT_EMAILS_ALREADY_DISABLED);
        }
    }

    protected function validateEnableInternational()
    {
        $merchant = $this->entity;

        if ($merchant->isInternational() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED);
        }
    }

    protected function validateDisableInternational()
    {
        $merchant = $this->entity;

        if ($merchant->isInternational() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED);
        }
    }

    /**
     * Throw an error if the merchant is already a partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIfAlreadyPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_ALREADY_PARTNER);
        }
    }

    /**
     * Throw an error if the merchant is not a partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIfNotAPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotLinkedAccount(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_PARTNER);

        }
    }
}
