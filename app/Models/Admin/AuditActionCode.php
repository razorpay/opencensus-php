<?php

namespace RZP\Models\Admin;


class AuditActionCode
{
    // categories
    const CATEGORY_AUTH                 = 'Auth';
    const CATEGORY_AUTH_POLICY          = 'Auth Policy';
    const CATEGORY_UAM                  = 'UAM';
    const CATEGORY_ORG                  = 'Org';
    const CATEGORY_HOSTNAME             = 'Hostname';
    const CATEGORY_MERCHANT             = 'Merchant';
    const CATEGORY_USER                 = 'User';
    const CATEGORY_DISPUTE              = 'Dispute';

    // actions
    const ACTION_CREATE                 = 'Create';
    const ACTION_EDIT                   = 'Edit';
    const ACTION_DELETE                 = 'Delete';
    const ACTION_LOGIN                  = 'Login';
    const ACTION_LOGIN_FAIL             = 'Login Fail';
    const ACTION_LOGIN_OAUTH            = 'Login Oauth';
    const ACTION_LOGIN_FAIL_OAUTH       = 'Login Oauth Fail';
    const ACTION_GENERATE_LOGIN_TOKEN   = 'Generate Login Token';
    const ACTION_RESET_PASSWORD         = 'Reset Password';
    const ACTION_RESET_PASSWORD_FAILED  = 'Reset Password Failed';

    const ACTION_RESET_PASSWORD_INVALID_OLD_PASSWORD = 'Reset Password Invalid Old Password';
    const ACTION_RESET_PASSWORD_INVALID_AUTH_TYPE = 'Reset Password Invalid Auth Type';

    // labels
    const LABEL_ADMIN                   = 'Admin';
    const LABEL_GROUP                   = 'Group';
    const LABEL_ORG                     = 'Org';
    const LABEL_HOSTNAME                = 'Hostname';
    const LABEL_PERMISSION              = 'Permission';
    const LABEL_ADMIN_ROLES             = 'Admin Roles';
    const LABEL_MERCHANT                = 'Merchant';
    const LABEL_SUBMERCHANT             = 'Sub-Merchant';
    const LABEL_MERCHANT_BALANCE        = 'Merchant Balance';
    const LABEL_MERCHANT_CREDITS        = 'Merchant Credits';
    const LABEL_MERCHANT_PRICING_PLAN   = 'Merchant Pricing Plan';
    const LABEL_PRICING_PLAN_RULE       = 'Pricing Plan Rule';
    const LABEL_USER                    = 'User';
    const LABEL_DISPUTE                 = 'Dispute';
}
