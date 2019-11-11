<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class GatewayStatus extends BaseStatus
{
    // ====== Status Codes ======

    const STATUS_CODE_SUCCESS = 'S';
    const STATUS_CODE_FAILURE = 'F';
    const STATUS_CODE_TIMEOUT = 'T';
    const STATUS_CODE_PENDING = 'P';

    const VALID_STATUS_CODES = [
        self::STATUS_CODE_SUCCESS,
        self::STATUS_CODE_FAILURE,
        self::STATUS_CODE_TIMEOUT,
        self::STATUS_CODE_PENDING,
    ];

    const COMPLETED     = '00';
    const COMPLETED2    = '0';

    const MT01 = 'MT01'; const MT02 = 'MT02'; const MT03 = 'MT03'; const MT04 = 'MT04'; const MT05 = 'MT05';
    const MT06 = 'MT06'; const MT07 = 'MT07'; const MT08 = 'MT08'; const MT09 = 'MT09'; const MT10 = 'MT10';
    const MT11 = 'MT11'; const MT12 = 'MT12'; const MT13 = 'MT13'; const MT14 = 'MT14'; const MT15 = 'MT15';
    const MT16 = 'MT16'; const MT17 = 'MT17'; const MT18 = 'MT18'; const MT19 = 'MT19'; const MT20 = 'MT20';
    const MT21 = 'MT21'; const MT22 = 'MT22'; const MT23 = 'MT23'; const MT24 = 'MT24'; const MT25 = 'MT25';
    const MT26 = 'MT26'; const MT27 = 'MT27'; const MT28 = 'MT28'; const MT29 = 'MT29'; const MT30 = 'MT30';
    const MT31 = 'MT31'; const Z9 = 'Z9'; const RM ='RM'; const RN = 'N'; const RZ = 'RZ'; const BR = 'BR';
    const B2 = 'B2'; const SP = 'SP'; const AJ = 'AJ'; const K1 = 'K1'; const ZI = 'ZI'; const Z8 = 'Z8';
    const Z7 = 'Z7'; const Z6 = 'Z6'; const ZM = 'ZM'; const ZD = 'ZD'; const ZR = 'ZR'; const ZS = 'ZS';
    const ZT = 'ZT'; const ZX = 'ZX'; const XD = 'XD'; const XF = 'XF'; const XH = 'XH'; const XJ = 'XJ';
    const XL = 'XL'; const XN = 'XN'; const XP = 'XP'; const XR = 'XR'; const XT = 'XT'; const XV = 'XV';
    const XY = 'XY'; const YA = 'YA'; const YC = 'YC'; const YE = 'YE'; const Z5 = 'Z5'; const ZP = 'ZP';
    const ZY = 'ZY'; const XE = 'XE'; const XG = 'XG'; const XI = 'XI'; const XK = 'XK'; const XM = 'XM';
    const XO = 'XO'; const XQ = 'XQ'; const XS = 'XS'; const XU = 'XU'; const XW = 'XW'; const Y1 = 'Y1';
    const YB = 'YB'; const YD = 'YD'; const YF = 'YF'; const X6 = 'X6'; const X7 = 'X7'; const XB = 'XB';
    const XC = 'XC'; const AM = 'AM'; const B1 = 'B1'; const B3 = 'B3'; const ZA = 'ZA'; const ZH = 'ZH';
    const UX = 'UX'; const ZG = 'ZG'; const ZE = 'ZE'; const ZB = 'ZB'; const YG = 'YG'; const X1 = 'X1';
    const UT = 'UT'; const BT = 'BT'; const RB = 'RB'; const RP = 'RP'; const ERSP32 = '32'; const ERSP21 = '21';
    const U01 = 'U01'; const U02 = 'U02'; const U03 = 'U03'; const U04 = 'U04'; const U05 = 'U05'; const U06 = 'U06';
    const U07 = 'U07'; const U08 = 'U08'; const U09 = 'U09'; const U10 = 'U10'; const U11 = 'U11'; const U12 = 'U12';
    const U13 = 'U13'; const U14 = 'U14'; const U15 = 'U15'; const U16 = 'U16'; const U17 = 'U17'; const U18 = 'U18';
    const U19 = 'U19'; const U20 = 'U20'; const U21 = 'U21'; const U22 = 'U22'; const U23 = 'U23'; const U24 = 'U24';
    const U25 = 'U25'; const U26 = 'U26'; const U27 = 'U27'; const U28 = 'U28'; const U29 = 'U29'; const U30 = 'U30';
    const U31 = 'U31'; const U32 = 'U32'; const U33 = 'U33'; const U34 = 'U34'; const U35 = 'U35'; const U36 = 'U36';
    const U37 = 'U37'; const U38 = 'U38'; const U39 = 'U39'; const U40 = 'U40'; const U41 = 'U41'; const U42 = 'U42';
    const U43 = 'U43'; const U44 = 'U44'; const U45 = 'U45'; const U46 = 'U46'; const U47 = 'U47'; const U48 = 'U48';
    const U49 = 'U49'; const U50 = 'U50'; const U51 = 'U51'; const U52 = 'U52'; const U53 = 'U53'; const U54 = 'U54';
    const U66 = 'U66'; const U67 = 'U67'; const U68 = 'U68'; const U69 = 'U69'; const U70 = 'U70'; const U77 = 'U77';
    const U78 = 'U78'; const U88 = 'U88'; const OC = 'OC'; const OD = 'OD'; const NC = 'NC'; const ND = 'ND'; const DT = 'DT';
    const EXT_RSP9001 = 'EXT_RSP9001'; const EXT_RSP9002 = 'EXT_RSP9002'; const EXT_RSP9003 = 'EXT_RSP9003';
    const EXT_RSP9004 = 'EXT_RSP9004'; const EXT_RSP9005 = 'EXT_RSP9005'; const EXT_RSP9006 = 'EXT_RSP9006';
    const EXT_RSP9007 = 'EXT_RSP9007'; const EXT_RSP9010 = 'EXT_RSP9010'; const EXT_RSP9011 = 'EXT_RSP9011';
    const EXT_RSP9018 = 'EXT_RSP9018'; const EXT_RSP9029 = 'EXT_RSP9029'; const EXT_RSP9031 = 'EXT_RSP9031';
    const EXT_RSP9035 = 'EXT_RSP9035'; const EXT_RSP9037 = 'EXT_RSP9037'; const EXT_RSP9039 = 'EXT_RSP9039';
    const EXT_RSP9042 = 'EXT_RSP9042'; const EXT_RSP9045 = 'EXT_RSP9045'; const EXT_RSP9086 = 'EXT_RSP9086';
    const EXT_RSP9088 = 'EXT_RSP9088'; const ERSP7 = '7'; const ERSP10 = '10'; const ERSP11 = '11'; const ERSP18 = '18';
    const ERSP92 = '92'; const ERSP1001 = '1001'; const ERSP1206 = '1206'; const ERSP1210 = '1210';
    const ERSP1282 = '1282'; const ERSP2075 = '2075'; const ERSP2435 = '2435'; const ERSP2988 = '2988';
    const ERSP3934 = '3934'; const ERSP3403 = '3403'; const ERSP3573 = '3573'; const ERSP2778 = '2778';
    const ERSP2853 = '2853'; const ERSP3611 = '3611'; const ERSP3769 = '3769'; const ERSP3915 = '3915';
    const ERSP4388 = '4388'; const ERSP4470 = '4470'; const ERSP5281 = '5281'; const ERSP8024 = '8024';
    const ERSP8037 = '8037'; const ERSP8080 = '8080'; const ERSP8086 = '8086'; const ERSP8087 = '8087';
    const ERSP8088 = '8088'; const ERSP9007 = '9007'; const ERSP9008 = '9008'; const ERSP9015 = '9015';
    const ERSP9030 = '9030'; const ERSP9093 = '9093'; const ERSP80002 = '80002'; const ERSP80004 = '80004';
    const ERSP80016 = '80016'; const ERSP90152 = '90152'; const ERSP90185 = '90185'; const ERSP90290 = '90290';
    const ERSP90296 = '90296'; const ERSP90188 = '90188'; const ERSP8014 = '8014'; const U71 = 'U71';
    const U74 = 'U74'; const U75 = 'U75'; const U76 = 'U76'; const U95 = 'U95'; const U96 = 'U96';
    const U97 = 'U97'; const RR = 'RR'; const U82 = 'U82'; const U85 = 'U85'; const U87 = 'U87';

    // We get this error code in Verify response. Mostly it should be failed only. Anyway, will be
    // removing this soon. Not going to rely on Verify later except in timeout or pending cases.
    const E99 = '99';

    // pre processing  error codes for transfer request
    const RZP_FTA_REQUEST_INVALID           = 'RZP_FTA_REQUEST_INVALID';
    const RZP_REQUEST_ENCRYPTION_FAILURE    = 'RZP_REQUEST_ENCRYPTION_FAILURE';

    // post processing  error codes for transfer request
    const RZP_DUPLICATE_PAYOUT              = 'RZP_DUPLICATE_PAYOUT';
    const RZP_PAYOUT_TIMED_OUT              = 'RZP_PAYOUT_TIMED_OUT';
    const RZP_PAYOUT_REQUEST_FAILURE        = 'RZP_PAYOUT_REQUEST_FAILURE';
    const RZP_PAYOUT_UNKNOWN_ERROR          = 'RZP_PAYOUT_UNKNOWN_ERROR';
    const RZP_RESPONSE_DECRYPTION_FAILED    = 'RZP_RESPONSE_DECRYPTION_FAILED';

    // Custom codes based on response code BT
    // In case of timeout we have to check time_out_status field to derive the result
    // here we have collectively with BT and time_out_status created a status
    // which can be used to determine the status of transaction
    const BT_TCC      = 'BT_TCC';
    const BT_RRC      = 'BT_RRC';
    const BT_RET      = 'BT_RET';

    // verify related status codes
    const RZP_REF_ID_MISMATCH               = 'RZP_REF_ID_MISMATCH';
    const RZP_AMOUNT_MISMATCH               = 'RZP_AMOUNT_MISMATCH';
    const RZP_PAYOUT_VERIFY_TIMED_OUT       = 'RZP_PAYOUT_VERIFY_TIMED_OUT';
    const RZP_PAYOUT_VERIFY_REQUEST_FAILURE = 'RZP_PAYOUT_VERIFY_REQUEST_FAILURE';

    const FAILURE_CODE_PUBLIC_MAPPING = [
        self::MT01                           => 'Payout failed. Contact support for help.',
        self::MT02                           => 'Payout failed. Contact support for help.',
        self::MT03                           => 'Payout failed. Contact support for help.',
        self::MT04                           => 'Payout failed. Contact support for help.',
        self::MT05                           => 'Payout failed. Contact support for help.',
        self::MT06                           => 'UPI account is closed',
        self::MT07                           => 'UPI account in inactive and cannot receive funds',
        self::MT08                           => 'Invalid UPI Pin',
        self::MT09                           => 'Inactive/Dormant Beneficiary Account',
        self::MT10                           => 'Payout failed. Contact support for help.',
        self::MT11                           => 'Payout failed. Contact support for help.',
        self::MT12                           => 'Payout failed. Contact support for help.',
        self::MT13                           => 'Payout failed. Contact support for help.',
        self::MT14                           => 'Payout failed. Contact support for help.',
        self::MT15                           => 'Invalid beneficiary UPI address/VPA',
        self::MT16                           => 'Payout failed. Contact support for help.',
        self::MT17                           => 'Number of PIN tries exceeded limit',
        self::MT18                           => 'Payout failed. Contact support for help.',
        self::MT19                           => 'Beneficiary Account does not exist',
        self::MT20                           => 'Account not whitelisted',
        self::MT21                           => 'Payout failed. Contact support for help.',
        self::MT22                           => 'Payout failed. Contact support for help.',
        self::MT23                           => 'Partner bank systems are down. Try again later.',
        self::MT24                           => 'Contacts bank systems are down. Try again later.',
        self::MT25                           => 'Payout failed. Contact support for help.',
        self::MT26                           => 'Payout failed. Contact support for help.',
        self::MT27                           => 'Transaction not permitted to beneficiary account',
        self::MT28                           => 'Payout failed. Contact support for help.',
        self::MT29                           => 'Payout failed. Contact support for help.',
        self::MT30                           => 'Beneficiary account is Blocked/Frozen',
        self::MT31                           => 'Remitter account is Blocked/Frozen. Contact support for help.',
        self::Z9                             => 'Payout failed. Reinitiate transfer after 60 min.',
        self::RM                             => 'Invalid UPI PIN (Policy Violation while setting/changing UPI PIN )',
        self::RN                             => 'Payout failed. Contact support for help.',
        self::RZ                             => 'Payout failed. Contact support for help.',
        self::BR                             => 'Payout failed. Contact support for help.',
        self::B2                             => 'Payout failed. Contact support for help.',
        self::SP                             => 'Invalid/Incorrect ATM PIN',
        self::AJ                             => 'Beneficiary has never created/activated an ATM PIN',
        self::K1                             => 'Payout failed. Contact support for help.',
        self::ZI                             => 'Bank declined the transaction based on beneficiary risk score',
        self::Z8                             => 'Payout failed. Reinitiate transfer after 60 min.',
        self::Z7                             => 'Payout failed. Contact support for help.',
        self::Z6                             => 'Number of PIN tries exceeded limit',
        self::ZM                             => 'Invalid UPI PIN',
        self::ZD                             => 'Payout failed. Contact support for help.',
        self::ZR                             => 'Invalid / Incorrect OTP',
        self::ZS                             => 'OTP Expired',
        self::ZT                             => 'OTP Transaction limit exceeded',
        self::ZX                             => 'Payout failed. Contact support for help.',
        self::XD                             => 'Payout failed. Contact support for help.',
        self::XF                             => 'Payout failed. Contact support for help.',
        self::XH                             => 'Payout failed. Contact support for help.',
        self::XJ                             => 'Payout failed. Contact support for help.',
        self::XL                             => 'Expired Card. Contact support for help.',
        self::XN                             => 'Payout failed. Contact support for help.',
        self::XP                             => 'Payout failed. Contact support for help.',
        self::XR                             => 'Payout failed. Contact support for help.',
        self::XT                             => 'Payout failed. Contact support for help.',
        self::XV                             => 'Payout failed. Contact support for help.',
        self::XY                             => 'Partner bank systems are down. Try again later.',
        self::YA                             => 'Payout failed. Contact support for help.',
        self::YC                             => 'Payout failed. Contact support for help.',
        self::YE                             => 'Account Blocked/Frozen. Contact support immediately.',
        self::Z5                             => 'Invalid Beneficiary Credentials.',
        self::ZP                             => 'Payout failed. Contact support for help.',
        self::ZY                             => 'Beneficiary Account is Inactive/Dormant',
        self::XE                             => 'Invalid Amount',
        self::XG                             => 'Payout failed. Contact support for help.',
        self::XI                             => 'Beneficiary Account does not exist',
        self::XK                             => 'Payout failed. Contact support for help.',
        self::XM                             => 'Payout failed. Contact support for help.',
        self::XO                             => 'Payout failed. Contact support for help.',
        self::XQ                             => 'Payout failed. Contact support for help.',
        self::XS                             => 'Payout failed. Contact support for help.',
        self::XU                             => 'Payout failed. Contact support for help.',
        self::XW                             => 'Payout failed due to beneficiary compliance violation',
        self::Y1                             => 'Contacts bank systems are down. Try again later.',
        self::YB                             => 'Payout failed. Contact support for help.',
        self::YD                             => 'Payout rejected by beneficiary bank',
        self::YF                             => 'Beneficiary account is Blocked/Frozen',
        self::X6                             => 'Payout failed. Contact support for help.',
        self::X7                             => 'Payout failed. Contact support for help.',
        self::XB                             => 'Payout failed. Contact support for help.',
        self::XC                             => 'Payout failed. Contact support for help.',
        self::AM                             => 'UPI PIN not set by beneficiary',
        self::B1                             => 'Registered Mobile number linked to beneficiary account has been changed/removed',
        self::B3                             => 'Payout failed. Contact support for help.',
        self::ZA                             => 'Trasaction decilned by customer/beneficiary bank',
        self::ZH                             => 'Invalid VPA/ UPI address',
        self::UX                             => 'Expired VPA/UPI address',
        self::ZG                             => 'Contact has blocked incoming payouts.',
        self::ZE                             => 'Beneficiarys PSP has blocked VPA payout',
        self::ZB                             => 'Invalid beneficiary PSP',
        self::YG                             => 'Payout failed due to beneficiary PSP',
        self::X1                             => 'Beneficiary is facing issues. Reinitiate transfer after 30 min.',
        self::UT                             => 'Timeout error. Contact support for help. Reinitiate transfer after 60 min.',
        self::BT                             => 'Timeout as Beneficiary is not available. Reinitiate transfer after 30 min.',
        self::RB                             => 'Payout reversed due to timeout. Reinitiate transfer after 30 min.',
        self::RP                             => 'Payout failed. Contact support for help.',
        self::ERSP32                         => 'Payout failed. Contact support for help.',
        self::ERSP21                         => 'Payout failed. Contact support for help.',
        self::U01                            => 'Payout failed. Contact support for help.',
        self::U02                            => 'Payout failed. Contact support for help.',
        self::U03                            => 'Payout failed. Contact support for help.',
        self::U04                            => 'Payout failed. Contact support for help.',
        self::U05                            => 'Payout failed. Contact support for help.',
        self::U06                            => 'Payout failed. Contact support for help.',
        self::U07                            => 'Payout failed. Contact support for help.',
        self::U08                            => 'Payout failed. Contact support for help.',
        self::U09                            => 'Payout failed. Contact support for help.',
        self::U10                            => 'Payout failed. Contact support for help.',
        self::U11                            => 'Payout failed. Contact support for help.',
        self::U12                            => 'Amount or currency mismatch',
        self::U13                            => 'Payout failed. Contact support for help.',
        self::U14                            => 'Payout failed. Contact support for help.',
        self::U15                            => 'Payout failed. Contact support for help.',
        self::U16                            => 'Payout failed by bank due to risk checks',
        self::U17                            => 'PSP is not registered. Check VPA for any issue',
        self::U18                            => 'Payout failed. Contact support for help.',
        self::U19                            => 'Payout failed. Contact support for help.',
        self::U20                            => 'Timeout during authorization',
        self::U21                            => 'Payout failed. Contact support for help.',
        self::U22                            => 'Payout failed. Contact support for help.',
        self::U23                            => 'Payout failed. Contact support for help.',
        self::U24                            => 'Payout failed. Contact support for help.',
        self::U25                            => 'Payout failed. Contact support for help.',
        self::U26                            => 'Beneficiary PSP is facing issues',
        self::U27                            => 'No response from Beneficiary PSP ',
        self::U28                            => 'Contacts PSP is facing issues. Try again later.',
        self::U29                            => 'Cannot resolve UPI address',
        self::U30                            => 'Payout failed. Contact support for help.',
        self::U31                            => 'Payout failed. Contact support for help.',
        self::U32                            => 'Payout failed. Contact support for help.',
        self::U33                            => 'Payout failed. Contact support for help.',
        self::U34                            => 'Payout failed. Contact support for help.',
        self::U35                            => 'Payout failed. Contact support for help.',
        self::U36                            => 'Payout failed. Contact support for help.',
        self::U37                            => 'Payout failed. Contact support for help.',
        self::U38                            => 'Payout failed. Contact support for help.',
        self::U39                            => 'Payout failed. Contact support for help.',
        self::U40                            => 'Payout failed. Contact support for help.',
        self::U41                            => 'Payout failed. Contact support for help.',
        self::U42                            => 'Payout failed. Contact support for help.',
        self::U43                            => 'Payout declined by bank',
        self::U44                            => 'Payout failed. Contact support for help.',
        self::U45                            => 'Payout failed. Contact support for help.',
        self::U46                            => 'Payout failed. Contact support for help.',
        self::U47                            => 'Payout failed. Contact support for help.',
        self::U48                            => 'Payout failed. Contact support for help.',
        self::U49                            => 'Payout failed. Contact support for help.',
        self::U50                            => 'Payout failed. Contact support for help.',
        self::U51                            => 'Payout failed. Contact support for help.',
        self::U52                            => 'Payout failed. Issue with beneficiary PSP.',
        self::U53                            => 'Payout failed. Issue with beneficiary PSP.',
        self::U54                            => 'Payout failed. Contact support for help.',
        self::U66                            => 'Failed due to fingerprint mismatch',
        self::U67                            => 'Payout failed due to timeout issue.',
        self::U68                            => 'Payout failed due to timeout issue.',
        self::U69                            => 'Collect request timed out',
        self::U70                            => 'Delay in response',
        self::U77                            => 'Beneficiary is blocked by bank',
        self::U88                            => 'Payout failed. Contact support for help.',
        self::U78                            => 'Beneficiary bank is offline. Reinitiate transfer after 60 min.',
        self::OC                             => 'Payout failed. Contact support for help.',
        self::OD                             => 'Payout failed. Contact support for help.',
        self::NC                             => 'Payout failed. Contact support for help.',
        self::ND                             => 'Payout failed. Contact support for help.',
        self::DT                             => 'Payout failed. Contact support for help.',
        self::E99                            => 'MPIN is not available',
        self::EXT_RSP9001                    => 'Beneficiary account is invalid',
        self::EXT_RSP9002                    => 'Beneficiary account is closed',
        self::EXT_RSP9003                    => 'Beneficiary account is blocked',
        self::EXT_RSP9004                    => 'Beneficiary account cannot be debited. Debit operation not allowed',
        self::EXT_RSP9005                    => 'Beneficiary account cannot be credited. Credit operation not allowed',
        self::EXT_RSP9006                    => 'Beneficiary account is closed',
        self::EXT_RSP9007                    => 'Beneficiary account is dormant',
        self::EXT_RSP9010                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9011                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9018                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9029                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9031                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9035                    => 'Payout failed. Memo pending on beneficiary account',
        self::EXT_RSP9037                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9039                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9042                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9045                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9086                    => 'Payout failed. Contact support for help.',
        self::EXT_RSP9088                    => 'Payout failed. Contact support for help.',
        self::ERSP7                          => 'Payout failed. Contact support for help.',
        self::ERSP10                         => 'Payout failed. Contact support for help.',
        self::ERSP11                         => 'Invalid Account Number',
        self::ERSP18                         => 'Invalid beneficiary bank',
        self::ERSP92                         => 'Payout failed. Contact support for help.',
        self::ERSP1001                       => 'Payout failed. Contact support for help.',
        self::ERSP1206                       => 'Partner bank is facing issue. Reinitiate transfer after 30 min.',
        self::ERSP1210                       => 'Partner bank is facing issue. Reinitiate transfer after 30 min.',
        self::ERSP1282                       => 'Payout failed. Contact support for help.',
        self::ERSP2075                       => 'Partner bank is facing issue. Reinitiate transfer after 30 min.',
        self::ERSP2435                       => 'Payout failed. Contact support for help.',
        self::ERSP2988                       => 'Payout failed. Contact support for help.',
        self::ERSP3934                       => 'Invalid operation. Debit and Credit accounts cannot be same',
        self::ERSP3403                       => 'Partner bank is facing issue. Reinitiate transfer after 30 min.',
        self::ERSP3573                       => 'Payout failed. Contact support for help.',
        self::ERSP2778                       => 'Payout failed. Contact support for help.',
        self::ERSP2853                       => 'Payout failed. Contact support for help.',
        self::ERSP3611                       => 'Payout failed. Contact support for help.',
        self::ERSP3769                       => 'Payout failed. Contact support for help.',
        self::ERSP3915                       => 'Payout failed. Contact support for help.',
        self::ERSP4388                       => 'Payout failed. Contact support for help.',
        self::ERSP4470                       => 'Payout failed. Contact support for help.',
        self::ERSP5281                       => 'Payout failed. Contact support for help.',
        self::ERSP8024                       => 'Payout failed. Contact support for help.',
        self::ERSP8037                       => 'Account cannot be closed as it is linked to Aadhar',
        self::ERSP8080                       => 'Mandate has Expired',
        self::ERSP8086                       => 'Payout failed. Contact support for help.',
        self::ERSP8087                       => 'Payout failed. Contact support for help.',
        self::ERSP8088                       => 'Payout failed. Contact support for help.',
        self::ERSP9007                       => 'Payout failed. Contact support for help.',
        self::ERSP9008                       => 'Payout failed. Contact support for help.',
        self::ERSP9015                       => 'Payout failed. Contact support for help.',
        self::ERSP9030                       => 'Payout failed. Contact support for help.',
        self::ERSP9093                       => 'Payout failed. Contact support for help.',
        self::ERSP80002                      => 'Payout failed. Contact support for help.',
        self::ERSP80004                      => 'Payout failed. Contact support for help.',
        self::ERSP80016                      => 'Payout failed. Contact support for help.',
        self::ERSP90152                      => 'Payout failed. Contact support for help.',
        self::ERSP90185                      => 'Invalid transaction amount',
        self::ERSP90290                      => 'Payout failed. Contact support for help.',
        self::ERSP90296                      => 'Invalid beneficiary account number',
        self::ERSP90188                      => 'Payout failed. Contact support for help.',
        self::ERSP8014                       => 'Payout failed. Reinitiate transfer after 60 min.',
        self::RZP_FTA_REQUEST_INVALID        => 'Payout failed. Contact support for help.',
        self::RZP_REQUEST_ENCRYPTION_FAILURE => 'Payout failed. Contact support for help.',
        self::RZP_PAYOUT_REQUEST_FAILURE     => 'Payout request timed out. Try again later',
        self::RZP_REF_ID_MISMATCH            => 'Payout failed. Contact support for help.',
        self::RZP_AMOUNT_MISMATCH            => 'Payout failed. Contact support for help.',
        self::BT_RRC                         => 'Payout request timed out. Try again later',
        self::BT_RET                         => 'Payout request timeout.',
        self::U71                            => 'Beneficiary could not be credited. IMPS credit not enabled. ',
        self::U74                            => 'Payout failed. Contact support for help.',
        self::U75                            => 'Payout failed. Contact support for help.',
        self::U76                            => 'Mobile banking registration format not supported by issuer bank',
        self::U95                            => 'Beneficiary VPA is disabled',
        self::U96                            => 'Payout failed. Beneficiary account number same as payer',
        self::U97                            => 'No response from PSP',
        self::RR                             => 'Beneficiary bank not available. Reinitiate transfer after 60 min.',
        self::U82                            => 'Payout failed. Contact support for help.',
        self::U85                            => 'Payout failed. Contact support for help.',
        self::U87                            => 'Payout failed. Contact support for help.',
    ];

    const FAILURE_CODE_INTERNAL_MAPPING = [
        self::MT01                              => 'Debit Transaction Failed',
        self::MT02                              => 'Credit Transaction Failed',
        self::MT03                              => 'Insufficient Balance in Account',
        self::MT04                              => 'Transaction Limit Exceeded',
        self::MT05                              => 'Transaction Amount Exceeded',
        self::MT06                              => 'Closed Account',
        self::MT07                              => 'Inactive/Dormant account (Remitter)',
        self::MT08                              => 'Invalid UPI PIN entered',
        self::MT09                              => 'Inactive/Dormant account (Beneficiary)',
        self::MT10                              => 'No Credit Account',
        self::MT11                              => 'Credit decline reversal',
        self::MT12                              => 'Partial Decline',
        self::MT13                              => 'Invalid amount (Remitter)',
        self::MT14                              => 'Incorrect/Invalid Payer Virtual Address',
        self::MT15                              => 'Incorrect/Invalid Payee Virtual Address',
        self::MT16                              => 'Collect rejected successfully',
        self::MT17                              => 'Number of PIN tries exceeded.',
        self::MT18                              => 'Account does not exist (Remitter)',
        self::MT19                              => 'Account does not exist (Beneficiary)',
        self::MT20                              => 'Account not whitelisted',
        self::MT21                              => 'Cutoff is in process(Remitter)',
        self::MT22                              => 'Cutoff is in process(Beneficiary)',
        self::MT23                              => 'Remitter CBS offline',
        self::MT24                              => "Beneficiary CBS offline",
        self::MT25                              => 'Invalid transaction (Remitter)',
        self::MT26                              => 'Invalid transaction (Beneficiary)',
        self::MT27                              => 'Transaction not permitted to account',
        self::MT28                              => 'Requested function not supported (Remitter)',
        self::MT29                              => 'Requested function not supported (Beneficiary)',
        self::MT30                              => "Beneficiary account blocked / Frozen",
        self::MT31                              => 'Remitter account blocked / Frozen',
        self::Z9                                => 'INSUFFICIENT FUNDS IN CUSTOMER (REMITTER) ACCOUNT',
        self::RM                                => 'Invalid UPI PIN (Violation of policies while setting/changing UPI PIN )',
        self::RN                                => 'Registration is temporary blocked due to maximum no of attempts exceeded',
        self::RZ                                => 'Account is already registered with MBEBA flag as \'Y\'',
        self::BR                                => 'Mobile number registered with multiple customer IDs',
        self::B2                                => 'Account linked with multiple names',
        self::SP                                => 'Invalid/Incorrect ATM PIN',
        self::AJ                                => 'Customer has never created/activated an ATM PIN',
        self::K1                                => 'SUSPECTED FRAUD, DECLINE / TRANSACTIONS DECLINED BASED ON RISK SCORE BY REMITTER',
        self::ZI                                => "SUSPECTED FRAUD, DECLINE / TRANSACTIONS DECLINED BASED ON RISK SCORE BY BENEFICIARY",
        self::Z8                                => 'PER TRANSACTION LIMIT EXCEEDED AS SET BY REMITTING MEMBER',
        self::Z7                                => 'TRANSACTION FREQUENCY LIMIT EXCEEDED AS SET BY REMITTING MEMBER',
        self::Z6                                => 'NUMBER OF PIN TRIES EXCEED',
        self::ZM                                => 'INVALID UPI PIN',
        self::ZD                                => 'VALIDATION ERROR',
        self::ZR                                => 'INVALID / INCORRECT OTP',
        self::ZS                                => 'OTP EXPIRED',
        self::ZT                                => 'OTP TRANSACTION LIMIT EXCEEDED',
        self::ZX                                => 'INACTIVE OR DORMANT ACCOUNT (REMITTER)',
        self::XD                                => 'INVALID AMOUNT (REMITTER)',
        self::XF                                => 'FORMAT ERROR (INVALID FORMAT) (REMITTER)',
        self::XH                                => 'ACCOUNT DOES NOT EXIST (REMITTER)',
        self::XJ                                => 'REQUESTED FUNCTION NOT SUPPORTED',
        self::XL                                => 'EXPIRED CARD, DECLINE (REMITTER)',
        self::XN                                => 'NO CARD RECORD (REMITTER)',
        self::XP                                => 'TRANSACTION NOT PERMITTED TO CARDHOLDER (REMITTER)',
        self::XR                                => 'RESTRICTED CARD, DECLINE (REMITTER)',
        self::XT                                => 'CUT-OFF IS IN PROCESS (REMITTER)',
        self::XV                                => 'TRANSACTION CANNOT BE COMPLETED. COMPLIANCE VIOLATION(REMITTER)',
        self::XY                                => 'REMITTER CBS OFFLINE',
        self::YA                                => 'LOST OR STOLEN CARD (REMITTER)',
        self::YC                                => 'DO NOT HONOUR (REMITTER)',
        self::YE                                => 'REMITTING ACCOUNT BLOCKED/FROZEN',
        self::Z5                                => 'INVALID BENEFICIARY CREDENTIALS',
        self::ZP                                => 'BANKS AS BENEFICIARY NOT LIVE ON PARTICULAR TXN TYPE',
        self::ZY                                => 'INACTIVE OR DORMANT ACCOUNT (BENEFICIARY)',
        self::XE                                => 'INVALID AMOUNT (BENEFICIARY)',
        self::XG                                => 'FORMAT ERROR (INVALID FORMAT) (BENEFICIARY)',
        self::XI                                => 'ACCOUNT DOES NOT EXIST (BENEFICIARY)',
        self::XK                                => 'REQUESTED FUNCTION NOT SUPPORTED',
        self::XM                                => 'EXPIRED CARD, DECLINE (BENEFICIARY)',
        self::XO                                => 'NO CARD RECORD (BENEFICIARY)',
        self::XQ                                => 'TRANSACTION NOT PERMITTED TO CARDHOLDER (BENEFICIARY)',
        self::XS                                => 'RESTRICTED CARD, DECLINE (BENEFICIARY)',
        self::XU                                => 'CUT-OFF IS IN PROCESS (BENEFICIARY)',
        self::XW                                => 'TRANSACTION CANNOT BE COMPLETED. COMPLIANCE VIOLATION(BENEFICIARY)',
        self::Y1                                => "BENEFICIARY CBS OFFLINE",
        self::YB                                => 'LOST OR STOLEN CARD (BENEFICIARY)',
        self::YD                                => "DO NOT HONOUR (BENEFICIARY)",
        self::YF                                => 'BENEFICIARY ACCOUNT BLOCKED/FROZEN',
        self::X6                                => 'INVALID MERCHANT (ACQURIER)',
        self::X7                                => 'MERCHANT not reachable (ACQURIER)',
        self::XB                                => 'INVALID TRANSACTION OR IF MEMBER IS NOT ABLE TO FIND ANY APROPRIATE RESPONSE CODE (REMITTER)',
        self::XC                                => 'INVALID TRANSACTION OR IF MEMBER IS NOT ABLE TO FIND ANY ',
        self::AM                                => 'UPI PIN not set by customer',
        self::B1                                => 'Registered Mobile number linked to the account has been changed/removed',
        self::B3                                => 'Transaction not permitted to the account',
        self::ZA                                => 'TRANSACTION DECLINED BY CUSTOMER',
        self::ZH                                => 'INVALID VIRTUAL ADDRESS',
        self::UX                                => 'EXPIRED VIRTUAL ADDRESS',
        self::ZG                                => 'VPA RESTRICTED BY CUSTOMER',
        self::ZE                                => "TRANSACTION NOT PERMITTED TO VPA by the PSP",
        self::ZB                                => 'INVALID MERCHANT (PAYEE PSP)',
        self::YG                                => 'MERCHANT ERROR (PAYEE PSP)',
        self::X1                                => "RESPONSE NOT RECEIVED WITHIN TAT AS SET BY PAYEE",
        self::UT                                => 'REMITTER/ISSUER UNAVAILABLE (TIMEOUT)',
        self::BT                                => 'ACQUIRER/BENEFICIARY UNAVAILABLE(TIMEOUT)',
        self::RB                                => 'CREDIT REVERSAL TIMEOUT(REVERSAL)',
        self::RP                                => 'PARTIAL DEBIT REVERSAL TIMEOUT',
        self::ERSP32                            => 'PARTIAL REVERSAL',
        self::ERSP21                            => 'NO ACTION TAKEN (FULL REVERSAL)',
        self::U01                               => 'The request is duplicate',
        self::U02                               => 'Amount CAP is exceeded',
        self::U03                               => 'Net debit CAP is exceeded',
        self::U04                               => 'Request is not found',
        self::U05                               => 'Formation is not proper',
        self::U06                               => 'Transaction ID is mismatched',
        self::U07                               => 'Validation error',
        self::U08                               => 'System exception',
        self::U09                               => 'ReqAuth Time out for PAY',
        self::U10                               => 'Illegal operation',
        self::U11                               => 'Credentials is not present',
        self::U12                               => 'Amount or currency mismatch',
        self::U13                               => 'External error',
        self::U14                               => 'Encryption error',
        self::U15                               => 'Checksum failed',
        self::U16                               => 'Risk threshold exceeded',
        self::U17                               => 'PSP is not registered',
        self::U18                               => 'Request authorization acknowledgement is not received',
        self::U19                               => 'Request authorization is declined',
        self::U20                               => 'Request authorization timeout',
        self::U21                               => 'Request authorization is not found',
        self::U22                               => 'CM request is declined',
        self::U23                               => 'CM request timeout',
        self::U24                               => 'CM request acknowledgement is not received',
        self::U25                               => 'CM URL is not found',
        self::U26                               => 'PSP request credit pay acknowledgement is not received',
        self::U27                               => "No response from PSP",
        self::U28                               => "PSP not available",
        self::U29                               => 'Address resolution is failed',
        self::U30                               => 'Debit has been failed',
        self::U31                               => 'Credit has been failed',
        self::U32                               => 'Credit revert has been failed',
        self::U33                               => 'Debit revert has been failed',
        self::U34                               => 'Reverted',
        self::U35                               => 'Response is already been received',
        self::U36                               => 'Request is already been sent',
        self::U37                               => 'Reversal has been sent',
        self::U38                               => 'Response is already been sent',
        self::U39                               => 'Transaction is already been failed',
        self::U40                               => 'IMPS processing failed in UPI',
        self::U41                               => 'IMPS is signed off',
        self::U42                               => 'IMPS transaction is already been processed',
        self::U43                               => 'IMPS is declined',
        self::U44                               => 'Form has been signed off',
        self::U45                               => 'Form processing has been failed in UPI',
        self::U46                               => 'Request credit is not found',
        self::U47                               => 'Request debit is not found',
        self::U48                               => 'Transaction id not present',
        self::U49                               => 'Request message id is not present',
        self::U50                               => 'IFSC is not present',
        self::U51                               => 'Request refund is not found',
        self::U52                               => 'PSP orgId not found',
        self::U53                               => 'PSP Request Pay Debit Acknowledgement not received',
        self::U54                               => 'Transaction Id or Amount in credential block does not match with that in ReqPay',
        self::U66                               => 'Device Fingerprint mismatch',
        self::U67                               => 'Debit Time Out',
        self::U68                               => 'Credit Time Out',
        self::U69                               => 'Collect Expired',
        self::U70                               => 'Received Late Response',
        self::U77                               => 'Merchant blocked',
        self::U78                               => "Beneficiary bank offline",
        self::U88                               => "Connection timeout in reqpay credit",
        self::OC                                => 'Original Credit Not Found',
        self::OD                                => 'Original Debit Not Found',
        self::NC                                => 'Credit Not Done',
        self::ND                                => 'Debit Not Done',
        self::DT                                => 'Duplicate request sent',
        self::E99                               => 'MPIN is pending. Can be marked as failed. Check first.',
        self::EXT_RSP9001                       => 'Account does not exist.',
        self::EXT_RSP9002                       => 'Account Closed.',
        self::EXT_RSP9003                       => 'Account Blocked.',
        self::EXT_RSP9004                       => 'No debits allowed on Account.',
        self::EXT_RSP9005                       => 'No credits allowed on Account.',
        self::EXT_RSP9006                       => 'Account Closed Today.',
        self::EXT_RSP9007                       => 'Account is Dormant.',
        self::EXT_RSP9010                       => 'Account details have been changed since last request. Please reinitiate',
        self::EXT_RSP9011                       => 'There is a memo present on the Debit account.',
        self::EXT_RSP9018                       => 'Hold Funds Present - Refer to Drawer ( Account would Overdraw )',
        self::EXT_RSP9029                       => 'Internal OLTP Error.',
        self::EXT_RSP9031                       => 'Insufficient funds in the debit account.',
        self::EXT_RSP9035                       => 'There is a memo present on the Credit account.',
        self::EXT_RSP9037                       => 'Transaction Amt is exceeding the limit Amt. Account is going to Overline.',
        self::EXT_RSP9039                       => 'All Installments have been paid for the account/Value date beyond maturity date',
        self::EXT_RSP9042                       => 'Hold Funds Present - Account is going to Overline.',
        self::EXT_RSP9045                       => 'Transaction Amt is exceeding the limit Amt. Account is going to Overline.',
        self::EXT_RSP9086                       => 'Insufficient Balance.',
        self::EXT_RSP9088                       => 'Account has Credit Override status.',
        self::ERSP7                             => 'No record in Endpoint Calender',
        self::ERSP10                            => 'No Status Change Of Customer',
        self::ERSP11                            => 'Invalid Account No',
        self::ERSP18                            => 'Invalid destination bank',
        self::ERSP92                            => 'Invalid LO code',
        self::ERSP1001                          => 'Error {0} {1} {2}',
        self::ERSP1206                          => 'Fatal Error has occurred.Please Exit and Contact System Administrator {0} {1} {2}',
        self::ERSP1210                          => 'Database Error : {0} {1} {2}',
        self::ERSP1282                          => 'Duplicate {0} {1}',
        self::ERSP2075                          => 'A database error occurred during the execution of a stored procedure',
        self::ERSP2435                          => 'Invalid Input {0} {1} {2}.',
        self::ERSP2988                          => 'Invalid Account Status',
        self::ERSP3934                          => 'Debit and Credit accounts cannot be same',
        self::ERSP3403                          => 'Called function has had a Fatal Error {1} {2}',
        self::ERSP3573                          => 'Invalid input {0} {1} {2}',
        self::ERSP2778                          => 'Account not found',
        self::ERSP2853                          => 'Account not found {1} {2}',
        self::ERSP3611                          => 'Invalid transaction',
        self::ERSP3769                          => 'Invalid Account Number',
        self::ERSP3915                          => 'Non-existent reference transaction number',
        self::ERSP4388                          => 'No Rows Found',
        self::ERSP4470                          => 'Batch number not found',
        self::ERSP5281                          => 'Invalid Product Type',
        self::ERSP8024                          => 'card number in use',
        self::ERSP8037                          => 'Account is linked to Aadhar no, cannot close the account.',
        self::ERSP8080                          => 'Mandate has Expired',
        self::ERSP8086                          => 'Cutoff start time not in range defined',
        self::ERSP8087                          => 'Could not save payment data',
        self::ERSP8088                          => 'Float details could not be resolved.',
        self::ERSP9007                          => 'Disbursement date cannot be less than CASA a/c opening date..',
        self::ERSP9008                          => 'Limit attached to the account is frozen.Cannot Modify Account Status.',
        self::ERSP9015                          => 'Value date should be greater than process date',
        self::ERSP9030                          => 'Maximum limit of Date Fields is over',
        self::ERSP9093                          => 'FROM and TO account products are marked for IB transfer block.',
        self::ERSP80002                         => 'Error {0} cannot be null or blank',
        self::ERSP80004                         => 'Error {0} Invalid field length.',
        self::ERSP80016                         => 'Transaction found with this  external reference number {0}',
        self::ERSP90152                         => 'Invalid FromAccountID',
        self::ERSP90185                         => 'Transaction Amount is invalid.',
        self::ERSP90290                         => 'Funds Transfer Not Allowed from NRE product.',
        self::ERSP90296                         => 'To Account Number is Invalid',
        self::ERSP90188                         => 'Voucher entry not allowed for this GL account.',
        self::ERSP8014                          => 'Failed to debit from remitter’s account',
        self::RZP_FTA_REQUEST_INVALID           => 'RZP: payout fta request is invalid',
        self::RZP_REQUEST_ENCRYPTION_FAILURE    => 'RZP: request encryption failure',
        self::RZP_PAYOUT_REQUEST_FAILURE        => 'RZP: payout request failed',
        self::RZP_REF_ID_MISMATCH               => 'RZP: Validation error, ref id mismatch',
        self::RZP_AMOUNT_MISMATCH               => 'RZP: amount mismatch',
        self::BT_RRC                            => 'Return has been initiated by Beneficiary Bank',
        self::BT_RET                            => 'Return has been posted in the remitter’s account',
        self::U71                               => 'MERCHANT CREDIT NOT SUPPORTED IN IMPS',
        self::U74                               => 'PAYER ACCOUNT MISMATCH',
        self::U75                               => 'PAYEE ACCOUNT MISMATCH',
        self::U76                               => 'MOBILE BANKING REGISTRATION FORMAT NOT SUPPORTED BY THE ISSUER BANK',
        self::U95                               => 'PAYEE VPA AADHAAR OR IIN VPA IS DISABLED',
        self::U96                               => "PAYER AND PAYEE IFSC/ACNUM CAN'T BE SAME",
        self::U97                               => 'PSP REQUEST META ACKNOWLEDGEMENT NOT RECEIVED',
        self::RR                                => 'DEBIT REVERSAL TIMEOUT(REVERSAL)',
        self::U82                               => 'READ TIMEOUT IN REQPAY CREDIT',
        self::U85                               => 'CONNECTION TIMEOUT IN REQPAY DEBIT',
        self::U87                               => 'READ TIMEOUT IN REQPAY DEBIT',
    ];

    public static function getSuccessfulStatus(): array
    {
        return [
            self::STATUS_CODE_SUCCESS => [],
            self::STATUS_CODE_TIMEOUT => [
                self::BT_TCC
            ],
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::STATUS_CODE_FAILURE => [],
            self::STATUS_CODE_TIMEOUT => [
                self::BT_RRC,
                self::BT_RET,
            ],
        ];
    }

    public static function getCriticalErrorStatus(): array
    {
       return [
           self::STATUS_CODE_FAILURE => [
               self::RZP_FTA_REQUEST_INVALID,
               self::RZP_REQUEST_ENCRYPTION_FAILURE,
               self::RZP_RESPONSE_DECRYPTION_FAILED,
               self::RZP_REF_ID_MISMATCH,
               self::RZP_AMOUNT_MISMATCH,
               self::U14,
               self::U15,
               self::U77,
               self::U05,
               self::U02,
               self::U03,
               self::U07,
               self::U10,
               self::U11,
               self::U12,
               self::XK,
               self::E99,
           ],
           self::STATUS_CODE_TIMEOUT => [
               self::RZP_PAYOUT_VERIFY_TIMED_OUT,
               self::RZP_PAYOUT_VERIFY_REQUEST_FAILURE,
           ],
           self::STATUS_CODE_PENDING => [
               self::RZP_PAYOUT_UNKNOWN_ERROR,
           ],
       ];
    }

    public static function getCriticalErrorRemarks(): array
    {
        // Will use critical error status instead

        return [];
    }

    public static function getMerchantFailures(): array
    {
        return [];
    }

    public static function isCriticalStatus($bankStatusCode, $bankResponseCode): bool
    {
        $statusCodes = static::getCriticalErrorStatus();

        $isCritical = (in_array($bankStatusCode, $statusCodes, true) === true);

        if ($isCritical === false)
        {
            if (is_int($bankStatusCode) === true)
            {
                $bankStatusCode = 'ERSP' . $bankStatusCode;
            }

            // If the status code is not present in the constant list then consider it as critical
            $isCritical = (defined('static::' . strtoupper($bankStatusCode)) === false);
        }

        return $isCritical;
    }

    public static function getPublicFailureReason($bankStatusCode, $bankResponseCode = null)
    {
        $successfulStatus = self::getSuccessfulStatus();

        $isSuccessful = self::inStatus($successfulStatus, $bankStatusCode, $bankResponseCode);

        if ($isSuccessful === true)
        {
            return null;
        }
        else if (in_array($bankResponseCode, array_keys(self::FAILURE_CODE_PUBLIC_MAPPING), true) === true)
        {
            return self::FAILURE_CODE_PUBLIC_MAPPING[$bankResponseCode] ?? 'Payout failed. Contact support for help.';
        }

        return 'Transfer not completed. Contact support for help.';
    }

    public static function getUsableCode($responseCode, $errorCode, $responseErrorCode)
    {
        if ((strtolower($responseCode) !== 'na') and
            (array_key_exists($responseCode, self::FAILURE_CODE_PUBLIC_MAPPING) === true))
        {
            return $responseCode;
        }

        if ((strtolower($errorCode) !== 'na') and
            (array_key_exists($errorCode, self::FAILURE_CODE_PUBLIC_MAPPING) === true))
        {
            return $errorCode;
        }

        if ((strtolower($responseErrorCode) !== 'na') and
            (array_key_exists($responseErrorCode, self::FAILURE_CODE_PUBLIC_MAPPING) === true))
        {
            return $responseErrorCode;
        }

        return self::getValidCode($responseCode, $errorCode, $responseErrorCode);
    }

    public static function getValidCode($responseCode, $errorCode, $responseErrorCode)
    {
        if ((empty($responseCode) === false) and
            (strtolower($responseCode) !== 'na'))
        {
            return $responseCode;
        }

        if ((empty($errorCode) === false) and
            (strtolower($errorCode) !== 'na'))
        {
            return $errorCode;
        }

        if ((empty($responseErrorCode) === false) and
            (strtolower($responseErrorCode) !== 'na'))
        {
            return $responseErrorCode;
        }

        return $responseCode;
    }
}
