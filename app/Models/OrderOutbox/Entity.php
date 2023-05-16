<?php

namespace RZP\Models\OrderOutbox;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                = 'id';
    const ORDER_ID          = 'order_id';
    const MERCHANT_ID       = 'merchant_id';
    const EVENT_NAME        = 'event_name';
    const PAYLOAD           = 'payload';
    const RETRY_COUNT       = 'retry_count';
    const IS_DELETED        = 'is_deleted';
    const DELETED_AT        = 'deleted_at';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    protected $generateIdOnCreate = true;

    protected $entity = 'order_outbox';

    protected $fillable = [
        self::ID,
        self::ORDER_ID,
        self::MERCHANT_ID,
        self::EVENT_NAME,
        self::PAYLOAD,
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
        self::RETRY_COUNT   => 0,
        self::IS_DELETED    => 0,
        self::DELETED_AT    => null,
    ];

    // Setters
    public function setId($id)
    {
        $this->setAttribute(self::ID, $id);
    }

    public function setOrderId($orderId)
    {
        $this->setAttribute(self::ORDER_ID, $orderId);
    }

    public function setEventName(string $eventName)
    {
        $this->setAttribute(self::EVENT_NAME, $eventName);
    }

    public function setPayloadSerialized(string $payload)
    {
        $this->setAttribute(self::PAYLOAD, $payload);
    }

    public function setRetryCount(int $retryCount)
    {
        $this->setAttribute(self::RETRY_COUNT, $retryCount);
    }

    public function setDeletedAt($deletedAt)
    {
        $this->setAttribute(self::DELETED_AT, $deletedAt);
    }

    public function setIsDeleted(bool $isDeleted)
    {
        $this->setAttribute(self::IS_DELETED, $isDeleted);
    }

    // ----------------------- Accessor --------------------------------------------
    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getEventName()
    {
        return $this->getAttribute(self::EVENT_NAME);
    }

    public function getPayload()
    {
        return $this->getAttribute(self::PAYLOAD);
    }

    public function getRetryCount()
    {
        return $this->getAttribute(self::RETRY_COUNT);
    }

    public function isDeleted(): bool
    {
        return $this->getAttribute(self::IS_DELETED);
    }

    public function reload()
    {
        $entity = Entity::withTrashed()->findOrFail($this->{$this->primaryKey});

        $this->attributes = $entity->attributes;

        $this->original = $entity->original;

        return $this;
    }
}
