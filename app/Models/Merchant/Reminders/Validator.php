<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Base;


/**
 * Class Validator
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::REMINDER_NAMESPACE  => 'string|max:255',
        Entity::REMINDER_STATUS     => 'string|max:255',
        Entity::REMINDER_COUNT      => 'integer'
    ];

    /**
     * @param string $attribute
     * @param string $reminderStatus
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateReminderStatus(string $attribute, string $reminderStatus)
    {
        if (empty($reminderStatus) === false)
        {
            Status::checkStatus($reminderStatus);
        }
    }

}
