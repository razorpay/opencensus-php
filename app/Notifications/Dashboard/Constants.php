<?php

namespace RZP\Notifications\Dashboard;

use RZP\Models\Admin\Permission\Name as PermissionName;

class Constants
{
    const PARAMS                                  = 'params';

    const PREVIOUS_BUSINESS_WEBSITE               = 'previous_business_website';

    const UPDATED_BUSINESS_WEBSITE                = 'updated_business_website';

    const MESSAGE_BODY                            = 'messageBody';

    const MESSAGE_SUBJECT                         = 'messageSubject';

    const NAME                                    = 'name';

    const MERCHANT_NAME                           = 'merchant_name';

    const BENEFICIARY_NAME                        = 'beneficiary_name';

    const ACCOUNT_NUMBER                          = 'account_number';

    const IFSC_CODE                               = 'ifsc_code';

    const UPDATED_TRANSACTION_LIMIT               = 'updated_transaction_limit';

    const WORKFLOW_CLARIFICATION_SUBMIT_LINK      = 'workflow_clarification_submit_link';

    const MAX_PAYMENT_AMOUNT                      = 'max_payment_amount';

    const WORKFLOW_PERMISSION_VS_NEEDS_CLARIFICATION_EVENT = [
        PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL  => Events::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW,
        PermissionName::UPDATE_MERCHANT_WEBSITE       => Events::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW,
        PermissionName::INCREASE_TRANSACTION_LIMIT    => Events::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW,
        PermissionName::EDIT_MERCHANT_BANK_DETAIL     => Events::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW,
    ];

    const EVENT_VS_WORKFLOW_CLARIFICATION_SUBMIT_LINK   = [
        Events::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW              => 'https://dashboard.razorpay.com/app/profile/clarification_add_website',
        Events::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW           => 'https://dashboard.razorpay.com/app/profile/clarification_update_website',
        Events::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW => 'https://dashboard.razorpay.com/app/profile/clarification_increase_transaction_limit',
        Events::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW      => 'https://dashboard.razorpay.com/app/profile/clarification_update_bank_account'
    ];
}
