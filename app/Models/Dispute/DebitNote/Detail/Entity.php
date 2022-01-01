<?php

namespace RZP\Models\Dispute\DebitNote\Detail;

use RZP\Models\Base;
use RZP\Constants as RZPConstants;

class Entity extends Base\PublicEntity
{
    const DEBIT_NOTE_ID = 'debit_note_id';
    const DETAIL_TYPE   = 'detail_type';
    const DETAIL_ID     = 'detail_id';


    protected $fillable = [
        self::DEBIT_NOTE_ID,
        self::DETAIL_TYPE,
        self::DETAIL_ID,
    ];

    protected $visible = [
        self::ID,
        self::DETAIL_TYPE,
        self::DETAIL_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $entity = RZPConstants\Entity::DEBIT_NOTE_DETAIL;

    protected $generateIdOnCreate = true;


}