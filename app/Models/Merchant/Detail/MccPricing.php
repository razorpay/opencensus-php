<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class MccPricing
{
    const MCC_CODE='mcc_code';
    const MCC_NAME='mcc_name';
    const MANDATORY_FLAG='mandatory_flag';
    const DEBIT_CARD_QR_ONUS='debit_card_qr_onus';
    const DEBIT_CARD_QR_OFFUS='debit_card_qr_offus';
    const CREDIT_CARD_PREMIUM_ONUS='credit_card_premium_onus';
    const CREDIT_CARD_PREMIUM_OFFUS='credit_card_premium_offus';
    const CREDIT_CARD_NON_PREMIUM_ONUS='credit_card_non_premium_onus';
    const CREDIT_CARD_NON_PREMIUM_OFFUS='credit_card_non_premium_offus';
    const AXIS_UPI_MSF_L20K='axis_upi_msf_l20k';
    const AXIS_UPI_MSF_G20K='axis_upi_msf_g20k';

    const MCC_PRICING = [
        0    => [
            self::MCC_NAME                      => 'Default MCC',
            self::MANDATORY_FLAG                => 'Mandatory',
            self::DEBIT_CARD_QR_ONUS            => 0.004,
            self::DEBIT_CARD_QR_OFFUS           => 0.004,
            self::CREDIT_CARD_PREMIUM_ONUS      => 0.006,
            self::CREDIT_CARD_PREMIUM_OFFUS     => 0.006,
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => 0.006,
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => 0.006,
            self::AXIS_UPI_MSF_L20K             => 0.0065,
            self::AXIS_UPI_MSF_G20K             => 0.0065
        ],
        9399 => [
            self::MCC_NAME                      => 'Government Services—not elsewhere classified',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => 0.003,
            self::DEBIT_CARD_QR_OFFUS           => 0.003,
            self::CREDIT_CARD_PREMIUM_ONUS      => 0.004,
            self::CREDIT_CARD_PREMIUM_OFFUS     => 0.004,
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => 0.004,
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => 0.004,
            self::AXIS_UPI_MSF_L20K             => 0.0065,
            self::AXIS_UPI_MSF_G20K             => 0.0065
        ],
        5411 => [
            self::MCC_NAME                      => 'Grocery Stores, Supermarkets',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => 0.0025,
            self::DEBIT_CARD_QR_OFFUS           => 0.0025,
            self::CREDIT_CARD_PREMIUM_ONUS      => 0.003,
            self::CREDIT_CARD_PREMIUM_OFFUS     => 0.003,
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => 0.003,
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => 0.003,
            self::AXIS_UPI_MSF_L20K             => 0.0065,
            self::AXIS_UPI_MSF_G20K             => 0.0065
        ],
    ];

    /**
     * @param int $mccCode
     *
     * @return array
     */
    public static function getMccPricing(int $mccCode): array
    {
        if (self::MCC_PRICING[$mccCode])
        {
            return self::MCC_TCC_MAPPING[$mccCode];
        }
        else
        {
            return self::MCC_TCC_MAPPING[0];
        }
    }
}
