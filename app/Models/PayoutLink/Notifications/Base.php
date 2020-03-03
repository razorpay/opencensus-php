<?php

namespace RZP\Models\PayoutLink\Notifications;

use RZP\Models\Base\Core;

abstract class Base extends Core
{
    public function notify()
    {
        $this->sendSms();

        $this->sendEmail();
    }
}
