<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Admin\Permission;

class Constants
{
    const MESSAGE_BODY     = 'body';

    const MESSAGE_SUBJECT  = 'subject';

    const MERCHANT         = 'merchant';

    const MERCHANT_DETAIL = 'merchant_detail';

    const NEEDS_WORKFLOW_CLARIFICATION_COMMENT_KEY = 'need_clarification_comment : ';

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
        Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED       => \RZP\Models\Typeform\Service::class,
        Permission\Name::EXECUTE_MERCHANT_SUSPEND_BULK       => \RZP\Models\BulkWorkflowAction\Service::class,
        Permission\Name::EXECUTE_MERCHANT_HOLD_FUNDS_BULK    => \RZP\Models\BulkWorkflowAction\Service::class,
        Permission\Name::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK   => \RZP\Models\BulkWorkflowAction\Service::class,
    ];

    const CLOSE_OPERATION_UNSUPPORTED_PERMISSIONS = [
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL,
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
        Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED,
    ];

    const WORKFLOWS_FOR_NEED_MERCHANT_CLARIFICATION = [
        Permission\Name::EDIT_MERCHANT_WEBSITE_DETAIL,
        Permission\Name::UPDATE_MERCHANT_WEBSITE,
        Permission\Name::EDIT_MERCHANT_BANK_DETAIL,
        Permission\Name::UPDATE_MERCHANT_GSTIN_DETAIL,
        Permission\Name::EDIT_MERCHANT_GSTIN_DETAIL,
        Permission\Name::ADD_ADDITIONAL_WEBSITE,
        Permission\Name::INCREASE_TRANSACTION_LIMIT,
        Permission\Name::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT,
        Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED,
    ];

    const PERMISSION_FOR_NEW_SELF_SERVE_COMMUNICATIONS = [
        Permission\Name::EDIT_MERCHANT_BANK_DETAIL,
    ];

    const WORKFLOWS_EXCLUDED_FOR_MAKER_IS_SAME_AS_CHECKER_OR_OWNER_VALIDATION = [
        Permission\Name::EDIT_MERCHANT_METHODS,
        Permission\Name::EDIT_MERCHANT_PRICING,
    ];

    const WORKFLOW_NEEDS_MERCHANT_CLARIFICATION_TAG      = 'Awaiting Customer Response';
    const WORKFLOW_MERCHANT_RESPONDED_TAG                = 'Customer Responded';
    const ADDED_TAG                                      = 'added_tag';
    const ADDED_COMMENT                                  = 'added_comment';

    const STATUS                                         = 'status';
    const NEEDS_CLARIFICATION                            = 'needs_clarification';

    const REJECTED_REASON = 'rejection_reason';

    const WORKFLOW_TAGS = 'workflow_tags';

    public static function getActionRejectHandlerByPermissionName(string $permissionName): ?string
    {
        if (isset(self::ACTION_REJECT_CALLBACK_HANDLERS[$permissionName]) === false)
        {
            return null;
        }

        return self::ACTION_REJECT_CALLBACK_HANDLERS[$permissionName];
    }
}
