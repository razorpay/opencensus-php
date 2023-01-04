<?php

namespace RZP\Models\Payment\Processor;

class Fpx
{
    // FPX bank code
    const PHBM      = 'PHBM';
    const PHBM_C    = 'PHBM_C';
    const MFBB_C    = 'MFBB_C';
    const MFBB      = 'MFBB';
    const ARBK      = 'ARBK';
    const ARBK_C    = 'ARBK_C';
    const AGOB      = 'AGOB';
    const AGOB_C    = 'AGOB_C';
    const BNPA_C    = 'BNPA_C';
    const BIMB_C    = 'BIMB_C';
    const BIMB      = 'BIMB';
    const BKRM_C    = 'BKRM_C';
    const BKRM      = 'BKRM';
    const BMMB_C    = 'BMMB_C';
    const BMMB      = 'BMMB';
    const BKCH      = 'BKCH';
    const BSNA      = 'BSNA';
    const CIBB_C    = 'CIBB_C';
    const CIBB      = 'CIBB';
    const CITI_C    = 'CITI_C';
    const DEUT_C    = 'DEUT_C';
    const HSBC_C    = 'HSBC_C';
    const HSBC      = 'HSBC';
    const HLBB_C    = 'HLBB_C';
    const HLBB      = 'HLBB';
    const KFHO_C    = 'KFHO_C';
    const KFHO      = 'KFHO';
    const MBBE_C    = 'MBBE_C';
    const MBBE      = 'MBBE';
    const MB2U      = 'MB2U';
    const OCBC_C    = 'OCBC_C';
    const OCBC      = 'OCBC';
    const PBBE_C    = 'PBBE_C';
    const PBBE      = 'PBBE';
    const PBBN_C    = 'PBBN_C';
    const RHBB_C    = 'RHBB_C';
    const RHBB      = 'RHBB';
    const SCBL_C    = 'SCBL_C';
    const SCBL      = 'SCBL';
    const UOVB      = 'UOVB';
    const UOVB_C    = 'UOVB_C';

    // test bank
    const TEST0021      = 'TEST0021';
    const TEST0022      = 'TEST0022';
    const TEST0023      = 'TEST0023';
    const TEST0001      = 'TEST0001';
    const TEST0002      = 'TEST0002';
    const TEST0003      = 'TEST0003';
    const TEST0004      = 'TEST0004';
    const TEST0005      = 'TEST0005';

    protected static $b2cBanks = [
        self::PHBM,
        self::MFBB,
        self::ARBK,
        self::AGOB,
        self::BIMB,
        self::BKRM,
        self::BMMB,
        self::BKCH,
        self::BSNA,
        self::CIBB,
        self::HSBC,
        self::HLBB,
        self::KFHO,
        self::MBBE,
        self::MB2U,
        self::OCBC,
        self::PBBE,
        self::RHBB,
        self::SCBL,
        self::UOVB,
    ];

    protected static $b2bBanks = [
        self::PHBM_C,
        self::MFBB_C,
        self::ARBK_C,
        self::AGOB_C,
        self::BNPA_C,
        self::BIMB_C,
        self::BKRM_C,
        self::BMMB_C,
        self::CIBB_C,
        self::CITI_C,
        self::DEUT_C,
        self::HSBC_C,
        self::HLBB_C,
        self::KFHO_C,
        self::MBBE_C,
        self::OCBC_C,
        self::PBBE_C,
        self::PBBN_C,
        self::RHBB_C,
        self::SCBL_C,
        self::UOVB_C,
    ];

    public static $fpxB2BBankCodeMapping = [
        self::PHBM_C    => 'ABB0235',
        self::MFBB_C    => 'ABMB0213',
        self::ARBK_C    => 'AMBB0208',
        self::AGOB_C    => 'AGRO02',
        self::BNPA_C    => 'BNP003',
        self::BIMB_C    => 'BIMB0340',
        self::BKRM_C    => 'BKRM0602',
        self::BMMB_C    => 'BMMB0342',
        self::CIBB_C    => 'BCBB0235',
        self::CITI_C    => 'CIT0218',
        self::DEUT_C    => 'DBB0219',
        self::HSBC_C    => 'HSBC0223',
        self::HLBB_C    => 'HLB0224',
        self::KFHO_C    => 'KFH0346',
        self::MBBE_C    => 'MBB0228',
        self::OCBC_C    => 'OCBC0229',
        self::PBBE_C    => 'PBB0233',
        self::PBBN_C    => 'PBB0234',
        self::RHBB_C    => 'RHB0218',
        self::SCBL_C    => 'SCB0215',
        self::UOVB_C    => 'UOB0228'
    ];

