<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant\Detail\Entity as Detail;

class FileType
{
    const BUSINESS_PROOF                = 'business_proof';
    const BUSINESS_PAN                  = 'business_pan';
    const BUSINESS_OPERATION_PROOF      = 'business_operation_proof';
    const ADDRESS_PROOF                 = 'address_proof';
    const PROMOTER_PROOF                = 'promoter_proof';
    const PROMOTER_PAN                  = 'promoter_pan';
    const PROMOTER_ADDRESS              = 'promoter_address';

    private static $typeToFieldMap = [
        self::BUSINESS_PROOF            => Detail::BUSINESS_PROOF_URL,
        self::BUSINESS_PAN              => Detail::BUSINESS_PAN_URL,
        self::BUSINESS_OPERATION_PROOF  => Detail::BUSINESS_OPERATION_PROOF_URL,
        self::ADDRESS_PROOF             => Detail::ADDRESS_PROOF_URL,
        self::PROMOTER_PROOF            => Detail::PROMOTER_PROOF_URL,
        self::PROMOTER_PAN              => Detail::PROMOTER_PAN_URL,
        self::PROMOTER_ADDRESS          => Detail::PROMOTER_ADDRESS_URL,
    ];

    public static function getFieldForType(string $type)
    {
        return self::$typeToFieldMap[$type];
    }
}
