<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;
use RZP\Models\Merchant\RazorxTreatment;
class BusinessType
{

    /**
     * These keys are used to fetch the type of the business from its integer representation.
     *
     * @todo: Revamp and remove this code and implement a cleaner approach.
     */
    const TYPE1  = 'Proprietorship';
    const TYPE2  = 'Individual';
    const TYPE3  = 'Partnership';
    const TYPE4  = 'Private Limited';
    const TYPE5  = 'Public Limited';
    const TYPE6  = 'LLP';
    const TYPE7  = 'NGO';
    const TYPE8  = 'Educational Institutes';
    const TYPE9  = 'Trust';
    const TYPE10 = 'Society';
    const TYPE11 = 'Not yet registered';
    const TYPE12 = 'Other';
    const TYPE13  = 'HUF';

    /**
     * These keys define the input keys for business_type.
     * It is used to dynamically create accounts using Account APIs.
     */
    const LLP                    = 'llp';
    const NGO                    = 'ngo';
    const OTHER                  = 'other';
    const INDIVIDUAL             = 'individual';
    const PARTNERSHIP            = 'partnership';
    const PROPRIETORSHIP         = 'proprietorship';
    const PUBLIC_LIMITED         = 'public_limited';
    const PRIVATE_LIMITED        = 'private_limited';
    const TRUST                  = 'trust';
    const SOCIETY                = 'society';
    const NOT_YET_REGISTERED     = 'not_yet_registered';
    const EDUCATIONAL_INSTITUTES = 'educational_institutes';
    const HUF                    = 'huf';

    /**
     * The database field for business_type is a string but integer values are currently being stored in it.
     * To maintain compatibility, the input keys are being mapped to indices.
     *
     * @todo: Refactor and clean up the constants - TYPE . $index
     *
     * @var array
     */
    public static $typeIndexMap = [
        self::PROPRIETORSHIP         => 1,
        self::INDIVIDUAL             => 2,
        self::PARTNERSHIP            => 3,
        self::PRIVATE_LIMITED        => 4,
        self::PUBLIC_LIMITED         => 5,
        self::LLP                    => 6,
        self::NGO                    => 7,
        self::EDUCATIONAL_INSTITUTES => 8,
        self::TRUST                  => 9,
        self::SOCIETY                => 10,
        self::NOT_YET_REGISTERED     => 11,
        self::OTHER                  => 12,
        self::HUF                    => 13,
    ];

    protected static $displayNameMap = [
        self::PROPRIETORSHIP         => "Proprietorship",
        self::INDIVIDUAL             => "Individual",
        self::PARTNERSHIP            => "Partnership",
        self::PRIVATE_LIMITED        => "Private Limited",
        self::PUBLIC_LIMITED         => "Public Limited",
        self::LLP                    => "LLP",
        self::NGO                    => "NGO",
        self::EDUCATIONAL_INSTITUTES => "Educational Institutes",
        self::TRUST                  => "Trust",
        self::SOCIETY                => "Society",
        self::NOT_YET_REGISTERED     => "Not Yet Registered",
        self::OTHER                  => "Other",
        self::HUF                    => "HUF",
    ];
    const REGISTERED   = 'registered';
    const UNREGISTERED = 'unregistered';

    // business type is divided into two category which decides on-boarding experience
    public static    $businessTypeBuckets                               = [
        self::REGISTERED   => [
            self::PROPRIETORSHIP,
            self::PARTNERSHIP,
            self::PRIVATE_LIMITED,
            self::PUBLIC_LIMITED,
            self::LLP,
            self::EDUCATIONAL_INSTITUTES,
            self::TRUST,
            self::SOCIETY,
            self::OTHER,
            self::NGO,
            self::HUF
        ],
        self::UNREGISTERED => [
            self::INDIVIDUAL,
            self::NOT_YET_REGISTERED,
        ]
    ];

    public static    $businessTypeExperiments                           = [
        self::HUF                       => RazorxTreatment::HUF_BUSINESS_TYPE,
        self::EDUCATIONAL_INSTITUTES    => RazorxTreatment::EDUCATION_OTHERS_BUSINESS_TYPE,
        self::OTHER                     => RazorxTreatment::EDUCATION_OTHERS_BUSINESS_TYPE
    ];

    protected static $GreylistedInternationalActivationFlowBusinessType = [
        self::PROPRIETORSHIP,
        self::NGO,
        self::SOCIETY,
        self::TRUST
    ];

    public static    $ValidateCompanyPanBusinessType                    = [
        self::PRIVATE_LIMITED,
        self::PUBLIC_LIMITED,
        self::LLP,
        self::EDUCATIONAL_INSTITUTES,
        self::TRUST,
        self::SOCIETY,
        self::OTHER,
        self::NGO,
        self::PARTNERSHIP,
        self::HUF
    ];

    protected static $ValidateGSTINBusinessType                         = [
        self::PROPRIETORSHIP
    ];

    public static    $ValidateCINBusinessType                           = [
        self::PRIVATE_LIMITED,
        self::PUBLIC_LIMITED,
        self::LLP,
    ];

    protected static $ValidateShopEstbBusinessType                      = [
        self::PROPRIETORSHIP
    ];

    protected static $validCompanySearchBusinessTypes                   = [
        self::PUBLIC_LIMITED,
        self::PRIVATE_LIMITED,
        self::LLP,
    ];

