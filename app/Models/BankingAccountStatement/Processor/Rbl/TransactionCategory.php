<?php

namespace RZP\Models\BankingAccountStatement\Processor\Rbl;

use RZP\Models\BankingAccountStatement\Category;

class TransactionCategory
{
    const TCI = 'TCI';
    const TBI = 'TBI';
    const CNP = 'CNP';
    const CNR = 'CNR';
    const CRI = 'CRI';
    const LI  = 'LI';
    const CPI = 'CPI';
    const TEI = 'TEI';
    const LO  = 'LO';

    protected static $RBLInternalCategoryMap = [
        self::TCI => Category::CUSTOMER_INITIATED,
        self::TBI => Category::BANK_INITIATED,
        self::CNP => Category::CASH_WITHDRAWAL,
        self::CNR => Category::CASH_DEPOSIT,
        // TODO: Need to confirm the below codes
        self::CRI => Category::OTHERS,
        self::LI  => Category::OTHERS,
        self::CPI => Category::OTHERS,
        self::TEI => Category::OTHERS,
        self::LO  => Category::OTHERS,
    ];

    public static function getInternalCategory($rblCategory)
    {
        return self::$RBLInternalCategoryMap[$rblCategory] ?? Category::OTHERS;
    }
}
