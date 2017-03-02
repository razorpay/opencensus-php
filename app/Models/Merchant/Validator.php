<?php

namespace RZP\Models\Merchant;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;

class Validator extends Base\Validator
{
    // Maximum image size - 1M.
    const maxImageSize = 1024 * 1024;
    const extensionMimeMap = array(
        "jpeg"  => "image/jpeg",
        "jpg"   => "image/jpeg",
        "png"   => "image/png",
    );

    protected static $createRules = array(
        Entity::ID                          => 'required|alpha_num|size:14|unique:merchants',
        Entity::NAME                        => 'sometimes|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
    );

    protected static $editRules = array(
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
        Entity::FEE_BEARER                  => 'sometimes|in:customer,platform',
        Entity::FEE_MODEL                   => 'sometimes|in:prepaid,postpaid',
        Entity::MAX_PAYMENT_AMOUNT          => 'sometimes|integer',
        'groups'                            => 'sometimes|array',
        // max: 5 days (don't change max value without consult), min:60 minutes
        Entity::AUTO_REFUND_DELAY           => 'sometimes|string|custom',
        Entity::AUTO_CAPTURE_LATE_AUTH      => 'sometimes|boolean',
        Entity::CONVERT_CURRENCY            => 'sometimes|boolean'
    );

    protected static $uniqueEmailRules = array(
        Entity::EMAIL                       => 'required|email|unique:merchants'
    );

    protected static $editCreditsRules = array(
        Balance\Entity::AMOUNT_CREDITS      => 'required|integer|min:0|max:50000000'
    );

    protected static $editEmailRules = array(
        Entity::EMAIL                       => 'required|email|unique:merchants'
    );

    protected static $editConfigRules = array(
        Entity::BRAND_COLOR                 => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|array',
        Entity::LOGO_URL                    => 'sometimes|max:2000',
        Entity::AUTO_CAPTURE_LATE_AUTH      => 'sometimes|boolean'
    );

    protected static $actionRules = array(
        Entity::ACTION                      => 'required|custom'
    );

    protected static $featureRules = [
        'features'          => 'required|array',
        'optout_reason'     => 'sometimes|string|max:200'
    ];

    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
    ];

    protected static $featureValidators = [
        'visible_features',
    ];

    public function validateLogo($imageDetails)
    {
        $fileSize = $imageDetails['size'];
        $width = $imageDetails['width'];
        $height = $imageDetails['height'];

        // File size should not be more than 1M.
        if ($fileSize > self::maxImageSize)
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
        $acceptedMimeArray = self::extensionMimeMap;

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

    protected function validateCsvEmail($input)
    {
        if (isset($input[Entity::TRANSACTION_REPORT_EMAIL]) === false)
            return;

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
        $attributes = array(
            Entity::WEBSITE,
            Entity::CATEGORY,
            Entity::BILLING_LABEL,
            Entity::TRANSACTION_REPORT_EMAIL);

        foreach ($attributes as $attribute)
        {
            $value = $merchant->getAttribute($attribute);

            if (empty($value))
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
                $min = 30;
                $max = 7200;
                break;

            case 'hours':
                $min = 1;
                $max = 120;
                break;

            case 'days':
                $min = 1;
                $max = 5;
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

    protected function validateAction($attribute, $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED);
        }

        $validator = 'validate' .ucfirst($action);

        $this->$validator();
    }

    protected function validateArchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED);
        }

        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS);
        }
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
}
