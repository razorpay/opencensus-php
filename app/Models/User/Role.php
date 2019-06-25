<?php

namespace RZP\Models\User;

use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Exception\LogicException;

class Role
{
    const MANAGER               = 'manager';
    const OPERATIONS            = 'operations';
    const FINANCE               = 'finance';
    const SUPPORT               = 'support';
    const ADMIN                 = 'admin';
    const SELLERAPP             = 'sellerapp';
    const OWNER                 = 'owner';
    const LINKED_ACCOUNT_OWNER  = 'linked_account_owner';
    const LINKED_ACCOUNT_ADMIN  = 'linked_account_admin';
    const RBL_SUPERVISOR        = 'rbl_supervisor';
    const RBL_AGENT             = 'rbl_agent';

    // Payment Link Agent - not publicly available
    const AGENT                 = 'agent';

    const ALL_ROLES = [
        self::MANAGER,
        self::OPERATIONS,
        self::FINANCE,
        self::SUPPORT,
        self::ADMIN,
        self::SELLERAPP,
        self::OWNER,
        self::AGENT,
    ];

    const WRITER_ROLES = [
        self::OWNER,
        self::MANAGER,
        self::OPERATIONS,
        self::ADMIN,
    ];

    const READER_ROLES = [
        self::OWNER,
        self::MANAGER,
        self::OPERATIONS,
        self::FINANCE,
        self::ADMIN,
    ];

    // Custom roles defined for payment link access control
    const PL_ROLES = [
        self::SELLERAPP,
        self::AGENT,
    ];

    const LINKED_ACCOUNT_ROLES = [
        self::LINKED_ACCOUNT_ADMIN,
        self::LINKED_ACCOUNT_OWNER
    ];

    const BANKING_ROLES = [
        self::OWNER,
        self::ADMIN
    ];

    const RBL_ROLES = [
        self::RBL_SUPERVISOR,
        self::RBL_AGENT
    ];

    public static function getPrimaryRoles(): array
    {
        return array_merge(self::ALL_ROLES, self::LINKED_ACCOUNT_ROLES, self::RBL_ROLES);
    }

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public static function validateProductRoleForMerchant(string $role, string $product, Merchant\Entity $merchant = null): bool
    {
        switch ($product)
        {
            case Product::PRIMARY:
                $productRoles = self::getPrimaryRoles();
                break;

            case Product::BANKING:
                $productRoles = BankingRole::getAllRolesForMerchant($merchant);
                break;

            default:
                throw new LogicException('Logic not defined for product: ' . $product);
        }

        return (in_array($role, $productRoles, true) === true);
    }

    public static function allExceptPaymentLinkRoles()
    {
        return array_diff(self::ALL_ROLES, self::PL_ROLES);
    }
}
