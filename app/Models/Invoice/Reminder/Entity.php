<?php

namespace RZP\Models\Invoice\Reminder;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const INVOICE_ID        = 'invoice_id';
    const REMINDER_ID       = 'reminder_id';
    const REMINDER_STATUS   = 'reminder_status';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $entity = 'invoice_reminder';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::REMINDER_ID,
        self::REMINDER_STATUS,
    ];

    protected $visible = [
        self::ID,
        self::INVOICE_ID,
        self::REMINDER_ID,
        self::REMINDER_STATUS,
    ];

    protected $public = [
        self::REMINDER_STATUS,
    ];

    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }

    public function getInvoiceId()
    {
        return $this->getAttribute(self::INVOICE_ID);
    }

    public function getReminderStatus()
    {
        return $this->getAttribute(self::REMINDER_STATUS);
    }

    public function getReminderId()
    {
        return $this->getAttribute(self::REMINDER_ID);
    }

    public function setReminderId($id)
    {
        $this->setAttribute(self::REMINDER_ID, $id);
    }

    public function setReminderStatus($status)
    {
        if ($status !== null)
        {
            Status::checkStatus($status);
        }

        $this->setAttribute(self::REMINDER_STATUS, $status);
    }
}
