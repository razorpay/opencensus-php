<?php

namespace RZP\Models\Bank;

use Illuminate\Support\Str;
use Razorpay\IFSC\Bank as BaseBank;
use RZP\Models\Payment\Processor\Netbanking;

class IFSC extends BaseBank
{
    // Custom defined for DC EMIs
    const HDFC_DC = 'HDFC_DC';
    const UTIB_DC = 'UTIB_DC';

    public static function exists($code)
    {
        return ((defined(get_class() . '::' . $code)) or
                (in_array($code, Netbanking::$inconsistentIfsc, true) === true));
    }

    /**
     * Check if an issuer is a custom defined debit card (_DC) issuer as they
     * are hacks for differentiating between debit & credit card EMI's.
     *
     * @param string|null $issuer
     *
     * @return bool
     */
    public static function isDebitCardIssuer(?string $issuer): bool
    {
        return Str::endsWith((string) $issuer, '_DC');
    }

    /**
     * Get issuing bank for issuer.
     * Issuer is the same as issuing bank in all cases except for custom debit
     * card issuers. Debit card issuers have '_DC' in the end & they cannot be
     * stored in the DB as they are 7 characters instead of the required 4 chars.
     *
     * @param string|null $issuer
     *
     * @return string|null
     */
    public static function getIssuingBank(?string $issuer): ?string
    {
        if ($issuer === self::HDFC_DC) {
            // HDFC_DC is a hack to differentiate between HDFC credit & debit card EMI plans
            return self::HDFC;
        }

        if ($issuer === self::UTIB_DC) {
            // UTIB_DC is a hack to differentiate between AXIS credit & debit card EMI plans
            return self::UTIB;
        }

        return $issuer;
    }
}
