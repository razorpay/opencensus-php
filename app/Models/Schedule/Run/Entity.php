<?php

namespace RZP\Models\Schedule\Run;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const SCHEDULE_ID   = 'schedule_id';
    const ENTITY_ID     = 'entity_id';
    const NEXT_RUN_AT   = 'next_run_at';
    const LAST_RUN_AT   = 'last_run_at';
    const DELETED_AT    = 'deleted_at';

    protected $entity = 'run';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NEXT_RUN_AT,
    ];

    protected $public = [
        self::ID,
        self::SCHEDULE_ID
    ];

    protected $defaults = [
        self::LAST_RUN_AT => null,
        self::DELETED_AT  => null,
    ];

    // -------------------- Relations ---------------------------

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    // -------------------- End Relations ---------------------------
}
