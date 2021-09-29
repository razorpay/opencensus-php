<?php

namespace RZP\Models\TrustedBadge\TrustedBadgeHistory;

use RZP\Models\TrustedBadge\TrustedBadgeHistory\Core;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

    }
}
