<?php

namespace RZP\Models\Merchant\Website;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID           => 'sometimes|string|size:14',
        Entity::DELIVERABLE_TYPE      => 'sometimes|string|nullable|in:' . Constants::VALID_DELIVERABLE_TYPE,
        Entity::SHIPPING_PERIOD       => 'sometimes|string|nullable',
        Entity::REFUND_REQUEST_PERIOD => 'sometimes|string|nullable',
        Entity::REFUND_PROCESS_PERIOD => 'sometimes|string|nullable',
        Entity::WARRANTY_PERIOD       => 'sometimes|string|nullable',
        Entity::STATUS                => 'sometimes|string|nullable|in:' . Constants::VALID_STATUS,

        Entity::MERCHANT_WEBSITE_DETAILS => 'sometimes|array|nullable',

        Entity::ADDITIONAL_DATA                                  => 'sometimes|array|nullable',
        Entity::ADDITIONAL_DATA . '.' . Constants::SUPPORT_EMAIL => 'sometimes|nullable|email|max:255',
        Entity::ADDITIONAL_DATA . '.' . Constants::SUPPORT_PHONE => 'sometimes|max:15|nullable|contact_syntax',

        Entity::ADMIN_WEBSITE_DETAILS => 'sometimes|array|nullable',
        Entity::GRACE_PERIOD          => 'sometimes|boolean|nullable',
        Entity::SEND_COMMUNICATION    => 'sometimes|boolean|nullable'
    ];

    protected static $editRules   = [
        Entity::MERCHANT_ID           => 'sometimes|string|size:14',
        Entity::DELIVERABLE_TYPE      => 'sometimes|string|nullable|in:' . Constants::VALID_DELIVERABLE_TYPE,
        Entity::SHIPPING_PERIOD       => 'sometimes|string|nullable',
        Entity::REFUND_REQUEST_PERIOD => 'sometimes|string|nullable',
        Entity::REFUND_PROCESS_PERIOD => 'sometimes|string|nullable',
        Entity::WARRANTY_PERIOD       => 'sometimes|string|nullable',
        Entity::STATUS                => 'sometimes|string|nullable|in:' . Constants::VALID_STATUS,

        Entity::MERCHANT_WEBSITE_DETAILS => 'sometimes|array|nullable',

        Entity::ADDITIONAL_DATA                                  => 'sometimes|array|nullable',
        Entity::ADDITIONAL_DATA . '.' . Constants::SUPPORT_EMAIL => 'sometimes|nullable|email|max:255',
        Entity::ADDITIONAL_DATA . '.' . Constants::SUPPORT_PHONE => 'sometimes|max:15|nullable|contact_syntax',

        Entity::ADMIN_WEBSITE_DETAILS => 'sometimes|array|nullable',
        Entity::GRACE_PERIOD          => 'sometimes|boolean|nullable',
        Entity::SEND_COMMUNICATION    => 'sometimes|boolean|nullable'
    ];


    protected static $saveMerchantSectionRules   = [
        Entity::DELIVERABLE_TYPE                                                               => 'sometimes|string|nullable|in:' . Constants::VALID_DELIVERABLE_TYPE,
        Entity::SHIPPING_PERIOD                                                                => 'sometimes|string|nullable',
        Entity::REFUND_REQUEST_PERIOD                                                          => 'sometimes|string|nullable',
        Entity::REFUND_PROCESS_PERIOD                                                          => 'sometimes|string|nullable',
        Entity::WARRANTY_PERIOD                                                                => 'sometimes|string|nullable',
        Entity::STATUS                                                                         => 'sometimes|string|nullable|in:' . Constants::VALID_STATUS,
        Entity::ADDITIONAL_DATA                                                                => 'sometimes|array|nullable',
        Entity::ADDITIONAL_DATA . '.' . Constants::SUPPORT_EMAIL                               => 'sometimes|nullable|email|max:255',
        Entity::ADDITIONAL_DATA . '.' . Constants::SUPPORT_PHONE                               => 'sometimes|max:15|nullable|contact_syntax',
        Entity::MERCHANT_WEBSITE_DETAILS                                                       => 'sometimes|array|nullable',
        Entity::MERCHANT_WEBSITE_DETAILS . '.'                                                => 'sometimes|array:' . Constants::VALID_MERCHANT_SECTIONS . '|distinct|nullable',
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.' . Constants::SECTION_STATUS                   => 'required|integer|in:' . Constants::VALID_SECTION_STATUS,
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.' . Constants::WEBSITE                          => "sometimes|array|nullable",
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.' . Constants::WEBSITE . '.*.' . Constants::URL => 'required|custom:active_url|string|max:255',
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.' . Constants::APPSTORE_URL                     => "prohibited",
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.' . Constants::PLAYSTORE_URL                    => "prohibited",
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.' . Constants::PUBLISHED_URL                    => 'prohibited',
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.*.*.' . Constants::DOCUMENT_ID                  => 'prohibited',
        Entity::MERCHANT_WEBSITE_DETAILS . '.*.*.*.' . Constants::UPDATED_AT                   => 'prohibited',
        Entity::SEND_COMMUNICATION => 'sometimes|boolean|nullable'
    ];

    protected static $saveAdminSectionRules      = [
        Constants::SECTION_NAME => 'sometimes|string|in:' . Constants::VALID_ADMIN_SECTIONS,
        Constants::URL_TYPE     => 'required_if:section_name,' . Constants::VALID_ADMIN_SECTIONS . ',' . '|in:' . Constants::VALID_URL_TYPE,
        Constants::URL          => 'required_if:section_name,' . Constants::VALID_ADMIN_SECTIONS . ',' . '|custom:active_url|string|max:255',
        Constants::SECTION_URL  => 'sometimes|custom:active_url|string|max:255|nullable',
        Constants::COMMENTS     => 'required_if:section_name,comments|string|nullable',
        Entity::GRACE_PERIOD    => 'sometimes|boolean|nullable'

    ];

    protected static $merchantActionSectionRules = [
        Constants::ACTION           => 'required|string|in:' . Constants::VALID_MERCHANT_ACTION,
        Entity::MERCHANT_ID         => 'sometimes|string|size:14|nullable',
        Constants::SECTION_NAME     => 'required|string|in:' . Constants::VALID_MERCHANT_SECTIONS,
        Constants::URL_TYPE         => 'required_if:' . Constants::ACTION . ',upload,delete|in:appstore_url,playstore_url|nullable',
        Constants::FILE             => 'required_if:' . Constants::ACTION . ',upload|nullable',
        Constants::MERCHANT_CONSENT => 'sometimes|boolean|nullable'
    ];

    protected static $adminActionSectionRules    = [
        Constants::ACTION       => 'required|string|in:' . Constants::VALID_ADMIN_ACTION,
        Constants::SECTION_NAME => 'required|string|in:' . Constants::VALID_ADMIN_SECTIONS,
        Constants::URL_TYPE     => 'required|in:appstore_url,playstore_url|nullable',
        Constants::FILE         => 'required_if:' . Constants::ACTION . ',upload|nullable',

    ];

    protected static $merchantSectionPageRules   = [
        Entity::ID              => 'sometimes|string|size:14|nullable',
        Constants::SECTION_NAME => 'required|string|in:' . Constants::VALID_MERCHANT_SECTIONS
    ];
}
