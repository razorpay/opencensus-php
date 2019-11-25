<?php

namespace RZP\Models\Invoice\Reminder;

use RZP\Base;


/**
 * Class Validator
 *
 * @package RZP\Models\Invoice
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::REMINDER_STATUS => 'string|max:255|custom'
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
