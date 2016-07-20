<?php

namespace RZP\Models\Terminal\DowntimeTrace;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                            = 'id';
    const TERMINAL_ID                   = 'terminal_id';
    const GATEWAY                       = 'gateway';
    const DOWNTIME_FROM                 = 'downtime_from';
    const DOWNTIME_TO                   = 'downtime_to';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';


    protected $fillable = array(
        self::TERMINAL_ID,
        self::GATEWAY
    );

    protected $public = array(
        self::ID,
        self::TERMINAL_ID,
        self::GATEWAY,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $table = 'terminal_downtime_trace';

    protected $generateIdOnCreate = true;

    protected $entity = 'TerminalDowntimeTrace';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(self::ID, self::TERMINAL_ID, self::GATEWAY,
                                         self::DOWNTIME_FROM, self::DOWNTIME_TO,
                                         self::CREATED_AT, self::UPDATED_AT
                                        );

}
