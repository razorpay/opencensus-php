<?php

namespace RZP\Services\Mock;

use RZP\Services\Reminders as BaseReminders;

class Reminders extends BaseReminders
{
    public function createReminder(array $input, string $merchantId = null): array
    {
        return [self::REMINDER_ID => self::TEST_REMINDER_ID];
    }

    public function deleteReminder(string $id, string $merchantId = null): array
    {
        return ['success' => true];
    }

    public function getReminderSettings(array $input)
    {
        $response = [
            'count'  => 1,
            'entity' => 'merchant_settings',
            'items'  => [[
                'namespace'          => 'payment_link',
                'merchant_id'        => '10000000000000',
                'active'             => true,
                'max_reminder_count' => 3,
                'id'                 => 'DiApbkyVNQye22',
                'created_at'         => 1574161637,
                'updated_at'         => 1574161637,
            ]],
        ];

        return $response;
    }
}
