<?php

namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID                                                   => 'required|string|size:14',
        Entity::WEBSITE_DETAILS                                               => 'sometimes|array',
        Entity::WEBSITE_DETAILS . '.' . Constants::TERMS                      => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ABOUT                      => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CONTACT                    => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRIVACY                    => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::REFUND                     => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRICING                    => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::LOGIN                      => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CANCELLATION               => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::COMMENTS                   => 'sometimes|string|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PHYSICAL_STORE             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA               => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_OR_APP             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS                     => 'sometimes|string',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_NOT_READY          => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_COMPLIANCE_CONSENT => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_PRESENT            => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ANDROID_APP_PRESENT        => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::IOS_APP_PRESENT            => 'sometimes|boolean|nullable',
        Entity::APP_URLS                                                      => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL                         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                          => 'sometimes|custom:active_url|max:255|nullable',
        Entity::BLACKLISTED_PRODUCTS_CATEGORY                                 => 'sometimes|string|max:255|nullable',
        Entity::BUSINESS_PARENT_CATEGORY                                      => 'sometimes|string|nullable',
    ];

    protected static $editRules   = [
        Entity::WEBSITE_DETAILS                                               => 'sometimes|array',
        Entity::WEBSITE_DETAILS . '.' . Constants::TERMS                      => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ABOUT                      => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CONTACT                    => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRIVACY                    => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::REFUND                     => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRICING                    => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::LOGIN                      => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CANCELLATION               => 'sometimes|custom:active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::COMMENTS                   => 'sometimes|string|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PHYSICAL_STORE             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA               => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_OR_APP             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS                     => 'sometimes|string',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_NOT_READY          => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_COMPLIANCE_CONSENT => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_PRESENT            => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ANDROID_APP_PRESENT        => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::IOS_APP_PRESENT            => 'sometimes|boolean|nullable',
        Entity::APP_URLS                                                      => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL                         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                          => 'sometimes|custom:active_url|max:255|nullable',
        Entity::BLACKLISTED_PRODUCTS_CATEGORY                                 => 'sometimes|string|max:255|nullable',
        Entity::BUSINESS_PARENT_CATEGORY                                      => 'sometimes|string|nullable',
    ];
}
