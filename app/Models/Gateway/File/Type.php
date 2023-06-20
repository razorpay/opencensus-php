<?php

namespace RZP\Models\Gateway\File;

class Type
{
    const EMI               = 'emi';
    const CLAIM             = 'claim';
    const REFUND            = 'refund';
    const COMBINED          = 'combined';
    const EMANDATE_REGISTER = 'emandate_register';
    const EMANDATE_DEBIT    = 'emandate_debit';
    const EMANDATE_CANCEL   = 'emandate_cancel';
    const NACH_DEBIT        = 'nach_debit';
    const NACH_REGISTER     = 'nach_register';
    const NACH_CANCEL       = 'nach_cancel';
    const REFUND_FAILED     = 'refund_failed';
    const PARESDATA         = 'paresdata';
    const CAPTURE           = 'capture';
    const CARDSETTLEMENT    = 'cardsettlement';

    // Sub types for gateway_file entity
    const TPV           = 'tpv';
    const NON_TPV       = 'non_tpv';
    const CORPORATE     = 'corporate';
    const NON_CORPORATE = 'non_corporate';

    const TARGET_SUBTYPES = [
        self::COMBINED => [
            Constants::KOTAK => [self::TPV, self::NON_TPV],
            Constants::AXIS  => [self::CORPORATE, self::NON_CORPORATE],
        ]
    ];

    public static function isValidType(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function isValidSubTypeForTargetAndType(
        string $target,
        string $type,
        string $subType = null): bool
    {
        if(Constants::PAPER_NACH_CITI_V2 === $target or
            Constants::COMBINED_NACH_CITI_EARLY_DEBIT_V2 === $target or
            Constants::YESB === $target or
            Constants::YESB_EARLY_DEBIT === $target)
        {
            return true;
        }

        if ((isset(self::TARGET_SUBTYPES[$type]) === true) and
            (isset(self::TARGET_SUBTYPES[$type][$target]) === true))
        {
            $validSubTypesForTarget = self::TARGET_SUBTYPES[$type][$target];

            return (in_array($subType, $validSubTypesForTarget, true) === true);
        }

        return (empty($subType) === true) ? true : false;
    }
}
