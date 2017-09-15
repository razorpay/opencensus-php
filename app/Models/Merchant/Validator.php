<?php

namespace RZP\Models\Merchant;

use App;
use RZP\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
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
        Entity::ID                          => 'required|alpha_num|size:14|unique:merchants',
        Entity::NAME                        => 'sometimes|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
        Entity::ORG_ID                      => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                      => 'sometimes|array',
        Entity::ADMINS                      => 'sometimes|array',
        Entity::COUPON_CODE                 => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME                        => 'sometimes|alpha_space_num|max:200',
        Entity::HOLD_FUNDS                  => 'sometimes|in:0,1',
        Entity::WEBSITE                     => 'sometimes|url|max:255',
        Entity::CATEGORY                    => 'sometimes|numeric|digits:4',
        Entity::CATEGORY2                   => 'sometimes|string|max:30|custom',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::BILLING_LABEL               => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|array',
        Entity::RECEIPT_EMAIL_ENABLED       => 'sometimes|boolean',
        Entity::SETTLEMENT_SCHEDULE         => 'sometimes|integer|min:1|max:30',
        Entity::NAME                        => 'sometimes|alpha_space_num|max:200',
        Entity::RISK_RATING                 => 'sometimes|min:0|max:5',
        Entity::RISK_THRESHOLD              => 'sometimes|integer|min:0|max:20',
        Entity::FEE_BEARER                  => 'sometimes|in:customer,platform',
        Entity::FEE_MODEL                   => 'sometimes|in:prepaid,postpaid',
        Entity::MAX_PAYMENT_AMOUNT          => 'sometimes|integer',
        // max: 5 days (don't change max value without consult), min:60 minutes
        Entity::AUTO_REFUND_DELAY           => 'sometimes|string|custom',
        Entity::AUTO_CAPTURE_LATE_AUTH      => 'sometimes|boolean',
        Entity::CONVERT_CURRENCY            => 'sometimes|boolean',
        Entity::ORG_ID                      => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                      => 'sometimes|array',
        Entity::ADMINS                      => 'sometimes|array',
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

    protected static $editConfigRules = [
        Entity::BRAND_COLOR                 => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|array',
        Entity::LOGO_URL                    => 'sometimes|max:2000',
        Entity::AUTO_CAPTURE_LATE_AUTH      => 'sometimes|boolean',
        Entity::HANDLE                      => 'sometimes|nullable|size:4|custom|unique:merchants,handle,null',
        MerchantDetail::GSTIN               => 'sometimes|nullable|string|size:15',
        MerchantDetail::P_GSTIN             => 'sometimes|nullable|string',
    ];

    protected static $actionRules = [
        Entity::ACTION                      => 'required|custom'
    ];

    protected static $featureRules = [
        'features'          => 'required|array',
        'optout_reason'     => 'sometimes|string|max:200'
    ];

    protected static $addTagsRules = [
        'tags' => 'required|array'
    ];

    protected static $updateHoldFundsRules = [
        'hold_funds'   => 'required|boolean',
        'merchant_ids' => 'required|array'
    ];

    protected static $updateBankAccountRules = [
        'bank_account'   => 'required|array',
        'merchant_ids'   => 'required|array'
    ];

    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
    ];

    protected static $featureValidators = [
        'visible_features',
        'feature_update_for_mode'
    ];

    /**
     * Throw an error, if any of the features that can be enabled or disabled only by
     * an admin in the LIVE mode, is being edited by the merchant.
     *
     * @param $features
     *
     * @throws Exception\BadRequestException
     */
    protected function validateFeatureUpdateForMode(array $input)
    {

        if ($this->isTestMode() === true)
        {
            return;
        }

        $requestedFeatures = array_keys($input['features']);

        $uneditableFeatures = Feature\Constants::$featuresUneditableOnLive;

        // array_values is required as array_intersect returns an associative array with keys
        // as the indexes if the element at index 0 in the first argument array is not present
        // in the 2nd argument array.
        $featuresNotAllowed = array_values(array_intersect($requestedFeatures, $uneditableFeatures));

        if (empty($featuresNotAllowed) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE, $featuresNotAllowed);
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

    public function validateBeforeActivate(Merchant\Entity $merchant)
    {
        // Dont validate these attributes for Marketplace accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $attributes = [
            Entity::WEBSITE,
            Entity::CATEGORY,
            Entity::BILLING_LABEL,
            Entity::TRANSACTION_REPORT_EMAIL
        ];

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

    protected function validateVisibleFeatures(array $input)
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
}
