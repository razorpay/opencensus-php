<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

class Category
{
    const AMC_BANK_ACCOUNT ='amc_bank_account';

    public static function exists(string $category)
    {
        $class      = new \ReflectionClass(__CLASS__);

        $validTypes = $class->getConstants();

        return in_array($category, $validTypes, true);
    }
}
