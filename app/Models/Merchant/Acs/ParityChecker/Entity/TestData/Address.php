<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;


use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Address implements TestDataInterface
{
    public function getTestData(): array
    {
        $reusableId = Helper::getUniqueIdCallBack()();
        return [
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "line1" => "I am random Value",
                    "type" => "primary",
                    "state" => "I am random Value",
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "entity_type" => "stakeholder",
                    "country" => "IN",
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "line1" => "I am random Value",
                    "type" => "primary",
                    "state" => "I am random Value",
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "entity_id" => "someentitys",
                    "line2" => "notI am random Value",
                    "city" => "notI am random Value",
                    "zipcode" => "02345",
                    "country" => "ID",
                    "contact" => "notI am random Value",
                    "tag" => "notI am random Value",
                    "landmark" => "notI am random Value",
                    "name" => "notI am random Value",
                    "source_id" => "ssomeid",
                    "source_type" => "vI am random Value",
                    "line1" => "dI am random Value",
                    "type" => "secondary",
                    "state" => "sI am random Value",
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "entity_id" => "someentity",
                    "entity_type" => "stakeholder",
                    "line2" => "I am random Value",
                    "city" => "I am random Value",
                    "zipcode" => "12345",
                    "country" => "IN",
                    "contact" => "I am random Value",
                    "tag" => "I am random Value",
                    "landmark" => "I am random Value",
                    "name" => "I am random Value",
                    "source_id" => "someid",
                    "source_type" => "I am random Value",
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "line1" => "I am random Value",
                    "type" => "primary",
                    "state" => "I am random Value",
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "entity_type" => "stakeholder",
                    "source_id" => "i am long value not 14 chars",
                ],
                Constant::API_EXPECTED_EXCEPTION => "SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'source_id'",
                Constant::ASV_EXPECTED_EXCEPTION => "db_error: Error 1406: Data too long for column 'source_id'",
            ],
        ];
    }

}