    protected static $validAadhaarEsignBusinessTypes                    = [
        self::NOT_YET_REGISTERED,
        self::INDIVIDUAL,
        self::PROPRIETORSHIP,
        self::PARTNERSHIP,
        self::HUF,
        self::PUBLIC_LIMITED,
        self::PRIVATE_LIMITED,
        self::LLP,
        self::TRUST,
        self::SOCIETY,
        self::NGO
    ];


    public static function isValidBusinessType(string $businessType)
    {
        return array_key_exists($businessType, self::$typeIndexMap);
    }

    public static function isAadhaarEsignVerificationRequired($businessType)
    {
        return in_array($businessType, self::$validAadhaarEsignBusinessTypes, true);
    }

    public static function isBusinessTypeGreylistedForInternational($businessType = null)
    {
        if (empty($businessType) === true)
        {
            return false;
        }

        return in_array($businessType, self::$GreylistedInternationalActivationFlowBusinessType, true);
    }

    /**
     * @param string $businessTypeBucket
     *
     * @return bool
     */
    public static function isValidBusinessTypeBucket(string $businessTypeBucket)
    {
        return isset(self::$businessTypeBuckets[$businessTypeBucket]) === true;
    }

    /**
     * Returns indexes of un-registered business type
     *
     * @return array
     */
    public static function getIndexForUnregisteredBusiness(): array
    {
        $unRegisteredBusiness = self::$businessTypeBuckets[self::UNREGISTERED];

        return array_map(function($businessType) {
            return self::getIndexFromKey($businessType);
        }, $unRegisteredBusiness);
    }

    /**
     * Checks business type is a unregistered business type or not
     *
     * @param  $businessType
     *
     * @return bool
     */
    public static function isUnregisteredBusiness(string $businessType): bool
    {
        if (empty($businessType))
        {
            return false;
        }

        $unRegisteredBusiness = self::$businessTypeBuckets[self::UNREGISTERED];

        return in_array($businessType, $unRegisteredBusiness, true);
    }

    /**
     * Checks that business type belongs to unregistered business or not
     *
     * @param int $businessType
     *
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function isUnregisteredBusinessIndex(int $businessType): bool
    {
        $businessType = self::getKeyFromIndex($businessType);

        return BusinessType::isUnregisteredBusiness($businessType) === true;
    }

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__ . '::' . 'TYPE' . $num);
    }

    /**
     * Given a key, it will return the index that the key corresponds to
     *
     * @param string $key
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function getIndexFromKey(string $key)
    {
        if (isset(self::$typeIndexMap[$key]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid business type: $key", Entity::BUSINESS_TYPE, ['type' => $key]);
        }

        return self::$typeIndexMap[$key];
    }

    /**
     * Given an index, it will return the key that the index corresponds to
     *
     * @param string|null $index
     *
     * @return string
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function getKeyFromIndex($index)
    {
        if (empty($index) === true)
        {
            return '';
        }

        $map = array_flip(self::$typeIndexMap);

        if (isset($map[$index]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid business type index: $index", Entity::BUSINESS_TYPE);
        }

        return $map[$index];
    }

    public static function isCompanyPanEnableBusinessTypes($businessType): bool
    {
        if (empty($businessType) === true)
        {
            return false;
        }

        $businessTypeName = self::getKeyFromIndex($businessType);

        return in_array($businessTypeName, self::$ValidateCompanyPanBusinessType, true);
    }


    public static function isGstinVerificationEnableBusinessTypes($businessTypeValue): bool
    {
        if (empty($businessTypeValue) === true)
        {
            return false;
        }

        $businessTypeName = self::getKeyFromIndex($businessTypeValue);

        return in_array($businessTypeName, self::$businessTypeBuckets[self::REGISTERED], true);
    }

    public static function isGstinVerificationExcludedBusinessTypes($businessTypeValue): bool
    {
        if (empty($businessTypeValue) === true)
        {
            return false;
        }

        $businessTypeName = self::getKeyFromIndex($businessTypeValue);

        $excludedBusinessType = array_merge(self::$businessTypeBuckets[self::UNREGISTERED], [self::PROPRIETORSHIP,]);

        return in_array($businessTypeName, $excludedBusinessType, true);
    }

    public static function isCinVerificationEnableBusinessTypes($businessType): bool
    {
        if (empty($businessType) === true)
        {
            return false;
        }

        $businessTypeName = self::getKeyFromIndex($businessType);

        return in_array($businessTypeName, self::$ValidateCINBusinessType, true);
    }

    public static function isShopEstbVerificationEnableBusinessTypes($businessType): bool
    {
        if (empty($businessType) === true)
        {
            return false;
        }

        $businessTypeName = self::getKeyFromIndex($businessType);

        return in_array($businessTypeName, self::$ValidateShopEstbBusinessType, true);
    }

    public static function isValidCompanySearchBusinessType($businessTypeName): bool
    {
        if (empty($businessTypeName) === true)
        {
            return false;
        }

        return in_array($businessTypeName, self::$validCompanySearchBusinessTypes, true);
    }

    public static function getCOIApplicableBusinessTypes()
    {
        return [
            self::PRIVATE_LIMITED,
            self::PUBLIC_LIMITED,
            self::LLP
        ];
    }

    public static function getTrustSocietyNgoBusinessCertificateApplicableBusinessTypes()
    {
        return [
            self::NGO,
            self::TRUST,
            self::SOCIETY
        ];
    }

    /**
     * Given a key, it will return the display name that the key corresponds to
     *
     * @param string $key
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function getDisplayNameFromKey(string $key)
    {
        if (isset(self::$displayNameMap[$key]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid business type: $key", Entity::BUSINESS_TYPE, ['type' => $key]);
        }

        return self::$displayNameMap[$key];
    }
}
