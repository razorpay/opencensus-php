<?php

namespace RZP\Models\Invitation;

use RZP\Base;
use RZP\Constants\Product;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Roles;
use RZP\Models\User;
use Lib\PhoneBook;

class Validator extends Base\Validator
{
    const CREATE_BANK_LMS_USER                  = 'createBankLmsUser';
    const CREATE_XPERIENCE_INVITATION           = 'createXperienceInvitation';
    const CREATE_INVITATION_VERIFY_OTP          = 'createInvitationVerifyOtp';
    const RESEND_XPERIENCE_USER_INVITE          = 'resendXperienceUserInvite';
    const CREATE_INVITATION_VENDOR_PORTAL_V2    = 'createInvitationVendorPortalV2';
    const HANDLE_INVITATION_VENDOR_PORTAL_V2    = 'handleInvitationVendorPortalV2';
    const PRODUCT                               = 'product';
    const ACCEPT_REJECT_INVITATION              = 'acceptRejectInvitation';

    protected static $createRules = [
        Entity::ROLE                        => 'required|string|custom',
        Entity::EMAIL                       => 'required_without:contact_mobile|max:255|email|custom',
        Entity::CONTACT_MOBILE              => 'required_without:email|max:15|contact_syntax|custom',
        Entity::TOKEN                       => 'required|string',
        Entity::SENDER_NAME                 => 'sometimes|string',
        Entity::PRODUCT                     => 'sometimes|string|in:primary,banking',
        Entity::IS_DRAFT                    => 'sometimes|boolean|',
        Entity::INVITATIONTYPE              => 'sometimes|string',
        Entity::INVITATION_DETAILS          => 'sometimes|array|custom',
        Entity::METADATA                    => 'sometimes|array|nullable',
        Entity::METADATA.'.'.Entity::NAME   => 'sometimes|string',
    ];

    protected static $productRules = [
        Entity::PRODUCT => 'required|string|in:primary,banking',
    ];

    protected static $createXperienceInvitationRules = [
        Entity::ROLE               => 'required|string|custom',
        Entity::EMAIL              => 'required|max:255|email',
        Entity::SENDER_NAME        => 'sometimes|string',
        Entity::PRODUCT            => 'sometimes|string|in:primary,banking',
        Entity::IS_DRAFT           => 'sometimes|boolean|',
        Entity::INVITATION_DETAILS => 'array|custom',
    ];

    protected static $createInvitationVerifyOtpRules = [
        'otp'    => 'required|filled|min:4',
        'token'  => 'required|unsigned_id',
        'action' => 'required|string',
    ];

    protected static $createInvitationVendorPortalV2Rules = [
        Entity::EMAIL              => 'required|max:255|email',
    ];

    protected static $handleInvitationVendorPortalV2Rules = [
        Entity::USER_ID     => 'required|string|max:14',
        Entity::ACTION      => 'required|string|in:accept,reject',
    ];

    protected static $editRules = [
        Entity::ROLE        => 'required|string|bail|custom',
        Entity::IS_DRAFT    => 'sometimes|boolean|',
        Entity::METADATA                    => 'sometimes|array|nullable',
        Entity::METADATA.'.'.Entity::NAME   => 'sometimes|string',
    ];

    protected static $resendRules = [
        Entity::SENDER_NAME => 'sometimes|string',
    ];

    protected static $resendXperienceUserInviteRules = [
        Entity::SENDER_NAME        => 'sometimes|string',
        Entity::INVITATION_DETAILS => 'sometimes|array|custom',
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

    protected static $acceptRejectInvitationRules = [
        Entity::USER_ID            => 'required|string|max:14',
        Entity::ACTION             => 'required|string|in:accept,reject',
        Entity::EMAIL              => 'required_without:contact_mobile|nullable|max:255|email',
        Entity::CONTACT_MOBILE     => 'required_without:email|nullable|max:15|contact_syntax',
    ];

    public function validateEmail(string $attribute, string $email)
    {
        if (empty($email) === true) {
            return;
        }

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

    public function validateContactMobile(string $attribute, string $rawContactMobile)
    {
        if (empty($rawContactMobile) === true) {
            return;
        }

        $product = app('basicauth')->getRequestOriginProduct();

        $merchant = $this->entity->merchant;

        // Note: this validation is cloned from email validation
        $vendorPortalMerchantId = app('config')->get('applications.vendor_payments.vendor_portal_merchant_id');

        if ($merchant->getPublicId() == $vendorPortalMerchantId) {
            return;
        }

        // Check for all formats being stored for the contact mobile(domestic, international, space-separated, etc)
        $validContactMobileFormats = (new PhoneBook($rawContactMobile, true))->getMobileNumberFormats();
        // Add raw contact to the query
        array_unshift($validContactMobileFormats, $rawContactMobile);

        if (($merchant->invitations
                      ->whereIn(Entity::CONTACT_MOBILE, $validContactMobileFormats)
                      ->where(Entity::PRODUCT, $product)
                      ->isEmpty()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_INVITED_FOR_PHONE);
        }

        if (($merchant->users()
                      ->whereIn(Entity::CONTACT_MOBILE, $validContactMobileFormats)
                      ->wherePivot(Entity::PRODUCT, $product)
                      ->get()
                      ->isEmpty()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_MEMBER_FOR_PHONE);
        }
    }

    protected function validateInvitationDetails(string $attribute, array $invitationDetails)
    {
        if (empty($invitationDetails) === true)
        {
            return;
        }

        if (array_key_exists(Constants::INVITATION_DETAILS_INPUT_FIRST_NAME, $invitationDetails) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVITATION_DETAILS_FIRST_NAME_MISSING);
        }

        if (array_key_exists(Constants::INVITATION_DETAILS_INPUT_LAST_NAME, $invitationDetails) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVITATION_DETAILS_LAST_NAME_MISSING);
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

        if ($merchant->isTagAdded('enable_rbl_role') === true) {
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