    public static $fpxB2CBankCodeMapping = [
        self::PHBM      => 'ABB0233',
        self::MFBB      => 'ABMB0212',
        self::ARBK      => 'AMBB0209',
        self::AGOB      => 'AGRO01',
        self::BIMB      => 'BIMB0340',
        self::BKRM      => 'BKRM0602',
        self::BMMB      => 'BMMB0341',
        self::BKCH      => 'BOCM01',
        self::BSNA      => 'BSN0601',
        self::CIBB      => 'BCBB0235',
        self::HSBC      => 'HSBC0223',
        self::HLBB      => 'HLB0224',
        self::KFHO      => 'KFH0346',
        self::MBBE      => 'MBB0228',
        self::MB2U      => 'MB2U0227',
        self::OCBC      => 'OCBC0229',
        self::PBBE      => 'PBB0233',
        self::RHBB      => 'RHB0218',
        self::SCBL      => 'SCB0216',
        self::UOVB      => 'UOB0226'
    ];

    // FPX bank name
    protected static $names = [
        self::PHBM      => 'Affin Bank Berhad',
        self::PHBM_C    => 'AffinMax',
        self::MFBB_C    => 'Alliance Bank Malaysia Berhad',
        self::MFBB      => 'Alliance Bank Malaysia Berhad',
        self::ARBK      => 'AmBank Malaysia Berhad',
        self::ARBK_C    => 'AmBank Malaysia Berhad',
        self::AGOB      => 'BANK PERTANIAN MALAYSIA BERHAD (AGROBANK)',
        self::AGOB_C    => 'BANK PERTANIAN MALAYSIA BERHAD (AGROBANK)',
        self::BNPA_C    => 'BNP Paribas Malaysia Berhad',
        self::BIMB_C    => 'Bank Islam Malaysia Berhad',
        self::BIMB      => 'Bank Islam Malaysia Berhad',
        self::BKRM_C    => 'Bank Kerjasama Rakyat Malaysia Berhad',
        self::BKRM      => 'Bank Kerjasama Rakyat Malaysia Berhad',
        self::BMMB_C    => 'Bank Muamalat Malaysia Berhad',
        self::BMMB      => 'Bank Muamalat Malaysia Berhad',
        self::BKCH      => 'Bank Of China (M) Berhad',
        self::BSNA      => 'Bank Simpanan Nasional',
        self::CIBB_C    => 'CIMB Bank Berhad',
        self::CIBB      => 'CIMB Bank Berhad',
        self::CITI_C    => 'CITI Bank Bhd',
        self::DEUT_C    => 'Deutsche Bank (Malaysia) Berhad',
        self::HSBC_C    => 'HSBC Bank Malaysia Berhad',
        self::HSBC      => 'HSBC Bank Malaysia Berhad',
        self::HLBB_C    => 'Hong Leong Bank Berhad',
        self::HLBB      => 'Hong Leong Bank Berhad',
        self::KFHO_C    => 'Kuwait Finance House (Malaysia) Berhad',
        self::KFHO      => 'Kuwait Finance House (Malaysia) Berhad',
        self::MBBE_C    => 'Malayan Banking Berhad (M2E)',
        self::MBBE      => 'Malayan Banking Berhad (M2E)',
        self::MB2U      => 'Malayan Banking Berhad (M2U)',
        self::OCBC_C    => 'OCBC Bank Malaysia Berhad',
        self::OCBC      => 'OCBC Bank Malaysia Berhad',
        self::PBBE_C    => 'Public Bank Berhad',
        self::PBBE      => 'Public Bank Berhad',
        self::PBBN_C    => 'PB Enterprise',
        self::RHBB_C    => 'RHB Bank Berhad',
        self::RHBB      => 'RHB Bank Berhad',
        self::SCBL_C    => 'Standard Chartered Bank',
        self::SCBL      => 'Standard Chartered Bank',
        self::UOVB      => 'United Overseas Bank',
        self::UOVB_C    => 'United Overseas Bank',
    ];

