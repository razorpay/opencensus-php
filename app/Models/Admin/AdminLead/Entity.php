<?php

namespace RZP\Models\Admin\AdminLead;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Admin\Base;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const ADMIN_ID          = 'admin_id';
    const TOKEN             = 'token';
    const EMAIL             = 'email';
    const FORM_DATA         = 'form_data';

    const DELETED_AT       = 'deleted_at';

    protected $entity = 'admin_lead';

    protected static $sign = 'admin_lead';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ID,
        self::ADMIN_ID,
        self::TOKEN,
        self::EMAIL,
        self::FORM_DATA,
    ];

    protected $public = [
        self::ID,
        self::ADMIN_ID,
        self::TOKEN,
        self::EMAIL,
        self::FORM_DATA,
        self::CREATED_AT,
    ];
}
