<?php

namespace RZP\Services\Mock;

use RZP\Services\Reminders as BaseReminders;

class Reminders extends BaseReminders
{

    public function createReminder(array $input, string $merchantId = null): array
    {
        return [self::REMINDER_ID => self::TEST_REMINDER_ID];
    }
}
