<?php

namespace RZP\Models\User;

use RZP\Models\Feature;
use RZP\Models\Merchant;

/**
 * Class BankingRole
 *
 * Static and dynamic user roles on the banking product (RazorpayX)
 *
 * @package RZP\Models\User
 */
class BankingRole
{
    // Static Roles
    const OWNER = Role::OWNER;
    const ADMIN = Role::ADMIN;

    //
    // Dynamic Roles:
    // These roles are linked to workflows on the banking product, used by
    // heimdall workflows and are hence also persisted to the `roles` table.
    //
    const FINANCE_L1 = 'finance_l1';
    const FINANCE_L2 = 'finance_l2';
    const FINANCE_L3 = 'finance_l3';

    protected static $defaultRoles = [
        self::OWNER,
        self::ADMIN,
    ];

    protected static $workflowRoles = [
        self::FINANCE_L1,
        self::FINANCE_L2,
        self::FINANCE_L3,
    ];

    protected static $workflowRoleToNameMap = [
        self::FINANCE_L1 => 'Finance L1',
        self::FINANCE_L2 => 'Finance L2',
        self::FINANCE_L3 => 'Finance L3',
    ];

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public static function getAllRolesForMerchant(Merchant\Entity $merchant): array
    {
        $bankingRoles = self::$defaultRoles;

        $workflowsEnabled = $merchant->isFeatureEnabled(Feature\Constants::PAYOUT_WORKFLOWS);

        if ($workflowsEnabled === true)
        {
            $bankingRoles = array_merge($bankingRoles, self::$workflowRoles);
        }

        return $bankingRoles;
    }
}
