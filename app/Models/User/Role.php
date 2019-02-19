<?php

namespace RZP\Models\User;

use RZP\Constants\Product;

class Role
{
    protected $productRoles = [];

    public function __construct()
    {
        $this->setProductRoles();
    }
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

    public function setProductRoles()
    {
        $this->productRoles = [
            Product::PRIMARY => array_merge(self::ALL_ROLES, self::LINKED_ACCOUNT_ROLES),
            Product::BANKING => self::BANKING_ROLES
        ];
    }

    public static function exists(string $action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public function validateProductRole(string $role, string $product): bool
    {
        $productRoles = $this->productRoles[$product];

        return (in_array($role, $productRoles, true) === true);
    }

    public static function allExceptPaymentLinkRoles()
    {
        return array_diff(self::ALL_ROLES, self::PL_ROLES);
    }
}
