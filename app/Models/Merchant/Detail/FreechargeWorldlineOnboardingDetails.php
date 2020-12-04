<?php

namespace RZP\Models\Merchant\Detail;

use Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class FreechargeWorldlineOnboardingDetails
{
    // pricing 
    const MCC_CODE                      = 'mcc_code';
    const MCC_NAME                      = 'mcc_name';
    const MANDATORY_FLAG                = 'mandatory_flag';
    const DEBIT_CARD_QR_ONUS            = 'debit_card_qr_onus';
    const DEBIT_CARD_QR_OFFUS           = 'debit_card_qr_offus';
    const CREDIT_CARD_PREMIUM_ONUS      = 'credit_card_premium_onus';
    const CREDIT_CARD_PREMIUM_OFFUS     = 'credit_card_premium_offus';
    const CREDIT_CARD_NON_PREMIUM_ONUS  = 'credit_card_non_premium_onus';
    const CREDIT_CARD_NON_PREMIUM_OFFUS = 'credit_card_non_premium_offus';
    const AXIS_UPI_MSF_L20K             = 'axis_upi_msf_l20k';
    const AXIS_UPI_MSF_G20K             = 'axis_upi_msf_g20k';
    const QR_CODE_BASE_TXNS_ONUS        = 'qr_code_base_txns_onus';
    const QR_CODE_BASE_TXNS_OFFUS       = 'qr_code_base_txns_offus';

    // other details
    const BUSINESSTYPE                  = 'businesstype';
    const DIPCODE                       = 'dipcode';
    const TELVERICODE                   = 'telvericode';
    const SGCODE                        = 'sgcode';
    const SECODE                        = 'secode';
    const PRICECATE                     = 'pricecate';
    const MONTHRENTFEE                  = 'monthrentfee';
    const YEARRENTFEE                   = 'yearrentfee';
    const SETUPFEE                      = 'setupfee';
    const OTHERFEE                      = 'otherfee';
    const PAYBY                         = 'payby';
    const ACCNO                         = 'accno';
    const PAYSOLID                      = 'paysolid';
    const ACCLABEL                      = 'acclabel';

    const MCC_PRICING = [
        0    => [
            self::MCC_NAME                      => 'Default MCC',
            self::MANDATORY_FLAG                => 'Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '0.8',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        4111 => [
            self::MCC_NAME                      => 'Local/Suburban Commuter Passenger Transportation – Railroads, Feries, Local Water Transportation',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        6513 => [
            self::MCC_NAME                      => 'Real Estate Agents and Managers-Rentals',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        7349 => [
            self::MCC_NAME                      => 'Cleaning and Maintenance, Janitorial Services',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        7641 => [
            self::MCC_NAME                      => 'Furniture, Furniture Repair, and Furniture Refinishing',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        1520 => [
            self::MCC_NAME                      => 'General Contractors-Residential and Commercial',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        1799 => [
            self::MCC_NAME                      => 'Contractors – Special Trade, Not Elsewhere Classified',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        5411 => [
            self::MCC_NAME                      => 'Grocery Stores, Supermarkets',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '0.8',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        4900 => [
            self::MCC_NAME                      => 'Electric, Gas, Sanitary and Water Utilities',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '0.6',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '0.6',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        8211 => [
            self::MCC_NAME                      => 'Elementary and Secondary Schools',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '0.6',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '0.6',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ],
        6300 => [
            self::MCC_NAME                      => 'Insurance Sales, Underwriting, and Premiums',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '0.6',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '0.6',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '5.9',
            self::QR_CODE_BASE_TXNS_OFFUS       => '5.9'
        ],
        4722 => [
            self::MCC_NAME                      => 'Travel Agencies and Tour Operations',
            self::MANDATORY_FLAG                => 'Non-Mandatory',
            self::DEBIT_CARD_QR_ONUS            => '0',
            self::DEBIT_CARD_QR_OFFUS           => '0',
            self::CREDIT_CARD_PREMIUM_ONUS      => '0.8',
            self::CREDIT_CARD_PREMIUM_OFFUS     => '0.8',
            self::CREDIT_CARD_NON_PREMIUM_ONUS  => '1.42',
            self::CREDIT_CARD_NON_PREMIUM_OFFUS => '1.42',
            self::AXIS_UPI_MSF_L20K             => '0',
            self::AXIS_UPI_MSF_G20K             => '0',
            self::QR_CODE_BASE_TXNS_ONUS        => '0.55',
            self::QR_CODE_BASE_TXNS_OFFUS       => '0.7'
        ]
    ];

    const BARRED_MCC = [
        5399
    ];

    // These hardcoded values for freecharge are provided to us by axis bank
    const OTHER_DETAILS = [
        self::BUSINESSTYPE  => 'O',
        self::DIPCODE       => '2132323',
        self::TELVERICODE   => '2132323',
        self::SGCODE        => '654123',
        self::SECODE        => '2132323', 
        self::PRICECATE     => 'Other',
        self::MONTHRENTFEE  => '0',
        self::SETUPFEE      => '0',
        self::OTHERFEE      => '250.00',
        self::PAYBY         => 'A/C Credit',
        self::PAYSOLID      => '',
        self::ACCLABEL      => '',
    ];

    /**
     * @param int $mccCode
     *
     * @return array
     */
    public static function getMccPricing(int $mccCode): array
    {
        if (isset(self::MCC_PRICING[$mccCode]) === true)
        {
            return self::MCC_PRICING[$mccCode];
        }
        
        if (in_array($mccCode, self::BARRED_MCC) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MCC_IS_BARRED);
        }

        return self::MCC_PRICING[0];
    }

}

