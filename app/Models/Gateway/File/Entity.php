<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Entity extends Base\PublicEntity
{
    const ID                  = 'id';
    const TYPE                = 'type';
    const TARGET              = 'target';
    const SUB_TYPE            = 'sub_type';
    const SENDER              = 'sender';
    const RECIPIENTS          = 'recipients';
    const BEGIN               = 'begin';
    const END                 = 'end';
    const STATUS              = 'status';
    const PARTIALLY_PROCESSED = 'partially_processed';
    const COMMENTS            = 'comments';
    const SCHEDULED           = 'scheduled';
    const ATTEMPTS            = 'attempts';
    const ERROR_CODE          = 'error_code';
    const ERROR_DESCRIPTION   = 'error_description';
    const FILE_GENERATED_AT   = 'file_generated_at';
    const SENT_AT             = 'sent_at';
    const ACKNOWLEDGED_AT     = 'acknowledged_at';
    const FAILED_AT           = 'failed_at';

    protected $entity = 'gateway_file';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TYPE,
        self::TARGET,
        self::SUB_TYPE,
        self::SENDER,
        self::RECIPIENTS,
        self::COMMENTS,
        self::BEGIN,
        self::END,
        self::SCHEDULED,
        self::PARTIALLY_PROCESSED,
    ];

    protected $visible = [
        self::ID,
        self::TYPE,
        self::TARGET,
        self::SUB_TYPE,
        self::SENDER,
        self::RECIPIENTS,
        self::BEGIN,
        self::END,
        self::STATUS,
        self::PARTIALLY_PROCESSED,
        self::COMMENTS,
        self::SCHEDULED,
        self::ATTEMPTS,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::FILE_GENERATED_AT,
        self::SENT_AT,
        self::ACKNOWLEDGED_AT,
        self::FAILED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $casts = [
        self::ATTEMPTS            => 'int',
        self::RECIPIENTS          => 'array',
        self::SCHEDULED           => 'boolean',
        self::PARTIALLY_PROCESSED => 'boolean'
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SENT_AT,
        self::FAILED_AT,
        self::ACKNOWLEDGED_AT,
        self::FILE_GENERATED_AT,
        self::BEGIN,
        self::END,
    ];

    protected $defaults = [
        self::STATUS              => Status::CREATED,
        self::SCHEDULED           => 1,
        self::PARTIALLY_PROCESSED => 0,
        self::ATTEMPTS            => 0,
    ];

    protected static $generators = [
        self::SENDER,
        self::RECIPIENTS,
    ];

    // --------------------------GENERATORS-------------------------------------
    protected function generateSender(array $input)
    {
        if (empty($input[self::SENDER]) === true)
        {
            $sender = Constants::TYPE_SENDER_MAPPING[$input[self::TYPE]];

            $this->setAttribute(self::SENDER, $sender);
        }
    }

    protected function generateRecipients(array $input)
    {
        if (empty($input[self::RECIPIENTS]) === true)
        {
            $recipients = Constants::RECIPIENTS_MAP[$input[Entity::TYPE]][$input[Entity::TARGET]];

            $this->setAttribute(self::RECIPIENTS, $recipients);
        }
    }

    // -------------------------GENERATORS END----------------------------------

    // -----------------------------GETTERS-------------------------------------
    public function files()
    {
        return $this->morphMany(FileStore\Entity::class, 'entity');
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getTarget()
    {
        return $this->getAttribute(self::TARGET);
    }

    public function getSender()
    {
        return $this->getAttribute(self::SENDER);
    }

    public function getRecipients()
    {
        return $this->getAttribute(self::RECIPIENTS);
    }

    public function getBegin()
    {
        return $this->getAttribute(self::BEGIN);
    }

    public function getEnd()
    {
        return $this->getAttribute(self::END);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getSubType()
    {
        return $this->getAttribute(self::SUB_TYPE);
    }

    public function isAcknowledged(): bool
    {
        return ($this->getStatus() === Status::ACKNOWLEDGED);
    }

    public function isFileGenerated(): bool
    {
        return ($this->getStatus() === Status::FILE_GENERATED);
    }

    public function isFileSent(): bool
    {
        return ($this->getStatus() === Status::FILE_SENT);
    }

    public function isFailed(): bool
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    // -----------------------------GETTERS END---------------------------------

    // -----------------------------SETTERS-------------------------------------

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAcknowledgedAt(int $acknowledgedAt)
    {
        $this->setAttribute(self::ACKNOWLEDGED_AT, $acknowledgedAt);
    }

    public function setComments(string $comments)
    {
        $this->setAttribute(self::COMMENTS, $comments);
    }

    public function setFailedAt(int $failedAt)
    {
        $this->setAttribute(self::FAILED_AT, $failedAt);
    }

    public function setFileGeneratedAt(int $generatedAt)
    {
        $this->setAttribute(self::FILE_GENERATED_AT, $generatedAt);
    }

    public function setFileSentAt(int $mailSentAt)
    {
        $this->setAttribute(self::SENT_AT, $mailSentAt);
    }

    public function setErrorCode(string $errorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    public function setErrorDescription(string $errorDescription)
    {
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDescription);
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    //-----------------------------SETTERS END----------------------------------

    public function getRecipientsAttribute()
    {
        if (isset($this->attributes[self::RECIPIENTS]) === false)
        {
            return [];
        }

        return json_decode($this->attributes[self::RECIPIENTS], true);
    }
}
