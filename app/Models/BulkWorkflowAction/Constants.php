<?php

namespace RZP\Models\BulkWorkflowAction;

use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Action;

class Constants
{
    const BULK_WORKFLOW_ACTION_ROUTE_NAME = 'execute_bulk_action';
    const BULK_WORKFLOW_ACTION_ROUTE_CONTROLLER = 'RZP\Http\Controllers\BulkActionController@executeBulkAction';

    const BULK_WORKFLOW_ACTION_PERMISSION_NAME = [
        Action::LIVE_ENABLE     => Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK,
        Action::LIVE_DISABLE    => Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK,
        Action::HOLD_FUNDS      => Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK,
        Action::RELEASE_FUNDS   => Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK,
        Action::SUSPEND         => Permission::EXECUTE_MERCHANT_SUSPEND_BULK,
        Action::UNSUSPEND       => Permission::EXECUTE_MERCHANT_SUSPEND_BULK,
        Action::ENABLE_INTERNATIONAL  => Permission::EXECUTE_MERCHANT_ENABLE_INTERNATIONAL_BULK,
        Action::DISABLE_INTERNATIONAL => Permission::EXECUTE_MERCHANT_DISABLE_INTERNATIONAL_BULK,
    ];

    const BULK_WORKFLOW_ACTION_PERMISSION = [
        Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK,
        Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK,
        Permission::EXECUTE_MERCHANT_SUSPEND_BULK,
    ];

    const RISK_ACTION_ROUTE_NAME = 'execute_risk_workflow_action';
    const RISK_ACTION_ROUTE_CONTROLLER = 'RZP\Http\Controllers\MerchantController@executeRiskAction';

    const MERCHANTS_STATUS_COMMENT_TPL = 'Merchants Status After Workflow Actions Execution:%s';
    // Statuses
    const APPROVED    = 'approved';
    const INVALIDATED = 'invalidated';
    const FAILED      = 'failed';

    const BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE = 'BULK_RISK_ACTION_WORKFLOW_TRIGGER';

    const BATCH_STATUS_TPL = 'BULK_WORKFLOW_ACTION_BATCH_STATUS:https://admin-dashboard.razorpay.com/admin/entity/batch.service/live/%s';

    const BULK_WORKFLOW_COMPLETED_TAG   = 'bulk_workflow_completed';
    const BULK_WORKFLOW_IN_PROGRESS_TAG = 'bulk_workflow_in_progress';

    const RISK_ATTRIBUTES        = 'risk_attributes';

    const RISK_CONSTRUCTIVE_ACTION_PERMISSION_ERROR_MESSAGE = 'Merchant is tagged by risk team hence constructive action can be performed on this only by risk team';
}
