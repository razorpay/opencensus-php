<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Admin\Permission;

class Constants
{
    const ONBOARDING_WORKFLOWS = [
        Permission\Name::NEEDS_CLARIFICATION_RESPONDED,
        Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH,
        Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH_UNREGISTERED,
        Permission\Name::EDIT_ACTIVATE_PARTNER,
    ];

    const KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES = ['password','password_confirmation'];

    const ACTION_REJECT_CALLBACK_HANDLERS = [
        Permission\Name::MERCHANT_RISK_ALERT_FOH             => \RZP\Models\MerchantRiskAlert\Service::class,
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL      => \RZP\Models\Typeform\Service::class,
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL => \RZP\Models\Typeform\Service::class,
    ];

    const CLOSE_OPERATION_UNSUPPORTED_PERMISSIONS = [
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL,
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
    ];

    public static function getActionRejectHandlerByPermissionName(string $permissionName): ?string
    {
        if (isset(self::ACTION_REJECT_CALLBACK_HANDLERS[$permissionName]) === false)
        {
            return null;
        }

        return self::ACTION_REJECT_CALLBACK_HANDLERS[$permissionName];
    }
}
