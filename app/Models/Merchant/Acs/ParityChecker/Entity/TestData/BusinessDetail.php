<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;


use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class BusinessDetail implements TestDataInterface
{
    public function getTestData(): array
    {
        $reusableId = Helper::getUniqueIdCallBack()();
        return [
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                    "website_details" => json_decode('[{"hello":"manthan"}, {"surkar":"hello"}]'),
                    "app_urls" => json_decode('[{"app_urls":"manthan"}, {"app_urls":"hello"}]'),
                    "plugin_details" => json_decode('[{"plugin_details":"manthan"}, {"plugin_details":"hello"}]'),
                    "lead_score_components" => json_decode('[{"lead_score_components":"manthan"}, {"lead_score_components":"hello"}]'),
                    "business_parent_category" => "I am some category",
                    "blacklisted_products_category" => "I am black listed category",
                    "onboarding_source" => "xpress_onboarding",
                    "pg_use_case" => "I am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use case",
                    "miq_sharing_date" => 1234,
                    "testing_credentials_date" => 1235,
                    "metadata" => json_decode('[{"metadata":"manthan"}, {"metadata":"hello"}]'),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                    "website_details" => json_decode('[{"newhello":"manthan"}, {"surkar":"hello"}]'),
                    "app_urls" => json_decode('[{"newapp_urls":"manthan"}, {"app_urls":"hello"}]'),
                    "plugin_details" => json_decode('[{"newplugin_details":"manthan"}, {"plugin_details":"hello"}]'),
                    "lead_score_components" => json_decode('[{"newlead_score_components":"manthan"}, {"lead_score_components":"hello"}]'),
                    "business_parent_category" => "newI am some category",
                    "blacklisted_products_category" => "newI am black listed category",
                    "onboarding_source" => "newxpress_onboarding",
                    "pg_use_case" => "newI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use case",
                    "miq_sharing_date" => 1230,
                    "testing_credentials_date" => 1230,
                    "metadata" => json_decode('[{"newmetadata":"manthan"}, {"metadata":"hello"}]'),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                    "website_details" => json_decode('[{"newhello":"manthan"}, {"surkar":"hello"}]'),
                    "app_urls" => json_decode('[{"newapp_urls":"manthan"}, {"app_urls":"hello"}]'),
                    "plugin_details" => json_decode('[{"newplugin_details":"manthan"}, {"plugin_details":"hello"}]'),
                    "lead_score_components" => json_decode('[{"newlead_score_components":"manthan"}, {"lead_score_components":"hello"}]'),
                    "business_parent_category" => "newI am some category",
                    "blacklisted_products_category" => "newI am black listed category",
                    "onboarding_source" => "newxpress_onboarding",
                    "pg_use_case" => "newI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use caseI am pg use case",
                    "miq_sharing_date" => 1230,
                    "testing_credentials_date" => 1230,
                    "metadata" => json_decode('[{"newmetadata":"manthan"}, {"metadata":"hello"}]'),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "blacklisted_products_category" => "I am black listed category. I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.I am black listed category.",
                    "id" => Helper::getUniqueIdCallBack(),
                ],
                Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'blacklisted_products_category'",
                Constant::ASV_EXPECTED_EXCEPTION => "base: db_error: Error 1406: Data too long for column 'blacklisted_products_category' at"
            ],
        ];
    }

}
