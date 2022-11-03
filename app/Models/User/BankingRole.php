<?php

namespace RZP\Models\User;

use RZP\Exception;
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
    const OWNER                = Role::OWNER;
    const ADMIN                = Role::ADMIN;
    const VIEW_ONLY            = Role::VIEW_ONLY;
    const OPERATIONS           = Role::OPERATIONS;
    const CHARTERED_ACCOUNTANT = Role::CHARTERED_ACCOUNTANT;
    const VENDOR               = Role::VENDOR;

    //
    // Dynamic Roles:
    // These roles are linked to workflows on the banking product, used by
    // heimdall workflows and are hence also persisted to the `roles` table.
    const FINANCE_L1 = 'finance_l1';
    const FINANCE_L2 = 'finance_l2';
    const FINANCE_L3 = 'finance_l3';
    const FINANCE    = 'finance';

    const AUTHORISED_SIGNATORY = 'authorised_signatory';
    const CC_ADMIN             = 'cc_admin';
    const VIEWER               = 'view_only';
    const MAKER                = 'maker';
    const MAKER_ADMIN          = 'maker_admin';

    const CHECKER_L1 = 'checker_l1';
    const CHECKER_L2 = 'checker_l2';
    const CHECKER_L3 = 'checker_l3';

    const BANK_MID_OFFICE_POC = 'bank_mid_office_poc';
    const BANK_MID_OFFICE_MANAGER = 'bank_mid_office_manager';

    protected static $defaultRoles = [
        self::OWNER,
        self::ADMIN,
        self::VIEW_ONLY,
        self::OPERATIONS,
        self::CHARTERED_ACCOUNTANT
    ];

    //initially adding axis roles array , eventually will switch to axisUserRole files
    protected static $axisRoles = [
        self::CC_ADMIN,
        self::VIEWER,
        self::MAKER,
        self::MAKER_ADMIN,
        self::AUTHORISED_SIGNATORY,
        self::CHECKER_L1,
        self::CHECKER_L2,
        self::CHECKER_L3,
    ];

    protected static $workflowRoles = [
        self::FINANCE_L1,
        self::FINANCE_L2,
        self::FINANCE_L3,
        self::FINANCE,
        // Owner and admin will also be possible workflow roles now,
        // hence adding here.
        self::OWNER,
        self::ADMIN,
    ];


    public static $rblBankCaManagementRoles = [
        self::OWNER,
        self::BANK_MID_OFFICE_POC,
        self::BANK_MID_OFFICE_MANAGER,
    ];


    protected static $workflowRoleToNameMap = [
        self::FINANCE_L1 => 'Finance L1',
        self::FINANCE_L2 => 'Finance L2',
        self::FINANCE_L3 => 'Finance L3',
        self::FINANCE    => 'Finance',
        self::OWNER      => 'Owner',
        self::ADMIN      => 'Admin',
    ];

    protected static $axisRoleToNameMap = [
        self::CC_ADMIN               => 'cc_admin',
        self::VIEWER                 => 'view_only',
        self::MAKER                  => 'maker',
        self::MAKER_ADMIN            => 'maker_admin',
        self::CHECKER_L1             => 'checker_l1',
        self::CHECKER_L2             => 'checker_l2',
        self::CHECKER_L3             => 'checker_l3',
        self::AUTHORISED_SIGNATORY   => 'authorised_signatory',

    ];


    public static function isWorkflowRole(string $role): bool
    {
        return (in_array($role, self::$workflowRoles, true) === true);
    }

    /**
     * @param string $roleId
     * @return string
     */
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
        $bankingRoles = array_merge(self::$defaultRoles, self::$workflowRoles, self::$axisRoles, self::$rblBankCaManagementRoles);

        return $bankingRoles;
    }

    public static function getWorkflowRoles(): array
    {
        return  self::$workflowRoles;
    }

    public static function getVendorPortalRoles(): array
    {
        return [BankingRole::VENDOR];
    }

    public static function getDefaultRoles(): array
    {
        return self::$defaultRoles;
    }

    public static function existsBankingLMSRole($role): bool
    {
        return in_array($role, self::$rblBankCaManagementRoles, true);
    }
}
