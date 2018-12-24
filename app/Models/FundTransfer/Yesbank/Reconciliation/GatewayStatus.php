<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class GatewayStatus extends BaseStatus
{
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
    const UT = 'UT'; const BT = 'BT'; const RB = 'RB'; const RP = 'RP'; const E32 = '32'; const E21 = '21';
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
    const U78 = 'U78'; const OC = 'OC'; const OD = 'OD'; const DT = 'DT';

    // pre processing  error codes for transfer request
    const RZP_FTA_REQUEST_INVALID           = 'RZP_FTA_REQUEST_INVALID';
    const RZP_REQUEST_ENCRYPTION_FAILURE    = 'RZP_REQUEST_ENCRYPTION_FAILURE';

    // post processing  error codes for transfer request
    const RZP_DUPLICATE_PAYOUT              = 'RZP_DUPLICATE_PAYOUT';
    const RZP_PAYOUT_TIMED_OUT              = 'RZP_PAYOUT_TIMED_OUT';
    const RZP_PAYOUT_UNKNOWN_ERROR          = 'RZP_PAYOUT_UNKNOWN_ERROR';
    const RZP_RESPONSE_DECRYPTION_FAILED    = 'RZP_RESPONSE_DECRYPTION_FAILED';

    // verify related status codes
    const RZP_REF_ID_MISMATCH           = 'RZP_REF_ID_MISMATCH';
    const RZP_AMOUNT_MISMATCH           = 'RZP_AMOUNT_MISMATCH';
    const RZP_PAYOUT_VERIFY_TIMED_OUT   = 'RZP_PAYOUT_VERIFY_TIMED_OUT';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::COMPLETED,
            self::COMPLETED2,
        ];
    }

    public static function getFailureStatus($bankStatusCode = null): array
    {
         return [
             self::MT01, self::MT02, self::MT03, self::MT04, self::MT05, self::MT06, self::MT07, self::MT08,
             self::MT09, self::MT10, self::MT11, self::MT12, self::MT13, self::MT14, self::MT15, self::MT16,
             self::MT17, self::MT18, self::MT19, self::MT20, self::MT21, self::MT22, self::MT23, self::MT24,
             self::MT25, self::MT26, self::MT27, self::MT28, self::MT29, self::MT30, self::MT31, self::Z9,
             self::RM, self::RN, self::RZ, self::BR, self::B2, self::SP, self::AJ, self::K1, self::ZI, self::Z8,
             self::Z7, self::Z6, self::ZM, self::ZD, self::ZR, self::ZS, self::ZT, self::ZX, self::XD, self::XF,
             self::XH, self::XJ, self::XL, self::XN, self::XP, self::XR, self::XT, self::XV, self::XY, self::YA,
             self::YC, self::YE, self::Z5, self::ZP, self::ZY, self::XE, self::XG, self::XI, self::XK, self::XM,
             self::XO, self::XQ, self::XS, self::XU, self::XW, self::Y1, self::YB, self::YD, self::YF, self::X6,
             self::X7, self::XB, self::XC, self::AM, self::B1, self::B3, self::ZA, self::ZH, self::UX, self::ZG,
             self::ZE, self::ZB, self::YG, self::X1, self::UT, self::BT, self::RB, self::RP, self::E32, self::E21,
             self::U01, self::U02, self::U03, self::U04, self::U05, self::U06, self::U07, self::U08, self::U09,
             self::U10, self::U11, self::U12, self::U13, self::U14, self::U15, self::U16, self::U17, self::U18,
             self::U19, self::U20, self::U21, self::U22, self::U23, self::U24, self::U25, self::U26, self::U27,
             self::U28, self::U29, self::U30, self::U31, self::U32, self::U33, self::U34, self::U35, self::U36,
             self::U37, self::U38, self::U39, self::U40, self::U41, self::U42, self::U43, self::U44, self::U45,
             self::U46, self::U47, self::U48, self::U49, self::U50, self::U51, self::U52, self::U53, self::U54,
             self::U66, self::U67, self::U68, self::U69, self::U70, self::U77, self::U78, self::OC, self::OD, self::DT,
             self::RZP_FTA_REQUEST_INVALID, self::RZP_REQUEST_ENCRYPTION_FAILURE, self::RZP_DUPLICATE_PAYOUT,
             self::RZP_RESPONSE_DECRYPTION_FAILED, self::RZP_REF_ID_MISMATCH, self::RZP_AMOUNT_MISMATCH,
         ];
    }

    public static function getCriticalErrorStatus(): array
    {
       return [
           self::RZP_DUPLICATE_PAYOUT,
           self::RZP_FTA_REQUEST_INVALID,
           self::RZP_REQUEST_ENCRYPTION_FAILURE,
           self::RZP_RESPONSE_DECRYPTION_FAILED,
           self::RZP_REF_ID_MISMATCH,
           self::RZP_AMOUNT_MISMATCH,
           self::RZP_PAYOUT_UNKNOWN_ERROR,
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
           self::DT,
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
}
