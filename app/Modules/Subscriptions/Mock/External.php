<?php

namespace RZP\Modules\Subscriptions\Mock;

use RZP\Modules\Subscriptions\External as Base;

class External extends Base
{
    public function __construct($request)
    {
        $this->request = $request;

        parent::__construct();
    }
}
