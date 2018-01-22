<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;

class BusinessType
{

    /**
     * These keys are used to fetch the type of the business from its integer representation.
     * @todo: Revamp and remove this code and implement a cleaner approach.
     */
    const TYPE1     = 'Proprietorship';
    const TYPE2     = 'Individual';
    const TYPE3     = 'Partnership';
    const TYPE4     = 'Private Limited';
    const TYPE5     = 'Public Limited';
    const TYPE6     = 'LLP';
    const TYPE7     = 'NGO';
    const TYPE8     = 'Educational Institutes';
    const TYPE9     = 'Trust';
    const TYPE10    = 'Society';
    const TYPE11    = 'Not yet registered';
    const TYPE12    = 'Other';

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

    /**
     * The database field for business_type is a string but integer values are currently being stored in it.
     * To maintain compatibility, the input keys are being mapped to indices.
     *
     * @todo: Refactor and clean up the constants - TYPE . $index
     *
     * @var array
     */
    protected static $typeIndexMap = [
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
