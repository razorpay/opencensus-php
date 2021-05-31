<?php

namespace RZP\Models\CardMandate\MandateHubs;

class Notification
{
    const NOTIFICATION_ID = 'notification_id';
    const STATUS          = 'status';
    const NOTIFIED_AT     = 'notified_at';

    protected $attributes = [];

    function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value)
        {
            $this->setAttribute($key, $value);
        }
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function getId(): string {
        return $this->getAttribute(self::NOTIFICATION_ID);
    }

    public function getStatus(): string {
        return $this->getAttribute(self::STATUS);
    }

    public function getNotifiedAt(): string {
        return $this->getAttribute(self::NOTIFIED_AT);
    }
}
