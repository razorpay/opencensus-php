<?php

namespace RZP\Models\User;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

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
    const OWNER      = Role::OWNER;
    const ADMIN      = Role::ADMIN;
    const VIEW_ONLY  = Role::VIEW_ONLY;
    const OPERATIONS = Role::OPERATIONS;

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
        self::VIEW_ONLY,
        self::OPERATIONS
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

    public static function isWorkflowRole(string $role): bool
    {
        return (in_array($role, self::$workflowRoles, true) === true);
    }

    public static function getNameForWorkflowRole(string $roleId): string
    {
        return self::$workflowRoleToNameMap[$roleId];
    }

    public static function getNamesForWorkflowRoles(array $roleIdentifiers): array
    {
        $names = [];

        foreach ($roleIdentifiers as $roleId)
        {
            $names[] = self::getNameForWorkflowRole($roleId);
        }

        return $names;
    }

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public static function getAllRoles(): array
    {
        $bankingRoles = array_merge(self::$defaultRoles, self::$workflowRoles);

        return $bankingRoles;
    }

    public static function getDefaultRoles(): array
    {
        return self::$defaultRoles;
    }
}
