<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;

class BusinessType
{
    /**
     * These keys are used to fetch the type of the business from its integer representation.
     * @todo: Revamp and remove this code and implement a cleaner approach.
     */
    const TYPE1  = 'Private Limited';
    const TYPE2  = 'Proprietorship';
    const TYPE3  = 'Partnership';
    const TYPE4  = 'Individual';
    const TYPE5  = 'Not yet registered';
    const TYPE6  = 'Public Limited';
    const TYPE7  = 'LLP';
    const TYPE8  = 'Educational Institutes';
    const TYPE9  = 'Trust / Society';
    const TYPE10 = 'NGO';
    const TYPE11 = 'Other';

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
    const TRUST_SOCIETY          = 'trust_society';
    const NOT_YET_REGISTERED     = 'not_yet_registered';
    const EDUCATIONAL_INSTITUTES = 'educational_institutes';

    /**
     * The database field for business_type is a string but integer values are currently being stored in it.
     * To maintain compatibility, the input keys are being mapped to indices.
     *
     * @todo: Refactor and clean up the constants - TYPE . $index
     *
     * @var array
     */
    protected static $typeIndexMap = [
        self::PRIVATE_LIMITED        => 1,
        self::PROPRIETORSHIP         => 2,
        self::PARTNERSHIP            => 3,
        self::INDIVIDUAL             => 4,
        self::NOT_YET_REGISTERED     => 5,
        self::PUBLIC_LIMITED         => 6,
        self::LLP                    => 7,
        self::EDUCATIONAL_INSTITUTES => 8,
        self::TRUST_SOCIETY          => 9,
        self::NGO                    => 10,
        self::OTHER                  => 11,
    ];

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'TYPE'.$num);
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
        if ($index === null)
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
}
