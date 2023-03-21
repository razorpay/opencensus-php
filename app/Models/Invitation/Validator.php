<?php

namespace RZP\Models\Invitation;

use phpDocumentor\Reflection\Types\Boolean;
use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Roles;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Admin\Role;

class Validator extends Base\Validator
{
    const CREATE_BANK_LMS_USER = 'createBankLmsUser';

    protected static $createRules = [
        Entity::ROLE        => 'required|string|custom',
        Entity::EMAIL       => 'required|max:255|email|custom',
        Entity::TOKEN       => 'required|string',
        Entity::SENDER_NAME => 'sometimes|string',
        Entity::PRODUCT     => 'sometimes|string|in:primary,banking',
        Entity::IS_DRAFT    => 'sometimes|boolean|',
        Entity::INVITATIONTYPE => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::ROLE        => 'required|string|bail|custom',
        Entity::IS_DRAFT    => 'sometimes|boolean|',
    ];

    protected static $resendRules = [
        Entity::SENDER_NAME => 'sometimes|string',
    ];

    protected static $actionRules = [
        Entity::USER_ID => 'required|string|max:14',
        Entity::ACTION  => 'required|string|in:accept,reject',
    ];

    protected static $createBankLmsUserRules = [
        Entity::ROLE        => 'required|string|in:bank_mid_office_poc,bank_mid_office_manager',
        Entity::EMAIL       => 'required|max:255|email',
        Entity::SENDER_NAME => 'sometimes|string',
    ];

    public function validateEmail(string $attribute, string $email)
    {
        $product = app('basicauth')->getRequestOriginProduct();

        $merchant = $this->entity->merchant;

        $vendorPortalMerchantId = app('config')->get('applications.vendor_payments.vendor_portal_merchant_id');

        if ($merchant->getPublicId() == $vendorPortalMerchantId) {
            return;
        }

        if (($merchant->invitations
                      ->where(Entity::EMAIL, $email)
                      ->where(Entity::PRODUCT, $product)
                      ->isEmpty()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_INVITED);
        }

        if (($merchant->users()
                      ->where(Entity::EMAIL, $email)
                      ->wherePivot(Entity::PRODUCT, $product)
                      ->get()
                      ->isEmpty()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_MEMBER);
        }
    }

    protected function validateRole(string $attribute, string $role)
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $this->entity->merchant;

        $product = app('basicauth')->getRequestOriginProduct();

        $userRole = app('basicauth')->getUserRole();

        if ($merchant->isLinkedAccount() === true)
        {
            $dashboardRoles = User\Role::LINKED_ACCOUNT_ROLES;
        }
        else if ($userRole === User\Role::RBL_SUPERVISOR)
        {
            $dashboardRoles = User\Role::RBL_ROLES;
        }
        else if ($product === Product::BANKING)
        {
            if ($role === User\BankingRole::OWNER)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_ROLE_INVALID);
            }

            $roleEntity = (new Roles\Repository())->fetchRole($role);

            $dashboardRoles = ( empty($roleEntity) === false and !in_array($role, User\BankingRole::$rblBankCaManagementRoles)) ? [ $role ] : [];
        }
        else
        {
            $dashboardRoles = array_values(array_diff(User\Role::ALL_ROLES, [User\Role::OWNER]));
        }

        if ($merchant->isTagAdded('enable_rbl_role') === true)
        {
            $dashboardRoles = array_merge($dashboardRoles, User\Role::RBL_ROLES);
        }

        if ($merchant->isTagAdded(Merchant\Constants::ENABLE_RBL_LMS_DASHBOARD) === true)
        {
            $dashboardRoles = User\BankingRole::$rblBankCaManagementRoles;
        }

        $vendorPortalMerchantId = app('config')->get('applications.vendor_payments.vendor_portal_merchant_id');

        if ($merchant->getPublicId() == $vendorPortalMerchantId)
        {
            $dashboardRoles = [User\BankingRole::VENDOR];
        }

        if (in_array($role, $dashboardRoles, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_ROLE_INVALID);
        }
    }
}
