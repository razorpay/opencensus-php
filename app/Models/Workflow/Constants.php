<?php

namespace RZP\Models\Workflow;

class Constants
{
    const CHECKER_ACTIONS   = 'checker_actions';
    const CLOSED_ACTIONS    = 'closed_actions';
    const ACTIONS_CHECKED   = 'actions_checked';
    const TYPE              = 'type';
    const EXPAND            = 'expand';
    const DUTY              = 'duty';

    const CREATED_START     = 'created_start';
    const CREATED_END       = 'created_end';

    const ORDER             = 'order';

    const APPROVAL_START_TIME   = 'approval_start_time';
    const APPROVAL_END_TIME     = 'approval_end_time';
    const WORKFLOW_IDS          = 'workflow_ids';
    const WORKFLOW_ACTION_IDS   = 'workflow_action_ids';

    const RISK_AUDIT_WORKFLOWS_FOR_QUERY = 'risk_audit_workflows_for_query';

    const WORKFLOW_NAME = 'workflow_name';
    const WORKFLOW_ID = 'workflow_id';

    const ON = 'on';

    const WORKFLOW_TOGGLE_ACTIVATE_MERCHANT                   = 'WORKFLOW_TOGGLE_ACTIVATE_MERCHANT';
    const WORKFLOW_TOGGLE_FUNDS                               = 'WORKFLOW_TOGGLE_FUNDS';
    const WORKFLOW_TOGGLE_INTERNATIONAL                       = 'WORKFLOW_TOGGLE_INTERNATIONAL';
    const WORKFLOW_TOGGLE_SUSPEND                             = 'WORKFLOW_TOGGLE_SUSPEND';
    const WORKFLOW_TOGGLE_MERCHANT_LIVE                       = 'WORKFLOW_TOGGLE_MERCHANT_LIVE';
    const WORKFLOW_TOGGLE_PG_INTERNATIONAL                    = 'WORKFLOW_TOGGLE_PG_INTERNATIONAL';
    const WORKFLOW_TOGGLE_EDIT_MERCHANT_PROD_V2_INTERNATIONAL = 'WORKFLOW_TOGGLE_EDIT_MERCHANT_PROD_V2_INTERNATIONAL';
    const WORKFLOW_MERCHANT_RISK_ALERT_FUNDS_ON_HOLD          = 'WORKFLOW_MERCHANT_RISK_ALERT_FUNDS_ON_HOLD';
    const WORKFLOW_TOGGLE_EDIT_MERCHANT_INTERNATIONAL_NEW     = 'WORKFLOW_TOGGLE_EDIT_MERCHANT_INTERNATIONAL_NEW';
    const WORKFLOW_INTERNATIONAL_ENABLEMENT_THROUGH_FORM      = 'WORKFLOW_INTERNATIONAL_ENABLEMENT_THROUGH_FORM';
    const WORKFLOW_TOGGLE_INTERNATIONAL_V2                    = 'WORKFLOW_TOGGLE_INTERNATIONAL_V2';
    const WORKFLOW_TOGGLE_FUNDS_V2                            = 'WORKFLOW_TOGGLE_FUNDS_V2';
    const WORKFLOW_UNSUSPEND_MERCHANT                         = 'WORKFLOW_UNSUSPEND_MERCHANT';
    const WORKFLOW_TOGGLE_HOLD_FUNDS                          = 'WORKFLOW_TOGGLE_HOLD_FUNDS';
    const WORKFLOW_TOGGLE_SUSPEND_V2                          = 'WORKFLOW_TOGGLE_SUSPEND_V2';
    const WORKFLOW_TOGGLE_INTERNATIONAL_V3                    = 'WORKFLOW_TOGGLE_INTERNATIONAL_V3';
    const WORKFLOW_TOGGLE_HOLD_FUNDS_V2                       = 'WORKFLOW_TOGGLE_HOLD_FUNDS_V2';
    const WORKFLOW_TOGGLE_MERCHANT_LIVE_V2                    = 'WORKFLOW_TOGGLE_MERCHANT_LIVE_V2';
    const WORKFLOW_TOGGLE_SUSPEND_V3                          = 'WORKFLOW_TOGGLE_SUSPEND_V3';
    const WORKFLOW_TOGGLE_MERCHANT_LIVE_V3                    = 'WORKFLOW_TOGGLE_MERCHANT_LIVE_V3';
    const WORKFLOW_TOGGLE_ACTIVATE_MERCHANT_V2                = 'WORKFLOW_TOGGLE_ACTIVATE_MERCHANT_V2';
    const WORKFLOW_TOGGLE_SUSPEND_V4                          = 'WORKFLOW_TOGGLE_SUSPEND_V4';

