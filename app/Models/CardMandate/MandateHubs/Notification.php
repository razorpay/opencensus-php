<?php

namespace RZP\Models\CardMandate\MandateHubs;

class Notification
{
    const NOTIFICATION_ID  = 'notification_id';
    const STATUS           = 'status';
    const NOTIFIED_AT      = 'notified_at';
    const AFA_REQUIRED     = 'afa_required';
    const AFA_STATUS       = 'afa_status';
    const AFA_COMPLETED_AT = 'afa_completed_at';
    const AMOUNT           = 'amount';
    const CURRENCY         = 'currency';
    const PURPOSE          = 'purpose';
    const NOTES            = 'notes';

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

    public function getAmount(): string {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency(): string {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getPurpose(): string {
        return $this->getAttribute(self::PURPOSE);
    }

    public function getNotes(): string {
        return $this->getAttribute(self::NOTES);
    }

    public function getStatus(): string {
        return $this->getAttribute(self::STATUS);
    }

    public function getNotifiedAt(): string {
        return $this->getAttribute(self::NOTIFIED_AT);
    }

    public function getAfaRequired() {
        return $this->getAttribute(self::AFA_REQUIRED);
    }

    public function getAfaStatus(): string {
        return $this->getAttribute(self::AFA_STATUS);
    }

    public function getAfaCompletedAt(): string {
        return $this->getAttribute(self::AFA_COMPLETED_AT);
    }
}
