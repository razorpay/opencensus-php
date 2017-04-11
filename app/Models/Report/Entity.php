<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const START_TIME    = 'start_time';
    const END_TIME      = 'end_time';
    const FILE_ID       = 'file_id';
    const TYPE          = 'type';
    const MERCHANT_ID   = 'merchant_id';
    const GENERATED_AT  = 'generated_at';
    const GENERATED_BY  = 'generated_by';

    protected $entity = 'report';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TYPE,
        self::START_TIME,
        self::END_TIME,
        self::GENERATED_BY
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TYPE,
        self::START_TIME,
        self::END_TIME,
        self::FILE_ID,
        self::GENERATED_AT,
        self::GENERATED_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::GENERATED_AT,
        self::GENERATED_BY
    ];

    protected $dates = [
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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    // ----------------------------------- GETTERS END --------------------------------
}
