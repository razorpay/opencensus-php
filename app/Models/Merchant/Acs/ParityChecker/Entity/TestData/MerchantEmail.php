<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;


use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class MerchantEmail implements TestDataInterface
{
    public function getTestData(): array
    {

       $reusableId = Helper::getUniqueIdCallBack()();
       return [
           [
               Constant::API_BUILD_ATTRIBUTES => [
                   'phone'=> "123412345",
                   "type" => "refund",
                   "email" => "test@test.com" // remove when email migration change is made
               ],
               Constant::UPDATE_ATTRIBUTES => [
                   "email" => "manthan.surkar@razorpay.com"
               ],
               Constant::CUSTOM_ATTRIBUTES => [
                   "merchant_id" => Helper::getUniqueIdCallBack(),
                   "id" => Helper::getUniqueIdCallBack(),
               ],
           ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    'phone'=> "123412345",
                    "type" => "refund",
                    "policy" => "abc",
                    "email" => "test@test.com" // remove when email migration change is made
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    'phone'=> "123412345",
                    "type" => "refund",
                    "policy" => "abc",
                    "email" => "abc@manthan.com"
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "id" => Helper::getUniqueIdCallBack(),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "email" => "manthan.surkar@razorpay.com",
                    "policy" => "manthan",
                    "url" => "https://surkars.in"
                ],

            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    'phone'=> "123412345",
                    "type" => "refund",
                    "policy" => "abc",
                    "email" => "abc@manthan.com",
                    "url" => "https://surkar.in"
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    'phone'=> "123412345",
                    "type" => "refund",
                    "policy" => "abc",
                    "email" => "abc@manthan.com",
                    "url" => "https://surkar.in",
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "verified" => 1,
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "id" => Helper::getUniqueIdCallBack(),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    'phone'=> "123412345",
                    "type" => "newmanthan",
                    "policy" => "abc",
                    "email" => "abc@manthan.com",
                    "url" => "https://surkar.in",
                    "merchant_id" => Helper::getUniqueIdCallBack(),
                    "verified" => 0
                ],
            ],
            [
               Constant::API_BUILD_ATTRIBUTES => [
                   'phone'=> "123412345",
                   "type" => "refund",
                   "policy" => "abc",
                   "email" => "abc@manthan.com",
                   "url" => "https://surkar.in",
               ],
               Constant::CUSTOM_ATTRIBUTES => [
                   "verified" => 1,
                   "merchant_id" => Helper::getUniqueIdCallBack(),
                   "id" => Helper::getUniqueIdCallBack(),
               ],
               Constant::UPDATE_ATTRIBUTES => [
                   'phone'=> null,
                   "policy" => null,
                   "url" => null,
//                   "email" => null, // uncomment when db migration are updated for e2e
               ],
            ],
            [
               Constant::API_BUILD_ATTRIBUTES => [
                   'phone'=> "123412345",
                   "type" => "refund",
                   "policy" => "abc",
                   "email" => "abc@manthan.com",
                   "url" => "https://surkar.in",
               ],
               Constant::CUSTOM_ATTRIBUTES => [
                   "verified" => 1,
                   "merchant_id" => Helper::getUniqueIdCallBack(),
                   "id" => Helper::getUniqueIdCallBack(),
               ],
               Constant::UPDATE_ATTRIBUTES => [
                   'phone'=> null,
                   "policy" => null,
                   "url" => null,
//                   "email" => null, // uncomment when db migration are updated for e2e
                   "type" => null,
               ],
               Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'type' cannot be null",
               Constant::ASV_EXPECTED_EXCEPTION => "Required Key: type, not found in attributes.",
            ],
           [
               Constant::API_BUILD_ATTRIBUTES => [
                   'phone'=> "123412345",
                   "type" => "refund",
                   "policy" => "abc",
                   "email" => "abc@manthan.com",
                   "url" => "https://surkar.in",
               ],
               Constant::CUSTOM_ATTRIBUTES => [
                   "verified" => 1,
                   "id" => Helper::getUniqueIdCallBack(),
               ],
               Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[HY000]: General error: 1364 Field 'merchant_id' doesn't have a default value",
               Constant::ASV_EXPECTED_EXCEPTION => "Required Key: merchant_id, not found in attributes.",
           ],
           [
               Constant::API_BUILD_ATTRIBUTES => [
                   'phone'=> "123412345",
                   "type" => "refund",
                   "policy" => "abc",
                   "email" => "abc@manthan.com",
                   "url" => "https://surkar.in",
               ],
               Constant::CUSTOM_ATTRIBUTES => [
                   "verified" => 1,
                   "id" => Helper::getUniqueIdCallBack(),
                   "merchant_id" => $reusableId,
               ],
               Constant::API_EXPECTED_EXCEPTION => "",
               Constant::ASV_EXPECTED_EXCEPTION => "base: unique_constraint_violation: Error 1062: Duplicate entry"
           ],
           [
               Constant::API_BUILD_ATTRIBUTES => [
                   'phone'=> "123412345",
                   "type" => "refund",
                   "policy" => "abc",
                   "email" => "abc@manthan.com",
                   "url" => "https://surkar.in",
               ],
               Constant::CUSTOM_ATTRIBUTES => [
                   "verified" => 1,
                   "id" => Helper::getUniqueIdCallBack(),
                   "merchant_id" => $reusableId,
               ],
               Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry",
               Constant::ASV_EXPECTED_EXCEPTION => "base: unique_constraint_violation: Error 1062: Duplicate entry"
           ],
        ];
    }

}
