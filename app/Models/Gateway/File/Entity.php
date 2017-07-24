<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;
use RZP\Models\FileStore;

class Entity extends Base\PublicEntity
{
    const TYPE                = 'type';
    const GATEWAY             = 'gateway';
    const FILE_ID             = 'file_id';
    const SENDER              = 'sender';
    const RECIPIENTS          = 'recipients';
    const FROM                = 'from';
    const TO                  = 'to';
    const STATUS              = 'status';
    const FAILURE_CODE        = 'failure_code';
    const SCHEDULED           = 'scheduled';
    const PARTIALLY_PROCESSED = 'partially_processed';
    const ATTEMPTS            = 'attempts';
    const FAILED_AT           = 'failed_at';
    const FILE_GENERATED_AT   = 'file_generated_at';
    const SENT_AT             = 'sent_at';
    const ACKNOWLEDGED_AT     = 'acknowledged_at';

    protected $entity = 'gateway_file';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TYPE,
        self::GATEWAY,
        self::SENDER,
        self::RECIPIENTS,
        self::FROM,
        self::TO,
        self::SCHEDULED,
    ];

    protected $visible = [
        self::ID,
        self::TYPE,
        self::GATEWAY,
        self::FILE_ID,
        self::SENDER,
        self::RECIPIENTS,
        self::FROM,
        self::TO,
        self::STATUS,
        self::FAILURE_CODE,
        self::SCHEDULED,
        self::PARTIALLY_PROCESSED,
        self::ATTEMPTS,
        self::FAILED_AT,
        self::FILE_GENERATED_AT,
        self::SENT_AT,
        self::ACKNOWLEDGED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $casts = [
        self::RECIPIENTS          => 'array',
        self::SCHEDULED           => 'boolean',
        self::PARTIALLY_PROCESSED => 'boolean'
    ];

    protected $defaults = [
        self::STATUS              => 'created',
        self::SCHEDULED           => 1,
        self::PARTIALLY_PROCESSED => 0,
        self::ATTEMPTS            => 0,
        self::SENDER              => 'refunds@razorpay.com'
    ];

    public function files()
    {
        return $this->morphMany(FileStore\Entity::class, 'entity');
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getSender()
    {
        return $this->getAttribute(self::SENDER);
    }

    public function getRecipients()
    {
        return $this->getAttribute(self::RECIPIENTS);
    }

    public function getFrom()
    {
        return $this->getAttribute(self::FROM);
    }

    public function getTo()
    {
        return $this->getAttribute(self::TO);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function isAcknowledged(): bool
    {
        return ($this->getStatus() === Status::ACKNOWLEDGED);
    }

    public function isFileGenerated(): bool
    {
        return (($this->isAttributeNotNull(self::FILE_GENERATED_AT) === true) and
                ($this->files !== null));
    }

    public function isMailSent(): bool
    {
        return ($this->isAttributeNotNull(self::SENT_AT) === true);
    }

    public function isFailed(): bool
    {
        return (($this->getStatus() === Status::FAILED) and
                ($this->isAttributeNotNull(self::FAILED_AT)));
    }

    public function getFailureCode()
    {
        return $this->getAttribute(self::FAILURE_CODE);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setFailedAt(int $failedAt)
    {
        $this->setAttribute(self::FAILED_AT, $failedAt);
    }

    public function setFileGeneratedAt(int $generatedAt)
    {
        $this->setAttribute(self::FILE_GENERATED_AT, $generatedAt);
    }

    public function setMailSentAt(int $mailSentAt)
    {
        $this->setAttribute(self::SENT_AT, $mailSentAt);
    }

    public function setFailureCode(string $failureCode)
    {
        $this->setAttribute(self::FAILURE_CODE, $failureCode);
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    public function getRecipientsAttribute()
    {
        $recipients = $this->attributes[self::RECIPIENTS];

        if (empty($recipients) === true)
        {
            return [];
        }

        return json_decode($recipients, true);
    }
}
