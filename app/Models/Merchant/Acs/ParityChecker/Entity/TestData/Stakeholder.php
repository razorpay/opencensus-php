<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;


use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Stakeholder implements TestDataInterface
{
    public function getTestData(): array
    {
        $reusableId = Helper::getUniqueIdCallBack()();
        return [
                [
                    Constant::API_BUILD_ATTRIBUTES => [
                        "merchant_id" => Helper::getUniqueIdCallBack()(),
                    ],
                    Constant::UPDATE_ATTRIBUTES => [
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
                            "email" => "notI am random Value",
                            "name" => "notI am random Value",
                            "phone_primary" => "notI am random Value",
                            "phone_secondary" => "notI am random Value",
                            "director" => 3,
                            "executive" => 5,
                            "percentage_ownership" => 11,
                            "poi_identification_number" => "notI am random Value",
                            "poi_status" => "notI am random Value",
                            "poa_status" => "notI am random Value",
                            "notes" => [
                                "hello" => "notmanthan sur"
                            ],
                            "pan_doc_status" => "notI am random Value",
                            "aadhaar_esign_status" => "notI am random Value",
                            "aadhaar_pin" => "notI am random Value",
                            "aadhaar_linked" => 0,
                            "aadhaar_verification_with_pan_status" => "notI am random Value",
                            "bvs_probe_id" => "notI am random Value",
                            "audit_id" => "notI am random Value",
                            "verification_metadata" => [
                                "hello" => "notmanthan"
                            ]
                        ],
                        Constant::CUSTOM_ATTRIBUTES => [
                            "id" => Helper::getUniqueIdCallBack(),
                            "email" => "I am random Value",
                            "name" => "I am random Value",
                            "phone_primary" => "I am random Value",
                            "phone_secondary" => "I am random Value",
                            "director" => 1,
                            "executive" => 1,
                            "percentage_ownership" => 10,
                            "poi_identification_number" => "I am random Value",
                            "poi_status" => "I am random Value",
                            "poa_status" => "I am random Value",
                            "notes" => [
                                "hello" => "manthan sur"
                            ],
                            "pan_doc_status" => "I am random Value",
                            "aadhaar_esign_status" => "I am random Value",
                            "aadhaar_pin" => "I am random Value",
                            "aadhaar_linked" => 1,
                            "aadhaar_verification_with_pan_status" => "I am random Value",
                            "bvs_probe_id" => "I am random Value",
                            "audit_id" => "I am random Value",
                            "verification_metadata" => [
                                "hello" => "manthan"
                            ]
                        ],
                ],
                [
                    Constant::API_BUILD_ATTRIBUTES => [
                        "merchant_id" => Helper::getUniqueIdCallBack()(),
                    ],
                    Constant::CUSTOM_ATTRIBUTES => [
                        "id" => Helper::getUniqueIdCallBack(),
                        "bvs_probe_id" => "longerthan30charslongerthan30charslongerthan30charslongerthan30charslongerthan30chars"
                    ],
                    Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'bvs_probe_id' at",
                    Constant::ASV_EXPECTED_EXCEPTION => "db_error: Error 1406: Data too long for column 'bvs_probe_id'"
                ],
        ];
    }

}
