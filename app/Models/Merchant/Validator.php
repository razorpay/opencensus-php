<?php

namespace RZP\Models\Merchant;

use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Admin\Admin;
use RZP\Models\Payment\Event;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\Merchant
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    // Maximum image size - 1M.
    const MAXIMAGESIZE = 1024 * 1024;

    const BATCH_ID                          = 'Batch Id';
    const BULK_SUBMERCHANT_ASSIGN           = 'Bulk Submerchant Assign';
    // Rate limit on items sending for bulk submerchant assign.
    const MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT = 15;

    const EXTENSIONMIMEMAP = [
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'png'   => 'image/png',
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
        Entity::NAME                                  => 'sometimes|string|max:200',
        Entity::HOLD_FUNDS                            => 'sometimes|in:0,1',
        Entity::WEBSITE                               => 'sometimes|url|max:255|nullable',
        Entity::CATEGORY                              => 'sometimes|string|digits:4',
        Entity::CATEGORY2                             => 'sometimes|string|max:30|custom',
        Entity::INTERNATIONAL                         => 'sometimes|boolean',
        Entity::BILLING_LABEL                         => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL              => 'sometimes|array',
        Entity::RECEIPT_EMAIL_ENABLED                 => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_TRIGGER_EVENT           => 'sometimes|nullable|string|in:authorized,captured',
        Entity::LINKED_ACCOUNT_KYC                    => 'sometimes|boolean',
        Entity::CHANNEL                               => 'sometimes|string|max:32|custom',
        Entity::RISK_RATING                           => 'sometimes|min:0|max:5',
        Entity::RISK_THRESHOLD                        => 'sometimes|integer|min:0|max:100',
        Entity::FEE_BEARER                            => 'sometimes|in:customer,platform',
        Entity::FEE_MODEL                             => 'sometimes|in:prepaid,postpaid',
        Entity::REFUND_SOURCE                         => 'sometimes|string|max:32|in:balance,credits',
        Entity::MAX_PAYMENT_AMOUNT                    => 'sometimes|integer',
        // max: 5 days (don't change max value without consult), min:60 minutes
        Entity::AUTO_REFUND_DELAY                     => 'sometimes|string|custom',
        Entity::DEFAULT_REFUND_SPEED                  => 'sometimes|filled|string|in:normal,optimum',
        Entity::AUTO_CAPTURE_LATE_AUTH                => 'sometimes|boolean',
        Entity::CONVERT_CURRENCY                      => 'sometimes|nullable|boolean',
        Entity::ORG_ID                                => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                                => 'sometimes|array',
        Entity::ADMINS                                => 'sometimes|array',
        Entity::WHITELISTED_IPS_LIVE                  => 'sometimes|array|max:15',
        Entity::WHITELISTED_IPS_LIVE . '.*'           => 'required_with:' . Entity::WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::WHITELISTED_IPS_TEST                  => 'sometimes|array|max:15',
        Entity::WHITELISTED_IPS_TEST . '.*'           => 'required_with:' . Entity::WHITELISTED_IPS_TEST . '|ipv4',
        Entity::DASHBOARD_WHITELISTED_IPS_LIVE        => 'sometimes|array|max:20',
        Entity::DASHBOARD_WHITELISTED_IPS_LIVE . '.*' => 'distinct|required_with:' .
                                                         Entity::DASHBOARD_WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::DASHBOARD_WHITELISTED_IPS_TEST        => 'sometimes|array|max:20',
        Entity::DASHBOARD_WHITELISTED_IPS_TEST . '.*' => 'distinct|required_with:' .
                                                         Entity::DASHBOARD_WHITELISTED_IPS_TEST . '|ipv4',
        Entity::FEE_CREDITS_THRESHOLD                 => 'sometimes|integer|nullable',
        Entity::PARTNERSHIP_URL                       => 'sometimes|max:2000'
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

    protected static $editPreSignupRules = [
        Entity::NAME                        => 'required|min:4|string|max:200',
        Entity::WEBSITE                     => 'sometimes|active_url|max:255|nullable',
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
        Entity::DISPLAY_NAME             => 'sometimes|nullable|string|min:3|max:255',
        Entity::FEE_CREDITS_THRESHOLD    => 'sometimes|integer|nullable',
    ];

    protected static $actionRules = [
        Entity::ACTION                      => 'required|custom'
    ];

    protected static $change2faSettingRules = [
        User\Entity::PASSWORD         => 'required|between:6,50',
        Entity::SECOND_FACTOR_AUTH    => 'required|boolean',
    ];

    protected static $bulkTagRules = [
        'action'         => 'required|string|filled|max:10|in:insert,delete',
        'name'           => 'required|string|filled',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|size:14'
    ];

    protected static $bulkAssignScheduleRules = [
        'schedule'       => 'required|array',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|size:14',
    ];

    protected static $bulkAssignPricingRules = [
        'pricing_plan_id' => 'required|string|size:14',
        'merchant_ids'    => 'required|array',
        'merchant_ids.*'  => 'required|string|filled|size:14',
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
        'es_enabled'                => 'sometimes|boolean',
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
        'refund'       => 'sometimes|filled|file|mimes:txt|max:1024',
        'delta_refund' => 'sometimes|filled|file|mimes:txt|max:1024',
        'settlement'   => 'sometimes|filled|file|mimes:txt|max:1024',
    ];

    protected static $createSubMerchantUserRules = [
        'merchant_id' => 'required|alpha_num|size:14',
        Entity::EMAIL => 'required|email',
    ];

    protected static $editMethodsRules = [
        //only this method editing is allowed for now
        Methods\Entity::EMI => 'required|bool',
    ];

    protected static $resetSettlementScheduleRules = [
        'merchant_ids'   => 'required|sequential_array',
        'merchant_ids.*' => 'required|alpha_num|size:14',
    ];

    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
    ];

    protected static $featureValidators = [
        'visible_and_editable_features',
        'mode_for_product_features',
    ];

    protected static $editEmailValidators = [
        'is_test_account',
    ];

    protected static $keyAccessValidators = [
        'key_access',
    ];

    protected static $listSubmerchantsRules = [
        Entity::NAME                     => 'sometimes|string',
        Entity::ID                       => 'sometimes|alpha_num|size:14',
        Entity::EMAIL                    => 'sometimes|email',
        Constants::APPLICATION_ID        => 'sometimes|string|size:14',
        Detail\Entity::ACTIVATION_STATUS => 'sometimes|string|max:30',
        Constants::FROM                  => 'integer',
        Constants::TO                    => 'integer',
        Constants::COUNT                 => 'integer|min:1|max:50',
        Constants::SKIP                  => 'integer',
    ];

    protected static $partnerSubmerchantMapRules = [
        'partner_type'              => 'required|string',
        'submerchant_id'            => 'required|string',
        'partner_merchant_id'       => 'required|string',
    ];

    protected static $merchantPartnerStatusRules = [
        'email' => 'required|email',
    ];

    protected static $bulkSyncBalanceRules = [
        Constants::INTERVAL => 'sometimes|integer|min:15|max:120'
    ];

    protected static $submitSupportCallRequestRules = [
        'contact' => 'required|contact_syntax',
    ];

    protected static $toggleInternationalRules = [
        Entity::INTERNATIONAL => 'required|boolean'
    ];

    protected static $restrictSettingsMerchantRules = [
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::ACTION      => 'required|in:add,remove',
    ];

    protected static $bulkSubmerchantAssignRules = [
        'idempotency_key'   => 'required',
        'submerchant_id'    => 'required|alpha_num|size:14',
        'terminal_id'       => 'required|alpha_num|size:14',
    ];

    protected static $suspendedMerchantRemoveRules = [
        'skip'  => 'sometimes|integer',
        'limit' => 'sometimes|integer',
    ];

    protected static $updatePartnerTypeRules = [
        Entity::PARTNER_TYPE    => 'required|string|in:aggregator,reseller',
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
     * Throws an exception if a merchant tries to enable an uneditable
     * feature for live mode, or via the should_sync flag
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateModeForProductFeatures(array $input)
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
        Settlement\Channel::validate($channel);
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
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerWithSettingsAccess(Entity $merchant)
    {
        if ($merchant->isPartnerWithSettingsAccess() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNonPurePlatformPartner(Entity $merchant)
    {
        // Block non partners and pure platforms
        if ($merchant->isNonPurePlatformPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * @param $email
     * @param $orgId
     *
     * @throws Exception\BadRequestException
     */
    public function validateMerchantEmailUnique($email, $orgId)
    {
        $merchants = app('repo')->merchant->fetchByEmailAndOrgId(mb_strtolower($email), $orgId);

        if ($merchants->count() > 0)
        {
            // throw exception if merchant by that email already exists
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
                Entity::EMAIL,
                $merchants->pluck(Entity::ID)->toArray()
            );
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsPurePlatformPartner(Entity $merchant)
    {
        // Block non partners and non pure-platforms
        if ($merchant->isPurePlatformPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
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

        $detailValidator = $merchant->merchantDetail->getValidator();

        $detailValidator->validateActivationFormSubmitted();

        $this->validateIsNotActivated($merchant);

        $detailValidator->validateIsNotArchived();

        // Don't validate these rest of the attributes for Marketplace accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $this->validateActivationMandatoryAttributes();
    }

    /**
     * @param array $attributes
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateMandatoryAttributes(array $attributes)
    {
        $merchant = $this->entity;

        $website = $merchant->merchantDetail->getWebsite();

        // If the merchant has submitted activation form with website/app data
        // i.e merchant will have access to keys.
        // or if website is not null [this check to be removed later]
        if (($merchant->getHasKeyAccess() === true) or
            (empty($website) === false))
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

    public function validateActivationMandatoryAttributes()
    {
        $attributes = Constants::ACTIVATION_MANDATORY_FIELDS;

        $this->validateMandatoryAttributes($attributes);
    }

    public function validateInstantActivationMandatoryAttributes()
    {
        $attributes = Constants::INSTANT_ACTIVATION_MANDATORY_FIELDS;

        $this->validateMandatoryAttributes($attributes);
    }

    public function validateBeforeInstantlyActivate()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $this->validateIsNotActivated($merchant);

        $detailValidator->validateIsNotArchived();

        // LA's should directly be activated. They should not go through the instant activations flow
        if ($merchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_INSTANTLY_ACTIVATED,
                Entity::PARENT_ID,
                [
                    Entity::PARENT_ID => $merchant->getParentId(),
                ]);
        }

        $this->validateInstantActivationMandatoryAttributes();
    }

    /**
     * Ensures that the merchant feature requested is a visible feature and an editable feature.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateVisibleAndEditableFeatures(array $input)
    {
        $featureNames = array_keys($input['features']);

        $visibleFeatures = array_keys(Feature\Constants::$visibleFeaturesMap);
        $editableFeature = Feature\Constants::$merchantEditableFeatures;

        foreach ($featureNames as $feature)
        {
            // Feature must be a "visible feature" and editable by the merchant
            if ((in_array($feature, $visibleFeatures, true) === false) or
                (in_array($feature, $editableFeature, true) === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                    'feature',
                    [$feature]);
            }
            // Only Merchant who have feature ES_ON_DEMAND enabled can change ES features
            else if (($input['es_enabled'] === false) and
                     (($feature === Feature\Constants::ES_AUTOMATIC) or
                     ($feature === Feature\Constants::ES_ON_DEMAND)))
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

    public function validateAction($attribute, $action)
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

    protected function validateSetReceiptEmailEventAuthorized()
    {
        $merchant = $this->entity;

        if ($merchant->getReceiptEmailTriggerEvent() === Event::AUTHORIZED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_TRIGGER_EVENT_ALREADY_AUTHORISED);
        }
    }

    protected function validateSetReceiptEmailEventCaptured()
    {
        $merchant = $this->entity;

        if ($merchant->getReceiptEmailTriggerEvent() === Event::CAPTURED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_TRIGGER_EVENT_ALREADY_CAPTURED);
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

        $this->validateHasBankAccount();
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

        $plan = app('repo')->pricing->getPricingPlanByIdWithoutOrgId($merchant->getPricingPlanId());

        if ($plan->hasInternationalPricing() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Pricing not present for international.');
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
    public function validateIsPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * Validates merchant is partner.
     * Throws an error if the merchant is not a partner or if merchant is a reseller partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotResellerPartner(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        if ($merchant->isResellerPartner() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_NOT_ALLOWED_FOR_RESELLER,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
        }
    }

    /**
     * Validates that the merchant is a partner and an aggregator
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsAggregatorPartner(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        if ($merchant->isAggregatorPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
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

    public function validatePartnerIsNotSubmerchant(Entity $partner, Entity $submerchant)
    {
        if ($submerchant->getId() === $partner->getId())
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_CANNOT_BE_SUBMERCHANT_TO_ITSELF);
        }
    }

    public function validatePartnerType(string $partnerType)
    {
        if (in_array($partnerType, Constants::$partnerTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $partnerType,
                ]);
        }
    }

    public function validateLinkedAccount(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT);
        }
    }

    /**
     * Validate if the input application is
     *
     * @param string $inputAppId
     * @param array  $partnerAppIds
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerApplicationId(string $inputAppId, array $partnerAppIds)
    {
        if (in_array($inputAppId, $partnerAppIds, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_ID,
                Constants::APPLICATION_ID,
                [
                    'partner_app_ids'         => $partnerAppIds,
                    Constants::APPLICATION_ID => $inputAppId,
                ]);
        }
    }

    public function validateLinkedAccountDashboardAccess(bool $dashboardAccess, Entity $merchant)
    {
        $merchantUsersCount = $merchant->users()->count();

        if ($dashboardAccess === true and $merchantUsersCount > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_DASHBOARD_ACCESS_ALREADY_GIVEN);
        }

        if ($dashboardAccess === false and $merchantUsersCount === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS);
        }
    }

    public function validateLinkedAccountReversals(bool $allowReversals, Entity $merchant)
    {
        $merchantUsersCount = $merchant->users()->count();

        $canReverse = $merchant->isFeatureEnabled(Feature\Constants::ALLOW_REVERSALS_FROM_LA);

        if ($merchantUsersCount === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS);
        }

        if (($allowReversals === true) and ($canReverse === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_GIVEN);
        }

        if (($allowReversals === false) and ($canReverse === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_REMOVED);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED,
                Entity::ACTIVATED);
        }
    }

    public function validateBeforeKycVerified()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $detailValidator->validateActivationFormSubmitted();

        $this->validateIsActivated($merchant);

        $detailValidator->validateIsNotArchived();
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
                Entity::ACTIVATED);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateHasBankAccount()
    {
        $merchant = $this->entity;

        $bankAccount = $merchant->bankAccount;

        if ($bankAccount === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }
    }

    public function validateNowIsWorkingHour()
    {
        if (is_rzp_business_hour() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Now is not a working hour. Please try this request on Mon-Fri between 9 AM - 6 PM.');
        }
    }

    public function validateBusinessBankingActivated()
    {
        $merchant = $this->entity;

        if ($merchant->isBusinessBankingEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                null,
                [
                    'merchant_id'      => $merchant->getId(),
                    'merchant_name'    => $merchant->getName(),
                    'business_banking' => $merchant->isBusinessBankingEnabled()
                ]);
        }
    }

    public function validateAndTranslateToAccountNumberForBankingIfApplicable(array & $input)
    {
        $product       = array_get($input, Entity::PRODUCT);
        $accountNumber = array_get($input, Balance\Entity::ACCOUNT_NUMBER);

        if ((empty($product) === true) or
            (empty($accountNumber) === false))
        {
            $this->validateAndTranslateAccountNumberForBanking($input);
        }
    }

    /**
     * There are service methods (list & fetch) for few models which expect
     * mandatory ACCOUNT_NUMBER in query parameter. Such models include
     * transactions, bank_transfers & payouts. This method is called from those
     * service methods to translate ACCOUNT_NUMBER to BALANCE_ID because beyond
     * service layer repository's fetch etc only understands BALANCE_ID.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateAndTranslateAccountNumberForBanking(array & $input)
    {
        $this->validateBusinessBankingActivated();

        // Validates input has valid ACCOUNT_NUMBER.
        (new Base\JitValidator)
            ->rules([Balance\Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,22'])
            ->strict(false)
            ->input($input)
            ->validate();

        // Replaces ACCOUNT_NUMBER with corresponding BALANCE_ID.
        $accountNumber = array_pull($input, Balance\Entity::ACCOUNT_NUMBER);

        $balanceId = app('repo')->balance->getBalanceIdByAccountNumberOrFail($accountNumber);

        $input[Balance\Entity::BALANCE_ID] = $balanceId;
    }

    /**
     * @param  Admin\Entity    $admin
     * @param  Entity          $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateAdminMerchantAccess(Admin\Entity $admin, Entity $merchant)
    {
        if (($admin->canSeeAllMerchants() === true) and ($admin->getOrgId() === $merchant->getOrgId()))
        {
            return;
        }

        if (in_array($merchant->getId(), $admin->merchants()->get()->getIds(), true) === true)
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

    public function validateBatchId($batchId)
    {
        if (empty($batchId) === true)
        {
            throw new BadRequestValidationFailureException('Batch Id not present');
        }
    }

    /**
     * @param array $input
     * Rate limit on number of submerchant terminal assign in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkSubmerchantAssignCount(array $input)
    {
        if (count($input) > self::MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of ' . self::BULK_SUBMERCHANT_ASSIGN . ' is ' . self::MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT,
                null,
                null
            );
        }
    }
}
