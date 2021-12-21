<?php

namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID                                         => 'required|string|size:14',
        Entity::WEBSITE_DETAILS                                     => 'sometimes|array',
        Entity::WEBSITE_DETAILS . '.' . Constants::TERMS            => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ABOUT            => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CONTACT          => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRIVACY          => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::REFUND           => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRICING          => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::LOGIN            => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CANCELLATION     => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::COMMENTS         => 'sometimes|string|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PHYSICAL_STORE   => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA     => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_OR_APP   => 'sometimes|boolean',
        Entity::APP_URLS                                            => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL               => 'sometimes|active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                => 'sometimes|active_url|max:255|nullable'
    ];

    protected static $editRules   = [
        Entity::WEBSITE_DETAILS                                     => 'sometimes|array',
        Entity::WEBSITE_DETAILS . '.' . Constants::TERMS            => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ABOUT            => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CONTACT          => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRIVACY          => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::REFUND           => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRICING          => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::LOGIN            => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CANCELLATION     => 'sometimes|active_url|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::COMMENTS         => 'sometimes|string|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PHYSICAL_STORE   => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA     => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_OR_APP   => 'sometimes|boolean',
        Entity::APP_URLS                                            => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL               => 'sometimes|active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                => 'sometimes|active_url|max:255|nullable'
    ];
}
