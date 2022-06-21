<?php

namespace RZP\Models\AMPEmail;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const ID          = 'id';
    const METADATA    = 'metadata';
    const VENDOR      = 'vendor';
    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID   = 'entity_id';
    const STATUS      = 'status';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';
    const TEMPLATE    = 'template';

    protected $primaryKey = self::ID;

    protected $entity     = 'amp_email';

    protected $fillable   = [
        self::ID,
        self::METADATA,
        self::VENDOR,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::STATUS,
        self::TEMPLATE
    ];

    protected $public     = [
        self::ID,
        self::METADATA,
        self::VENDOR,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TEMPLATE
    ];

    protected $casts      = [
        self::METADATA => 'array',
    ];

    protected $defaults      = [
        self::METADATA => [],
    ];

    public function getId(): string
    {
        return $this->getAttribute(self::ID) ?? '';
    }

    public function getMetaData(): array
    {
        if(empty($this->getAttribute(self::METADATA))===true) return [];
        return $this->getAttribute(self::METADATA);
    }

    public function getVendor(): string
    {
        return $this->getAttribute(self::VENDOR);
    }

    public function getEntityId(): string
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType(): string
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getTemplate(): string
    {
        return $this->getAttribute(self::TEMPLATE);
    }

    public function setStatus(string $status): string
    {
        return $this->setAttribute(self::STATUS, $status);
    }
}
