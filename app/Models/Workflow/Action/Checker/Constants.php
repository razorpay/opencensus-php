<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Admin\Permission;

class Constants
{
    const SKIP_CHECKER_STRICT_VALIDATION_FOR_PERMISSIONS = [
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL,
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
    ];
}
