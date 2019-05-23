<?php

namespace RZP\Models\Batch;

use RZP\Models\Batch;

class ResponseEntity extends Batch\Entity
{
    const ENTITY_ID     = "entity_id";
    const BATCH_TYPE_ID = "batch_type_id";
    const IS_SCHEDULED  = "is_scheduled";
    const UPLOAD_COUNT  = "upload_count";
    const FAILURE_COUNT = "failure_count";


    protected $public = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ID,
        self::ENTITY_ID,
        self::NAME,
        self::BATCH_TYPE_ID,
        self::TYPE,
        self::IS_SCHEDULED,
        self::UPLOAD_COUNT,
        self::ENTITY,
        self::PROCESSED_COUNT,
        self::FAILURE_COUNT,
        self::TOTAL_COUNT,
        self::SUCCESS_COUNT,
        self::ATTEMPTS,
        self::STATUS,
        self::AMOUNT,
        self::PROCESSED_AMOUNT,
    ];

    protected $fillable = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ID,
        self::ENTITY_ID,
        self::ENTITY,
        self::NAME,
        self::BATCH_TYPE_ID,
        self::TYPE,
        self::IS_SCHEDULED,
        self::UPLOAD_COUNT,
        self::PROCESSED_COUNT,
        self::FAILURE_COUNT,
        self::TOTAL_COUNT,
        self::SUCCESS_COUNT,
        self::ATTEMPTS,
        self::STATUS,
        self::AMOUNT,
        self::PROCESSED_AMOUNT,
    ];

    public function getPublicId()
    {
        return $this->getKey();
    }
}
