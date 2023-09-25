<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;


use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class MerchantWebsite implements TestDataInterface
{
    public function getTestData(): array
    {
        $reusableId = Helper::getUniqueIdCallBack()();
        return [
            [
                Constant::API_BUILD_ATTRIBUTES => [

                ],
                Constant::UPDATE_ATTRIBUTES => [

                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "deliverable_type" => "goods",
                    "shipping_period" => "1-2 days",
                    "refund_request_period" => "1-3 days",
                    "refund_process_period" => "1-5 days",
                    "warranty_period" => "1-6 days",
                    "status" => "submitted",
                    "grace_period" => true,
                    "send_communication" => false,
                    "merchant_website_details" => array(json_decode('{"website":{"https://shopproduct.in/":{"terms":{"url":"https://shopproduct.in/policies/terms-of-service"},"refund":{"url":"https://shopproduct.in/pages/refund-returns"},"shipping":{"url":"https://shopproduct.in/pages/shipping-delivery"},"cancellation":{"url":"https://shopproduct.in/pages/refund-returns"}}}}')),
                    "admin_website_details" => array(json_decode('{"manthan":{"https://shopproduct.in/":{"terms":{"url":"https://shopproduct.in/policies/terms-of-service"},"refund":{"url":"https://shopproduct.in/pages/refund-returns"},"shipping":{"url":"https://shopproduct.in/pages/shipping-delivery"},"cancellation":{"url":"https://shopproduct.in/pages/refund-returns"}}}}')),
                    "additional_data" => array(json_decode('{"surkar":{"https://shopproduct.in/":{"terms":{"url":"https://shopproduct.in/policies/terms-of-service"},"refund":{"url":"https://shopproduct.in/pages/refund-returns"},"shipping":{"url":"https://shopproduct.in/pages/shipping-delivery"},"cancellation":{"url":"https://shopproduct.in/pages/refund-returns"}}}}')),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "deliverable_type" => "new goods",
                    "shipping_period" => "new1-2 days",
                    "refund_request_period" => "new1-3 days",
                    "refund_process_period" => "new1-5 days",
                    "warranty_period" => "new1-6 days",
                    "status" => "newsubmitted",
                    "grace_period" => false,
                    "send_communication" => true,
                    "merchant_website_details" => json_decode('{"newwebsite":{"https://shopproduct.in/":{"terms":{"url":"https://shopproduct.in/policies/terms-of-service"},"refund":{"url":"https://shopproduct.in/pages/refund-returns"},"shipping":{"url":"https://shopproduct.in/pages/shipping-delivery"},"cancellation":{"url":"https://shopproduct.in/pages/refund-returns"}}}}'),
                    "admin_website_details" => json_decode('{"newmanthan":{"https://shopproduct.in/":{"terms":{"url":"https://shopproduct.in/policies/terms-of-service"},"refund":{"url":"https://shopproduct.in/pages/refund-returns"},"shipping":{"url":"https://shopproduct.in/pages/shipping-delivery"},"cancellation":{"url":"https://shopproduct.in/pages/refund-returns"}}}}'),
                    "additional_data" => json_decode('{"newsurkar":{"https://shopproduct.in/":{"terms":{"url":"https://shopproduct.in/policies/terms-of-service"},"refund":{"url":"https://shopproduct.in/pages/refund-returns"},"shipping":{"url":"https://shopproduct.in/pages/shipping-delivery"},"cancellation":{"url":"https://shopproduct.in/pages/refund-returns"}}}}'),

                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "merchant_id" => Helper::getUniqueIdCallBack()
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [

                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "warranty_period" => "IAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONGIAMVERYLONG"
                ],
                Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'warranty_period'",
                Constant::ASV_EXPECTED_EXCEPTION => "base: db_error: Error 1406: Data too long for column 'warranty_period'",
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [

                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "grace_period" => false,
                    "send_communication" => true,
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "grace_period" => 1,
                    "send_communication" => 0,
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [

                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "grace_period" => 1,
                    "send_communication" => 0,
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "grace_period" => false,
                    "send_communication" => true,
                ],
            ],
        ];
    }

}
