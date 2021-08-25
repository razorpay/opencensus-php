<?php

namespace RZP\Models\User;


/**
 * Class AxisUserRole
 *
 * Static and dynamic user roles on the axis banking product (RazorpayX)
 *
 * @package RZP\Models\User
 */
class AxisUserRole
{
    const AUTHORISED_SIGNATORY = 'authorised_signatory';
    const CC_ADMIN             = 'cc_admin';
    const VIEWER               = 'view_only';
    const MAKER                = 'maker';
    const MAKER_ADMIN          = 'maker_admin';

    const CHECKER_L1 = 'checker_l1';
    const CHECKER_L2 = 'checker_l2';
    const CHECKER_L3 = 'checker_l3';

    protected static $defaultRoles = [
        self::AUTHORISED_SIGNATORY,
        self::CC_ADMIN,
        self::VIEWER,
    ];

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public static function getDefaultRoles(): array
    {
        return self::$defaultRoles;
    }
}
