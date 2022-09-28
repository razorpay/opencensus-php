<?php


namespace RZP\Models\Merchant\PaymentLimit;


use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Action;

class Constants
{
    const BULK_MAX_PAYMENT_WORKFLOW_ACTION_ROUTE_NAME = 'execute_max_payment_limit_workflow';
    const BULK_MAX_PAYMENT_WORKFLOW_ACTION_ROUTE_CONTROLLER = '\RZP\Http\Controllers\MerchantController@executeMaxPaymentLimitWorkflow';

    const BULK_MAX_PAYMENT_WORKFLOW_ACTION_PERMISSION_NAME = [
        Action::UPDATE_MAX_PAYMENT_LIMIT            => Permission::EXECUTE_MERCHANT_MAX_PAYMENT_LIMIT_WORKFLOW
    ];

    const MERCHANT_ID_KEY = 'merchant_id';
    const MAX_PAYMENT_LIMIT_KEY = 'max_payment_amount';
    const MAX_INTERNATIONAL_PAYMENT_LIMIT_KEY = 'max_international_payment_amount';

    const FILE = 'file';
    const MAX_PAYMENT_LIMIT_OUTPUT = 'max_payment_limit';

    // Signed URL is valid for a week
    const SIGNED_URL_DURATION = '10080';

}
