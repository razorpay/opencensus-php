<?php

namespace RZP\Models\Payout\Notifications;

use RZP\Models\Base\Core;

class Base extends Core
{
    public function notify()
    {
        $this->sendSms();

        $this->sendEmail();
    }
}
