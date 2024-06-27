<?php

namespace RZP\Models\BankAccount;


use RZP\Constants\Country;

class BankCodes
{
    const AFFIN_BANK                     = "Affin Bank";
    const AFFIN_ISLAMIC_BANK             = "Affin Islamic Bank";
    const AGROBANK                       = "Agrobank";
    const ALLIANCE_BANK                  = "Alliance Bank";
    const ALLIANCE_ISLAMIC_BANK          = "Alliance Islamic Bank";
    const AL_RAJHI_BANKING               = "Al-Rajhi Banking & Inv.Corp. (M) Bhd";
    const AMBANK                         = "AmBank Berhad";
    const AMISLAMIC_BANK                 = "AmIslamic Bank";
    const BANGKOK_BANK                   = "Bangkok Bank Berhad";
    const BANK_ISLAM                     = "Bank Islam Malaysia Berhad";
    const BANK_MUAMALAT                  = "Bank Muamalat Malaysia Berhad";
    const BANK_OF_AMERICA                = "Bank of America";
    const BANK_OF_CHINA                  = "Bank of China";
    const BANK_RAKYAT                    = "Bank Rakyat";
    const BANK_SIMPANAN_NASIONAL         = "Bank Simpanan Nasional";
    const BNP_PARIBAS                    = "BNP Paribas";
    const CHINA_CONSTRUCTION_BANK        = "China Construction Bank";
    const CIMB_BANK                      = "CIMB Bank";
    const CIMB_ISLAMIC_BANK              = "CIMB Islamic Bank";
    const CITIBANK                       = "Citibank";
    const DEUTSCHE_BANK                  = "Deutsche Bank";
    const HONG_LEONG_BANK                = "Hong Leong Bank";
    const HONG_LEONG_ISLAMIC_BANK        = "Hong Leong Islamic Bank";
    const HSBC_AMANAH                    = "HSBC Amanah";
    const HSBC_BANK                      = "HSBC Bank";
    const ICBC                           = "Industrial & Commercial Bank of China";
    const JP_MORGAN_CHASE_BANK           = "JP Morgan Chase Bank";
    const KUWAIT_FINANCE_HOUSE           = "Kuwait Finance House";
    const MALAYAN_BANKING                = "Malayan Banking Berhad";
    const MAYBANK_ISLAMIC                = "Maybank Islamic Bank";
    const MBSB_BANK                      = "MBSB Bank Malaysia Berhad";
    const MIZUHO_BANK                    = "Mizuho Bank (M) Berhad";
    const MUFG_BANK                      = "MUFG Bank (Malaysia) BHD";
    const OCBC_AL_AMIN_BANK              = "OCBC Al-Amin Bank Berhad";
    const OCBC_BANK                      = "OCBC Bank";
    const PUBLIC_BANK                    = "Public Bank";
    const PUBLIC_ISLAMIC_BANK            = "Public Islamic Bank Berhad";
    const RHB_BANK                       = "RHB Bank";
    const RHB_ISLAMIC_BANK               = "RHB Islamic Bank";
    const STANDARD_CHARTERED_BANK        = "Standard Chartered Bank Berhad";
    const STANDARD_CHARTERED_SAADIQ_BANK = "Standard Chartered Saadiq Bank Berhad";
    const SUMITOMO_MITSUI_BANK           = "Sumitomo Mitsui Bank";
    const UOB                            = "United Overseas Bank";
    const TNG_DIGITAL                    = "TNG Digital";

    const COUNTRY_ELIGIBLE_FOR_SWIFT_CODE_MAPPING = [
        Country::MY
    ];

    const BANK_CODE_TO_SWIFTCODE_MAPPING = [
        self::AFFIN_BANK                        => "PHBMMYKL",
        self::AFFIN_ISLAMIC_BANK                => "PHBMMYKL",
        self::AGROBANK                          => "AGOBMYKL",
        self::ALLIANCE_BANK                     => "MFBBMYKL",
        self::ALLIANCE_ISLAMIC_BANK             => "MFBBMYKL",
        self::AL_RAJHI_BANKING                  => "RJHIMYKL",
        self::AMBANK                            => "ARBKMYKL",
        self::AMISLAMIC_BANK                    => "ARBKMYKL",
        self::BANGKOK_BANK                      => "BKKBMYKL",
        self::BANK_ISLAM                        => "BIMBMYKL",
        self::BANK_MUAMALAT                     => "BMMBMYKL",
        self::BANK_OF_AMERICA                   => "BOFAMY2X",
        self::BANK_OF_CHINA                     => "BKCHMYKL",
        self::BANK_RAKYAT                       => "BKRMMYKL",
        self::BANK_SIMPANAN_NASIONAL            => "BSNAMYK1",
        self::BNP_PARIBAS                       => "BNPAMYKL",
        self::CHINA_CONSTRUCTION_BANK           => "PCBCMYKL",
        self::CIMB_BANK                         => "CIBBMYKL",
        self::CIMB_ISLAMIC_BANK                 => "CIBBMYKL",
        self::CITIBANK                          => "CITIMYKL",
        self::DEUTSCHE_BANK                     => "DEUTMYKL",
        self::HONG_LEONG_BANK                   => "HLBBMYKL",
        self::HONG_LEONG_ISLAMIC_BANK           => "HLBBMYKL",
        self::HSBC_AMANAH                       => "HBMBMYKL",
        self::HSBC_BANK                         => "HBMBMYKL",
        self::ICBC                              => "ICBKMYKL",
        self::JP_MORGAN_CHASE_BANK              => "CHASMYKX",
        self::KUWAIT_FINANCE_HOUSE              => "KFHOMYKL",
        self::MALAYAN_BANKING                   => "MBBEMYKL",
        self::MAYBANK_ISLAMIC                   => "MBBEMYKL",
        self::MBSB_BANK                         => "AFBQMYKL",
        self::MIZUHO_BANK                       => "MHCBMYKA",
        self::MUFG_BANK                         => "BOTKMYKX",
        self::OCBC_AL_AMIN_BANK                 => "OCBCMYKL",
        self::OCBC_BANK                         => "OCBCMYKL",
        self::PUBLIC_BANK                       => "PBBEMYKL",
        self::PUBLIC_ISLAMIC_BANK               => "PBBEMYKL",
        self::RHB_BANK                          => "RHBBMYKL",
        self::RHB_ISLAMIC_BANK                  => "RHBBMYKL",
        self::STANDARD_CHARTERED_BANK           => "SCBLMYKX",
        self::STANDARD_CHARTERED_SAADIQ_BANK    => "SCBLMYKX",
        self::SUMITOMO_MITSUI_BANK              => "SMBCMYKL",
        self::UOB                               => "UOVBMYKL",
        self::TNG_DIGITAL                       => "TNGDMYNB",
    ];
}
