<?php

namespace RZP\Models\IdempotencyKey;

use Hash;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const IDEMPOTENCY_KEY   = 'idempotency_key';
    const MERCHANT_ID       = 'merchant_id';
    const REQUEST_ID        = 'request_id';
    const REQUEST_HASH      = 'request_hash';
    const RESPONSE          = 'response';
    const STATUS_CODE       = 'status_code';
    const SOURCE_ID         = 'source_id';
    const SOURCE_TYPE       = 'source_type';

    const HEADER_KEY        = 'header_key';

    const SOURCE            = 'source';

    protected $generateIdOnCreate = true;

    protected $entity = 'idempotency_key';

    protected static $sign = 'ikey';

    protected $fillable = [
        self::IDEMPOTENCY_KEY,
        self::REQUEST_HASH,
        self::SOURCE_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::IDEMPOTENCY_KEY,
        self::MERCHANT_ID,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::REQUEST_HASH,
        self::RESPONSE,
        self::STATUS_CODE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::IDEMPOTENCY_KEY,
        self::REQUEST_HASH,
        self::RESPONSE,
        self::STATUS_CODE,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::CREATED_AT,
    ];

    protected $ignoredRelations = [
        self::SOURCE,
    ];

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    // ============================= END RELATIONS =============================

    // ============================= GETTERS =============================

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getRequestHash()
    {
        return $this->getAttribute(self::REQUEST_HASH);
    }

    public function getIdempotencyKey()
    {
        return $this->getAttribute(self::IDEMPOTENCY_KEY);
    }

    public function getRequestHashFromRequestBody($requestBody)
    {
        return Hash::make($requestBody);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setRequestHash(string $requestHash)
    {
        $this->setAttribute(self::REQUEST_HASH, $requestHash);
    }

    public function setIdempotencyKey(string $idempotencyKey)
    {
        $this->setAttribute(self::IDEMPOTENCY_KEY, $idempotencyKey);
    }

    public function setSourceType(string $sourceType)
    {
        $this->setAttribute(self::IDEMPOTENCY_KEY, $sourceType);
    }

    public function setSourceId(string $sourceId)
    {
        $this->setAttribute(self::SOURCE_ID, $sourceId);
    }

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setResponse($response)
    {
        $this->setAttribute(self::RESPONSE, $response);
    }

    public function setStatusCode($statusCode)
    {
        $this->setAttribute(self::STATUS_CODE, $statusCode);
    }

    // ============================= END SETTERS =============================

    // ============================= MUTATORS =============================

    // ============================= END MUTATORS =============================

    // ============================= ACCESSORS =============================

    // ============================= END ACCESSORS =============================

    // ============================= PUBLIC SETTERS =============================

    // ============================= END PUBLIC SETTERS =============================

    // ============================= MODIFIERS =============================

    // ============================= END MODIFIERS =============================
}
