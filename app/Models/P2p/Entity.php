<?php

namespace RZP\Models\P2p;

use Carbon\Carbon;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                  = 'id';
    const SOURCE_ID           = 'source_id';
    const SOURCE_TYPE         = 'source_type';
    const SINK_ID             = 'sink_id';
    const SINK_TYPE           = 'sink_type';
    const STATUS              = 'status';
    const MERCHANT_ID         = 'merchant_id';
    const AMOUNT              = 'amount';
    const DESCRIPTION         = 'description';
    const TYPE                = 'type';
    const GATEWAY             = 'gateway';
    const NOTES               = 'notes';
    const CURRENCY            = 'currency';
    const INTERNAL_ERROR_CODE = 'internal_error_code';
    const ERROR_DESCRIPTION   = 'error_description';
    const ERROR_CODE          = 'error_code';

    protected $fillable = array(
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::SINK_ID,
        self::SINK_TYPE,
        self::STATUS,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::DESCRIPTION,
        self::TYPE,
        self::GATEWAY,
        self::NOTES,
        self::CURRENCY,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ERROR_CODE,
    );

    protected $entity = 'p2p';

}
