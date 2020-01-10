<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Card;

class Category
{
    const CONSUMER              = 'Consumer';
    const PLATINUM              = 'Platinum';
    const SIGNATURE             = 'Signature';
    const INFINITE              = 'Infinite';
    const BUSINESS              = 'Business';
    const CORPORATE             = 'Corporate';
    const PURCHASING            = 'Purchasing';
    const SIGNATURE_BUSINESS    = 'Signature Business';
    const PREMIUM               = 'Premium';
    const SUPER_PREMIUM         = 'Super Premium';
    const ELITE                 = 'Elite';
    const COMMERCIAL_STANDARD   = 'Commercial Standard';
    const COMMERCIAL            = 'Commercial';
    const STANDARD              = 'Standard';
    const VISA                  = 'Visa';
    const SELECT                = 'Select';
    const PREMIER               = 'Premier';
    const CLASSIC               = 'Classic';

    public static $supportedCardCategories = [
        Card\NetworkName::VISA                  => [
            Card\Type::CREDIT           => [
                Card\SubType::CONSUMER      => [
                    self::CONSUMER, self::PLATINUM, self::SIGNATURE, self::INFINITE,
                ],
                Card\SubType::BUSINESS      => [
                    self::BUSINESS, self::CORPORATE, self::PURCHASING, self::SIGNATURE_BUSINESS,
                ]
            ],
            Card\Type::PREPAID            => [
                Card\SubType::CONSUMER      => [
                    self::CONSUMER, self::PLATINUM, self::SIGNATURE, self::INFINITE,
                ],
                Card\SubType::BUSINESS      => [
                    self::BUSINESS, self::CORPORATE, self::PURCHASING, self::SIGNATURE_BUSINESS,
                ]
            ],
            Card\Type::DEBIT            => [
                Card\SubType::CONSUMER      => [
                    self::CONSUMER, self::PLATINUM, self::SIGNATURE, self::INFINITE,
                ],
                Card\SubType::BUSINESS      => [
                    self::BUSINESS, self::CORPORATE, self::PURCHASING, self::SIGNATURE_BUSINESS,
                ]
            ]
        ],

        Card\NetworkName::MC                    => [
            Card\Type::CREDIT           => [
                Card\SubType::CONSUMER      => [
                    self::STANDARD, self::PREMIUM, self::SUPER_PREMIUM, self::ELITE,
                ],
                Card\SubType::BUSINESS      => [
                    self::COMMERCIAL_STANDARD,
                ]
            ],
            Card\Type::DEBIT            => [
                Card\SubType::CONSUMER      => [
                    self::STANDARD, self::PREMIUM,
                ],
                Card\SubType::BUSINESS      => [
                    self::COMMERCIAL,
                ]
            ],
        ]
    ];


    /** Check whether a category is supported
     * @param string $network
     * @param string $type
     * @param string $subtype
     * @param string $category
     * @return bool
     */
    public static function isValidIinCategory($network, $type, $subtype, $category): bool
    {
        $supportedTypes = isset(self::$supportedCardCategories[$network][$type][$subtype]);

        if($supportedTypes === true)
        {
            $categories = self::$supportedCardCategories[$network][$type][$subtype];

            return in_array($category, $categories, true);
        }

        return true;
    }
}
