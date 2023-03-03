<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Admin\Permission;

class Constants
{
    const EDIT_ACTIVATE_MERCHANT  = 'edit_activate_merchant';

    const APPROVED_WITH_FEEDBACK = 'approved_with_feedback';

    const SKIP_CHECKER_STRICT_VALIDATION_FOR_PERMISSIONS = [
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL,
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
        Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED,
    ];
}
