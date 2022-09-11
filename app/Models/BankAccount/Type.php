<?php

namespace RZP\Models\BankAccount;

use RZP\Exception;

class Type
{
    const ORDER           = 'order';
    const REFUND          = 'refund';
    const MERCHANT        = 'merchant';
    const CUSTOMER        = 'customer';
    const VIRTUAL_ACCOUNT = 'virtual_account';
    const CONTACT         = 'contact';
    const QR_CODE         = 'qr_code';
    const ORG_SETTLEMENT  = 'org_settlement';
    const ORG             = 'org';

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid bank account owner: ' . $type);
        }
    }

    public static function getEntityClass($type)
    {
        $entity = 'RZP\Models\\' . studly_case($type) . '\Entity';

        return $entity;
    }

    public static function getBeneficiaryRegistrationTypes()
    {
        return [
            self::MERCHANT,
            self::CONTACT,
        ];
    }

    public static function isValidBeneficiaryRegistrationType(string $type = null): bool
    {
        return (in_array($type, self::getBeneficiaryRegistrationTypes(), true) === true);
    }
}
