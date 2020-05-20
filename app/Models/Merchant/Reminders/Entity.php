<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const REMINDER_ID            = 'reminder_id';
    const REMINDER_STATUS        = 'reminder_status';
    const REMINDER_NAMESPACE     = 'reminder_namespace';
    const REMINDER_COUNT         = 'reminder_count';

    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';

    protected $entity            = 'merchant_reminders';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::REMINDER_ID,
        self::REMINDER_STATUS,
        self::REMINDER_NAMESPACE,
        self::REMINDER_COUNT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::REMINDER_ID,
        self::REMINDER_STATUS,
        self::REMINDER_NAMESPACE,
        self::REMINDER_COUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::REMINDER_ID,
        self::REMINDER_STATUS,
        self::REMINDER_NAMESPACE,
        self::REMINDER_COUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::REMINDER_STATUS       => Status::PENDING,
        self::REMINDER_NAMESPACE    => '',
        self::REMINDER_COUNT        => 0,
    ];

    protected $casts = [
        self::REMINDER_COUNT => 'integer',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function getReminderNamespace()
    {
        return $this->getAttribute(self::REMINDER_NAMESPACE);
    }

    public function getReminderStatus()
    {
        return $this->getAttribute(self::REMINDER_STATUS);
    }

    public function getReminderCount()
    {
        return $this->getAttribute(self::REMINDER_COUNT);
    }

    public function getReminderId()
    {
        return $this->getAttribute(self::REMINDER_ID);
    }

    public function setReminderId($id)
    {
        $this->setAttribute(self::REMINDER_ID, $id);
    }

    public function setReminderNamespace(string $namespace)
    {
        $this->setAttribute(self::REMINDER_NAMESPACE, $namespace);
    }

    public function setReminderStatus($status)
    {
        if ($status !== null)
        {
            Status::checkStatus($status);
        }

        $this->setAttribute(self::REMINDER_STATUS, $status);
    }

    public function setReminderCount(string $reminderCount)
    {
        $this->setAttribute(self::REMINDER_COUNT, $reminderCount);
    }
}
