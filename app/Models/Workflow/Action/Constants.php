<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Admin\Permission;

class Constants
{
    const AUTO_CLOSE_WF_NAMES_ON_NC = [
        Permission\Name::EDIT_ACTIVATE_MERCHANT,
        Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH,
        Permission\Name::EDIT_ACTIVATE_PARTNER,
    ];

    const KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES = ['password','password_confirmation'];

    const ACTION_REJECT_CALLBACK_HANDLERS = [
        Permission\Name::MERCHANT_RISK_ALERT_FOH => \RZP\Models\MerchantRiskAlert\Service::class,
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
