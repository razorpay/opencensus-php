<?php

namespace RZP\Models\User;

use RZP\Models\Roles;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;

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
    const VIEW_ONLY             = 'view_only';
    const CHARTERED_ACCOUNTANT  = 'chartered_accountant';
    const VENDOR                = 'vendor';
    const AUTH_LINK_SUPERVISOR  = 'auth_link_supervisor';
    const AUTH_LINK_AGENT       = 'auth_link_agent';

    // SELLERAPP and extra functionality (Reports) - not publicly available.
    const SELLERAPP_PLUS        = 'sellerapp_plus';

    // Payment Link Agent - not publicly available
    const AGENT                 = 'agent';

    const AUTHORISED_SIGNATORY = 'authorised_signatory';
    const CC_ADMIN             = 'cc_admin';
    const VIEWER               = 'view_only';
    const MAKER                = 'maker';
    const MAKER_ADMIN          = 'maker_admin';

    const CHECKER_L1 = 'checker_l1';
    const CHECKER_L2 = 'checker_l2';
    const CHECKER_L3 = 'checker_l3';

    const ALL_ROLES = [
        self::MANAGER,
        self::OPERATIONS,
        self::FINANCE,
        self::SUPPORT,
        self::ADMIN,
        self::SELLERAPP,
        self::OWNER,
        self::AGENT,
        self::SELLERAPP_PLUS,
        self::AUTH_LINK_AGENT,
        self::AUTH_LINK_SUPERVISOR,

        self::CC_ADMIN,
        self::VIEWER,
        self::AUTHORISED_SIGNATORY,
        self::CHECKER_L1,
        self::CHECKER_L2,
        self::CHECKER_L3,
        self::MAKER_ADMIN,
        self::MAKER,

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
        self::SELLERAPP_PLUS,
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

    /**
     * Only Owner/Admin can update some user details
     * such as mobile number, unlock user account.
     */
    const USER_DETAILS_UPDATE_ROLES = [
        self::OWNER,
        self::ADMIN,
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

                if (BankingRole::existsBankingLMSRole($role)) {
                    return true;
                }

                $roleEntity = (new Roles\Repository())->fetchRole($role);

                if(empty($roleEntity))
                {
                    return false;
                }
                return true;

            default:
                throw new LogicException('Logic not defined for product: ' . $product);
        }

        return (in_array($role, $productRoles, true) === true);
    }

    public static function allExceptSellerAppRole()
    {
        $allRoles = array_merge(self::ALL_ROLES, BankingRole::getAllRoles());

        return array_diff($allRoles, [self::SELLERAPP]);
    }

    public static function allExceptPaymentLinkRoles()
    {
        $allRoles = array_merge(self::ALL_ROLES, BankingRole::getAllRoles());

        return array_diff($allRoles, self::PL_ROLES);
    }

    public function validateMerchantUserRoleForUpdateUserDetails(string $role)
    {
        if (in_array($role, self::USER_DETAILS_UPDATE_ROLES, true) === true)
        {
            return;
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ROLE_INVALID);
    }
}
