<?php

namespace RZP\Models\LedgerOutbox;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                    = 'id';
    const PAYLOAD_NAME          = 'payload_name';
    const PAYLOAD_SERIALIZED    = 'payload_serialized';
    const IS_ENCRYPTED          = 'is_encrypted';
    const PRIORITY              = 'priority';
    const RETRY_COUNT           = 'retry_count';
    const IS_DELETED            = 'is_deleted';
    const DELETED_AT            = 'deleted_at';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    protected $entity = 'ledger_outbox';

    protected $fillable = [
        self::ID,
        self::PAYLOAD_NAME,
        self::PAYLOAD_SERIALIZED,
        self::IS_ENCRYPTED,
        self::PRIORITY,
        self::RETRY_COUNT,
        self::IS_DELETED,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
    ];

    protected $primaryKey = self::ID;

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults = [
        self::IS_ENCRYPTED  => 0,
        self::IS_DELETED    => 0,
        self::RETRY_COUNT   => 0,
        self::DELETED_AT    => null,
    ];

    // Setters
    public function setId($id)
    {
        $this->setAttribute(self::ID, $id);
    }

    public function setPayloadName(string $payloadName)
    {
        $this->setAttribute(self::PAYLOAD_NAME, $payloadName);
    }

    public function getPayloadName()
    {
        return $this->getAttribute(self::PAYLOAD_NAME);
    }

    public function setPayloadSerialized(string $payloadSerialized)
    {
        $this->setAttribute(self::PAYLOAD_SERIALIZED, $payloadSerialized);
    }

    public function setRetryCount(int $retryCount)
    {
        $this->setAttribute(self::RETRY_COUNT, $retryCount);
    }

    public function setDeletedAt( $deletedAt)
    {
        $this->setAttribute(self::DELETED_AT, $deletedAt);
    }

    public function setIsDeleted(int $isDeleted)
    {
        $this->setAttribute(self::IS_DELETED, $isDeleted);
    }

    // Getters
    public function isDeleted(): bool
    {
        return $this->getAttribute(self::IS_DELETED);
    }

    public function getRetryCount(): int
    {
        return $this->getAttribute(self::RETRY_COUNT);
    }

    public function getPayloadSerialized()
    {
        return $this->getAttribute(self::PAYLOAD_SERIALIZED);
    }
}
