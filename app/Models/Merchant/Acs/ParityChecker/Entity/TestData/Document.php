<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;


use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Document implements TestDataInterface
{
    public function getTestData(): array
    {
        $reusableId = Helper::getUniqueIdCallBack()();
        return [
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                    "file_store_id" => "JWeLWktZ6Id3NL",
                    "source" => "UFH",
                    "document_type" => "memorandum_of_association",
                    "entity_type" => "merchant",
                    "entity_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                    "file_store_id" => "JWeLWktZ6Id3NL",
                    "source" => "UFH",
                    "document_type" => "memorandum_of_association",
                    "entity_type" => "merchant",
                    "entity_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "document_type" => "memorandum_of_association",
                    "entity_type" => "stakeholder",
                    "entity_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "ocr_verify" => "true",
                    "validation_id" => Helper::getUniqueIdCallBack()(),
                    "upload_by_admin_id" => Helper::getUniqueIdCallBack()(),
                    "document_date" => 123456,
                    "metadata" => json_decode('[{"field":"metadata"}, {"name":"hello world"}]'),
                ],
            ],
            [
                Constant::API_BUILD_ATTRIBUTES => [
                    "merchant_id" => Helper::getUniqueIdCallBack()(),
                    "file_store_id" => "JWeLWktZ6Id3NL",
                    "source" => "UFH",
                    "document_type" => "memorandum_of_association",
                    "entity_type" => "merchant",
                    "entity_id" => Helper::getUniqueIdCallBack()(),
                ],
                Constant::UPDATE_ATTRIBUTES => [
                    "document_type" => "memorandum_of_association",
                    "entity_type" => "stakeholder",
                    "entity_id" => Helper::getUniqueIdCallBack()(),
                    "ocr_verify" => "false",
                    "validation_id" => Helper::getUniqueIdCallBack()(),
                    "upload_by_admin_id" => Helper::getUniqueIdCallBack()(),
                    "document_date" => 123456,
                    "metadata" => json_decode('[{"field":"metadata"}, {"name":"hello world"}]'),
                ],
                Constant::CUSTOM_ATTRIBUTES => [
                    "id" => Helper::getUniqueIdCallBack(),
                    "ocr_verify" => "true",
                    "validation_id" => Helper::getUniqueIdCallBack()(),
                    "upload_by_admin_id" => Helper::getUniqueIdCallBack()(),
                    "document_date" => 123456,
                    "metadata" => json_decode('[{"field":"metadata"}, {"name":"hello world"}]'),
                ],
            ],
        ];
    }

}
