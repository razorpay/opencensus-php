<?php

namespace RZP\Models\Invoice\Reminder;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\CreditNote;
use RZP\Models\Payment\Refund;

class Core extends Base\Core
{
    public function create(
        array $input,
        Invoice\Entity $invoice): Entity
    {
        $this->trace->info(TraceCode::REMINDER_CREATE_REQUEST, $input);

        $reminder = (new Entity)->build($input);

        $reminder->invoice()->associate($invoice);

        $this->repo->saveOrFail($reminder);

        return $reminder;
    }

    /**
     * @param array                      $input
     * @param \RZP\Models\Invoice\Entity $invoice
     * @param Entity                     $reminderEntity
     *
     * @return Entity
     */
    public function createOrUpdate(array $input, Invoice\Entity $invoice, $reminderEntity = null): Entity
    {
        if (empty($reminderEntity) === true)
        {
            $reminderEntity = $this->create($input, $invoice);
        }
        else
        {
            $reminderEntity->setReminderStatus($input[Entity::REMINDER_STATUS]);

            $this->repo->saveOrFail($reminderEntity);
        }

        return $reminderEntity;
    }
}