    public static function getRiskAuditWorkflows()
    {
        return [
            self::WORKFLOW_TOGGLE_ACTIVATE_MERCHANT => [
                self::WORKFLOW_NAME => "Toggle Activate Merchant",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_ACTIVATE_MERCHANT),
            ],
            self::WORKFLOW_TOGGLE_FUNDS => [
                self::WORKFLOW_NAME => "Toggle Funds",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_FUNDS),
            ],
            self::WORKFLOW_TOGGLE_INTERNATIONAL => [
                self::WORKFLOW_NAME => "Toggle international",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_INTERNATIONAL),
            ],
            self::WORKFLOW_TOGGLE_SUSPEND => [
                self::WORKFLOW_NAME => "Toggle Suspend",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_SUSPEND),
            ],
            self::WORKFLOW_TOGGLE_MERCHANT_LIVE => [
                self::WORKFLOW_NAME => "Toggle Merchant Live",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_MERCHANT_LIVE),
            ],
            self::WORKFLOW_TOGGLE_PG_INTERNATIONAL => [
                self::WORKFLOW_NAME => "Toggle PG international",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_PG_INTERNATIONAL),
            ],
            self::WORKFLOW_TOGGLE_EDIT_MERCHANT_PROD_V2_INTERNATIONAL => [
                self::WORKFLOW_NAME => "Toggle Edit Merchant Prod v2 International",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_EDIT_MERCHANT_PROD_V2_INTERNATIONAL),
            ],
            self::WORKFLOW_MERCHANT_RISK_ALERT_FUNDS_ON_HOLD => [
                self::WORKFLOW_NAME => "Merchant Risk Alert - Funds on Hold",
                self::WORKFLOW_ID   => env(self::WORKFLOW_MERCHANT_RISK_ALERT_FUNDS_ON_HOLD),
            ],
            self::WORKFLOW_TOGGLE_EDIT_MERCHANT_INTERNATIONAL_NEW => [
                self::WORKFLOW_NAME => "Toggle Edit merchant International New",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_EDIT_MERCHANT_INTERNATIONAL_NEW),
            ],
            self::WORKFLOW_INTERNATIONAL_ENABLEMENT_THROUGH_FORM => [
                self::WORKFLOW_NAME => "International enablement through form",
                self::WORKFLOW_ID   => env(self::WORKFLOW_INTERNATIONAL_ENABLEMENT_THROUGH_FORM),
            ],
            self::WORKFLOW_TOGGLE_INTERNATIONAL_V2 => [
                self::WORKFLOW_NAME => "Toggle international",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_INTERNATIONAL_V2),
            ],
            self::WORKFLOW_TOGGLE_FUNDS_V2 => [
                self::WORKFLOW_NAME => "Toggle Funds",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_FUNDS_V2),
            ],
            self::WORKFLOW_UNSUSPEND_MERCHANT => [
                self::WORKFLOW_NAME => "Unsuspend Merchant",
                self::WORKFLOW_ID   => env(self::WORKFLOW_UNSUSPEND_MERCHANT),
            ],
            self::WORKFLOW_TOGGLE_HOLD_FUNDS => [
                self::WORKFLOW_NAME => "Toggle Hold Funds",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_HOLD_FUNDS),
            ],
            self::WORKFLOW_TOGGLE_SUSPEND_V2 => [
                self::WORKFLOW_NAME => "Toggle Suspend",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_SUSPEND_V2),
            ],
            self::WORKFLOW_TOGGLE_INTERNATIONAL_V3 => [
                self::WORKFLOW_NAME => "Toggle international",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_INTERNATIONAL_V3),
            ],
            self::WORKFLOW_TOGGLE_HOLD_FUNDS_V2  => [
                self::WORKFLOW_NAME => "Toggle Hold Funds",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_HOLD_FUNDS_V2),
            ],
            self::WORKFLOW_TOGGLE_MERCHANT_LIVE_V2 => [
                self::WORKFLOW_NAME => "Toggle Merchant Live",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_MERCHANT_LIVE_V2),
            ],
            self::WORKFLOW_TOGGLE_SUSPEND_V3 => [
                self::WORKFLOW_NAME => "Toggle Suspend",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_SUSPEND_V3),
            ],
            self::WORKFLOW_TOGGLE_MERCHANT_LIVE_V3  => [
                self::WORKFLOW_NAME => "Toggle Merchant Live",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_MERCHANT_LIVE_V3),
            ],
            self::WORKFLOW_TOGGLE_ACTIVATE_MERCHANT_V2 => [
                self::WORKFLOW_NAME => "Toggle Activate Merchant",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_ACTIVATE_MERCHANT_V2),
            ],
            self::WORKFLOW_TOGGLE_SUSPEND_V4 => [
                self::WORKFLOW_NAME => "WORKFLOW_TOGGLE_suspend",
                self::WORKFLOW_ID   => env(self::WORKFLOW_TOGGLE_SUSPEND_V4),
            ]
        ];
    }

    public static function getRiskAuditWorkflowIds()
    {
        $workflowIdsObject = array_map(
            function ($workflow)
            {
                return $workflow[Constants::WORKFLOW_ID];
            },
            Constants::getRiskAuditWorkflows()
        );


        return array_values($workflowIdsObject);
    }
}
