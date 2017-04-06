<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const START_TIME    = 'start_time';
    const END_TIME      = 'end_time';
    const FILE_ID       = 'file_id';
    const ENTITY        = 'entity';
    const MERCHANT_ID   = 'merchant_id';
    const GENERATED_AT  = 'generated_at';

    protected $entity = 'report';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::START_TIME,
        self::END_TIME,
        self::FILE_ID,
        self::ENTITY,
    ];

    protected $visible = [
        self::ID,
        self::START_TIME,
        self::END_TIME,
        self::FILE_ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GENERATED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::START_TIME,
        self::END_TIME,
        self::FILE_ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GENERATED_AT,
    ];

    // ----------------------------------- RELATIONS ---------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // ----------------------------------- RELATIONS END -----------------------------

    // ----------------------------------- SETTERS -----------------------------------

    public function setGeneratedAt($time)
    {
        $this->setAttribute(self::GENERATED_AT, $time);
    }

    public function setFileId($fileId)
    {
        $this->setAttribute(self::FILE_ID, $fileId);
    }

    // ----------------------------------- SETTERS END -------------------------------

    // ----------------------------------- GETTERS -----------------------------------

    public function getFileId()
    {
        return $this->getAttribute(self::FILE_ID);
    }

    public function getEntity()
    {
        return $this->getAttribute(self::ENTITY);
    }

    // ----------------------------------- GETTERS END --------------------------------
}