    // FPX bank display name
    protected static $displayNames = [
        self::PHBM      => 'Affin Bank',
        self::PHBM_C    => 'AFFINMAX',
        self::MFBB_C    => 'Alliance Bank (Business)',
        self::MFBB      => 'Alliance Bank (Personal)',
        self::ARBK      => 'AmBank',
        self::ARBK_C    => 'AmBank',
        self::AGOB      => 'AGRONet',
        self::AGOB_C    => 'AGRONetBIZ',
        self::BNPA_C    => 'BNP Paribas',
        self::BIMB_C    => 'Bank Islam',
        self::BIMB      => 'Bank Islam',
        self::BKRM_C    => 'i-bizRakyat',
        self::BKRM      => 'Bank Rakyat',
        self::BMMB_C    => 'Bank Muamalat',
        self::BMMB      => 'Bank Muamalat',
        self::BKCH      => 'Bank Of China',
        self::BSNA      => 'BSN',
        self::CIBB_C    => 'CIMB Bank',
        self::CIBB      => 'CIMB Clicks',
        self::CITI_C    => 'Citibank Corporate Banking',
        self::DEUT_C    => 'Deutsche Bank',
        self::HSBC_C    => 'HSBC Bank',
        self::HSBC      => 'HSBC Bank',
        self::HLBB_C    => 'Hong Leong Bank',
        self::HLBB      => 'Hong Leong Bank',
        self::KFHO_C    => 'KFH',
        self::KFHO      => 'KFH',
        self::MBBE_C    => 'Maybank2E',
        self::MBBE      => 'Maybank2E',
        self::MB2U      => 'Maybank2U',
        self::OCBC_C    => 'OCBC Bank',
        self::OCBC      => 'OCBC Bank',
        self::PBBE_C    => 'Public Bank',
        self::PBBE      => 'Public Bank',
        self::PBBN_C    => 'PB Enterprise',
        self::RHBB_C    => 'RHB Bank',
        self::RHBB      => 'RHB Bank',
        self::SCBL_C    => 'Standard Chartered',
        self::SCBL      => 'Standard Chartered',
        self::UOVB      => 'UOB Bank',
        self::UOVB_C    => 'UOB Regional',
    ];

    // list of test bank
    protected static  $testBankCodeMapping = [
        self::TEST0021      => 'TEST0021',
        self::TEST0022      => 'TEST0022',
        self::TEST0023      => 'TEST0023',
        self::TEST0001      => 'TEST0001',
        self::TEST0002      => 'TEST0002',
        self::TEST0003      => 'TEST0003',
        self::TEST0004      => 'TEST0004',
        self::TEST0005      => 'TEST0005',
    ];

    public static function getSupportedBanks(): array
    {
        return array_values(array_unique(array_merge(self::$b2cBanks, self::$b2bBanks)));
    }

    public static function getB2CBanks(): array
    {
        return self::$b2cBanks;
    }

    public static function getB2BBanks(): array
    {
        return self::$b2bBanks;
    }

    public static function getName($bankCode)
    {
        return self::$names[$bankCode];
    }

    public static function getNames($bankCodes): array
    {
        return array_intersect_key(self::$names, array_flip($bankCodes));
    }

    public static function getDisplayName($bankCode)
    {
        return self::$displayNames[$bankCode];
    }

    public static function getDisplayNames($bankCodes): array
    {
        return array_intersect_key(self::$displayNames, array_flip($bankCodes));
    }

    public static function getFpxBankCode($bankCode)
    {
        $fpxBankCodeMapping = array_merge(self::$fpxB2BBankCodeMapping, self::$fpxB2CBankCodeMapping);

        return $fpxBankCodeMapping[$bankCode];
    }

    // return fpx bank codes respectively to rzp bank code
    public static function getFpxBankCodes($bankCodes): array
    {
        $fpxBankCodeMapping = array_merge(self::$fpxB2BBankCodeMapping, self::$fpxB2CBankCodeMapping);

        return array_intersect_key($fpxBankCodeMapping, array_flip($bankCodes));
    }

}

