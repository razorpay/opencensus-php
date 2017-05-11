<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const TYPE          = 'type';
    const DAY           = 'day';
    const MONTH         = 'month';
    const YEAR          = 'year';
    const START_TIME    = 'start_time';
    const END_TIME      = 'end_time';
    const FILE_ID       = 'file_id';
    const MERCHANT_ID   = 'merchant_id';
    const GENERATED_AT  = 'generated_at';
    const GENERATED_BY  = 'generated_by';

    protected $entity   = 'report';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TYPE,
        self::DAY,
        self::MONTH,
        self::YEAR,
        self::START_TIME,
        self::END_TIME,
        self::GENERATED_BY,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TYPE,
        self::DAY,
        self::MONTH,
        self::YEAR,
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
        self::DAY,
        self::MONTH,
        self::YEAR,
        self::TYPE,
        self::GENERATED_AT,
        self::GENERATED_BY
    ];

    protected $dates = [
        self::GENERATED_AT,
    ];

    protected $casts = [
        self::DAY   => 'int',
        self::MONTH => 'int',
        self::YEAR  => 'int',
    ];

    // ----------------------------------- RELATIONS ---------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function file()
    {
        return $this->belongsTo('RZP\Models\FileStore\Entity');
    }

    // ----------------------------------- RELATIONS END -----------------------------

    // ----------------------------------- SETTERS -----------------------------------

    public function setGeneratedAt($time)
    {
        $this->setAttribute(self::GENERATED_AT, $time);
    }

    // ----------------------------------- SETTERS END -------------------------------
}
