<?php

namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

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
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS_PRESENT             => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WHATSAPP_SMS_EMAIL         => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA_URLS          => 'sometimes|array|nullable',
        Entity::APP_URLS                                                      => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL                         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                          => 'sometimes|custom:active_url|max:255|nullable',
        Entity::BLACKLISTED_PRODUCTS_CATEGORY                                 => 'sometimes|string|max:255|nullable',
        Entity::BUSINESS_PARENT_CATEGORY                                      => 'sometimes|string|nullable',
        Entity::PLUGIN_DETAILS                                                => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS                                         => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::GSTIN_SCORE          => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::DOMAIN_SCORE         => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::REGISTERED_YEAR      => 'sometimes|numeric|digits:4|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::AGGREGATED_TURNOVER_SLAB   => 'sometimes|string|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::WEBSITE_VISITS       => 'sometimes|numeric|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::ECOMMERCE_PLUGIN     => 'sometimes|boolean|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::ESTIMATED_ANNUAL_REVENUE   => 'sometimes|string|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::TRAFFIC_RANK         => 'sometimes|string|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::CRUNCHBASE           => 'sometimes|boolean|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::TWITTER_FOLLOWERS    => 'sometimes|numeric|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::LINKEDIN             => 'sometimes|boolean|nullable',
        Entity::ONBOARDING_SOURCE                                             => 'filled|in:xpress_onboarding,xpress_onboarding_test',
        Entity::PG_USE_CASE                                                   => 'sometimes|string|max:500|min:50|nullable',
        Entity::MIQ_SHARING_DATE                                              => 'sometimes|integer',
        Entity::TESTING_CREDENTIALS_DATE                                      => 'sometimes|integer',
        Entity::METADATA                                                      => 'sometimes|array',
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
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS_PRESENT             => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WHATSAPP_SMS_EMAIL         => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA_URLS          => 'sometimes|array|nullable',
        Entity::APP_URLS                                                      => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL                         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                          => 'sometimes|custom:active_url|max:255|nullable',
        Entity::BLACKLISTED_PRODUCTS_CATEGORY                                 => 'sometimes|string|max:255|nullable',
        Entity::BUSINESS_PARENT_CATEGORY                                      => 'sometimes|string|nullable',
        Entity::PLUGIN_DETAILS                                                => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS                                         => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::GSTIN_SCORE          => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::DOMAIN_SCORE         => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::ONBOARDING_SOURCE                                             => 'filled|in:xpress_onboarding,xpress_onboarding_test',
        Entity::PG_USE_CASE                                                   => 'sometimes|string|nullable|max:500|min:50',
        Entity::MIQ_SHARING_DATE                                              => 'sometimes|integer',
        Entity::TESTING_CREDENTIALS_DATE                                      => 'sometimes|integer',
        Entity::METADATA                                                      => 'sometimes|array',
    ];

    /**
    * @throws BadRequestValidationFailureException
     * @throws BadRequestException
     * */
    public static function validateMIQSharingAndTestingDate(array &$input, $merchantDetails)
    {
        $minValue =  Carbon::now()->setTimezone(Timezone::IST)->subDays(7)->modify('today')->getTimestamp();
        $maxValue =  Carbon::now()->setTimezone(Timezone::IST)->modify('today')->getTimestamp();

        if(isset($input[Entity::MIQ_SHARING_DATE]) === true)
        {
            if($input[Entity::MIQ_SHARING_DATE] === 0 )
            {
                unset($input[Entity::MIQ_SHARING_DATE]);
            }
            else if ($input[Entity::MIQ_SHARING_DATE] < $minValue or $input[Entity::MIQ_SHARING_DATE] > $maxValue)
            {
               throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_MIQ_SHARING_DATE, null);
            }
        }

        if(isset($input[Entity::TESTING_CREDENTIALS_DATE]) === true)
        {
            if($input[Entity::TESTING_CREDENTIALS_DATE] === 0)
            {
                unset($input[Entity::TESTING_CREDENTIALS_DATE]);
            }
            else if ($input[Entity::TESTING_CREDENTIALS_DATE] < $minValue or $input[Entity::TESTING_CREDENTIALS_DATE] > $maxValue)
            {
                throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_TESTING_CREDENTIALS_DATE, null);
            }
        }

        if((isset($input['miq_sharing_date']) === true or
                isset($input['testing_credentials_date']) === true ) and
            $merchantDetails->merchant->org->isFeatureEnabled(\RZP\Models\Feature\Constants::ADDITIONAL_ONBOARDING) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED, null);
        }
    }
}
