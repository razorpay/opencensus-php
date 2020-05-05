<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * @param array                       $input
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param Entity                      $reminderEntity
     *
     * @return Entity
     */
    public function createOrUpdate(array $input, Merchant\Entity $merchant, $reminderEntity = null): Entity
    {
        if (empty($reminderEntity) === true)
        {
            $reminderEntity = $this->create($input, $merchant);
        }
        else
        {
            $reminderEntity->setReminderStatus($input[Entity::REMINDER_STATUS]);

            $this->repo->saveOrFail($reminderEntity);
        }

        return $reminderEntity;
    }

    protected function create(array $input, Merchant\Entity $merchant): Entity
    {
        $reminder = (new Entity)->build($input);

        $reminder->merchant()->associate($merchant);

        $this->repo->saveOrFail($reminder);

        return $reminder;
    }
}
