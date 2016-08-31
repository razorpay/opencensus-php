<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    // Maximum image size - 1M.
    const maxImageSize = 1024*1024;
    const extensionMimeMap = array(
        "jpeg"  => "image/jpeg",
        "jpg"   => "image/jpeg",
        "png"   => "image/png",
    );

    protected static $createRules = array(
        Entity::ID                          => 'required|alpha_num|size:14|unique:merchants',
        Entity::NAME                        => 'required|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
    );

    protected static $editRules = array(
        Entity::HOLD_FUNDS                  => 'sometimes|in:0,1',
        Entity::WEBSITE                     => 'sometimes|url|max:255',
        Entity::CATEGORY                    => 'sometimes|numeric|digits:4',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::BILLING_LABEL               => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|array',
        Entity::RECEIPT_EMAIL_ENABLED       => 'sometimes|boolean',
        Entity::SETTLEMENT_SCHEDULE         => 'sometimes|integer|min:1|max:30',
        Entity::FEATURES                    => 'sometimes|max:255',
        Entity::NAME                        => 'sometimes|alpha_space_num|max:200',
        Entity::RISK_RATING                 => 'sometimes|min:0|max:5',
        Entity::FEE_BEARER                  => 'sometimes|in:customer,platform',
        Entity::MAX_PAYMENT_AMOUNT          => 'sometimes|integer',
        Entity::TERMINAL_CATEGORY 	        => 'sometimes',
    );

    protected static $uniqueEmailRules = array(
        Entity::EMAIL                       => 'required|email|unique:merchants'
    );

    protected static $editCreditsRules = array(
        Balance\Entity::CREDITS             => 'required|integer|min:0|max:50000000'
    );

    protected static $editEmailRules = array(
        Entity::EMAIL                       => 'required|email|unique:merchants'
    );

    protected static $editConfigRules = array(
        Entity::BRAND_COLOR                 => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|array',
        Entity::LOGO_URL                    => 'sometimes|max:2000',
    );

    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
        'features',
        'terminal_category',
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

    public function validateTerminalCategory($input)
    {
        if (empty($input[Entity::TERMINAL_CATEGORY]) === true)
        {
            return;
        }

        $category = $input[Entity::TERMINAL_CATEGORY];

        if (Terminal\Category::isMerchantCategoryValid($category) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category : '.$category.' invalid for merchant',
                Entity::TERMINAL_CATEGORY
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

    protected function validateFeatures($input)
    {
        Features::validateFeatures($input);
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
}
