<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Admin\Permission;

class Constants
{
    const AUTO_CLOSE_WF_NAMES_ON_NC = [
        Permission\Name::EDIT_ACTIVATE_MERCHANT,
        Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH
    ];
}
