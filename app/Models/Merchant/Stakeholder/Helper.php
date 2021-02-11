<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Address;
use RZP\Models\Merchant\Detail;

class Helper
{
    public static function getStakeholderInput(array $input): array
    {
        $stakeholderInput = [];

        if (array_key_exists(Entity::PERCENTAGE_OWNERSHIP, $input) === true)
        {
            $stakeholderInput[Entity::PERCENTAGE_OWNERSHIP] = $input[Entity::PERCENTAGE_OWNERSHIP] ? (int) round(($input[Entity::PERCENTAGE_OWNERSHIP] * 100), 0) : null;
        }

        $keyMap = [
            Entity::NAME                   => Entity::NAME,
            Entity::EMAIL                  => Entity::EMAIL,
            Entity::NOTES                  => Entity::NOTES,
            Entity::AADHAAR_LINKED         => Entity::AADHAAR_LINKED,
            Entity::AADHAAR_ESIGN_STATUS   => Entity::AADHAAR_ESIGN_STATUS,
            Entity::AADHAAR_PIN            => Entity::AADHAAR_PIN,
            Entity::BVS_PROBE_ID           => Entity::BVS_PROBE_ID,
        ];

        self::addKeyMapFromInput($keyMap, $input, $stakeholderInput);

        if (isset($input[Constants::RELATIONSHIP]) === true)
        {
            $keyMap = [
                Entity::DIRECTOR   => Constants::DIRECTOR,
                Entity::EXECUTIVE  => Constants::EXECUTIVE,
            ];

            self::addKeyMapFromInput($keyMap, $input[Constants::RELATIONSHIP], $stakeholderInput);
        }

        if (isset($input[Constants::PHONE]) === true)
        {
            $keyMap = [
                Entity::PHONE_PRIMARY   => Constants::PRIMARY,
                Entity::PHONE_SECONDARY => Constants::SECONDARY,
            ];

            self::addKeyMapFromInput($keyMap, $input[Constants::PHONE], $stakeholderInput);
        }

        if (isset($input[Constants::KYC]) === true)
        {
            $keyMap = [
                Entity::POI_IDENTIFICATION_NUMBER   => Constants::PAN,
            ];

            self::addKeyMapFromInput($keyMap, $input[Constants::KYC], $stakeholderInput);
        }

        if (isset($input[Constants::ADDRESSES][Constants::RESIDENTIAL]) === true)
        {
            $stakeholderInput[Constants::ADDRESSES][] = [Address\Entity::PRIMARY => true, Address\Entity::TYPE => Address\Type::RESIDENTIAL,];

            $keyMap = [
                Address\Entity::LINE1   => Constants::STREET,
                Address\Entity::CITY    => Constants::CITY,
                Address\Entity::STATE   => Constants::STATE,
                Address\Entity::ZIPCODE => Constants::POSTAL_CODE,
                Address\Entity::COUNTRY => Constants::COUNTRY,
            ];

            self::addKeyMapFromInput($keyMap, $input[Constants::ADDRESSES][Constants::RESIDENTIAL], $stakeholderInput[Constants::ADDRESSES][0]);

            if (isset($stakeholderInput[Constants::ADDRESSES][0][Address\Entity::COUNTRY]) === true)
            {
                $stakeholderInput[Constants::ADDRESSES][0][Address\Entity::COUNTRY] = strtolower($stakeholderInput[Constants::ADDRESSES][0][Address\Entity::COUNTRY]);
            }
        }

        return $stakeholderInput;
    }

    public static function getMerchantDetailInput(array $input): array
    {
        $merchantDetailInput = [];

        if (isset($input[Constants::KYC]) === true)
        {
            $keyMap = [
                Detail\Entity::PROMOTER_PAN   => Constants::PAN,
            ];

            self::addKeyMapFromInput($keyMap, $input[Constants::KYC], $merchantDetailInput);
        }

        $keyMap = [
            Detail\Entity::PROMOTER_PAN_NAME => Entity::NAME,
        ];

        self::addKeyMapFromInput($keyMap, $input, $merchantDetailInput);

        return $merchantDetailInput;
    }

    protected static function addKeyMapFromInput(array $keyMap, array $input, array & $data)
    {
        foreach ($keyMap as $key => $value)
        {
            if (array_key_exists($value, $input) === true)
            {
                $data[$key] = $input[$value];
            }
        }
    }
}
