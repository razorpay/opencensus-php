<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

class Subcategory
{
    const DISPUTE_A_PAYMENT = 'dispute_a_payment';
    const REPORT_FRAUD    = 'report_fraud';

    const DISPUTE_A_PAYMENT_FD = 'Dispute a payment';
    const REPORT_FRAUD_FD      = 'Report fraud';

    private static $validSubcategories = [
        self::DISPUTE_A_PAYMENT,
        self::REPORT_FRAUD,
    ];

    public static function isValidSubcategory(string $subcategory): bool
    {
        return in_array($subcategory, self::$validSubcategories, true) === true;
    }
}
