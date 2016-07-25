<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const DOWNTIME_FROM                 = 'downtime_from';
    const DOWNTIME_TO                   = 'downtime_to';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';


    protected $fillable = array(
        self::GATEWAY,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO
    );

    protected $public = array(
        self::ID,
        self::GATEWAY,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $table = \RZP\Constants\Table::TERMINAL_ABSENCE;

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal_absence';

    protected static $sign = '';

    protected static $delimiter = '';

    //protected static $generators = array(self::ID, self::GATEWAY,
    //                                     self::DOWNTIME_FROM, self::DOWNTIME_TO,
    //                                     self::CREATED_AT, self::UPDATED_AT
    //                                    );

    public function getGateway()
    {
        return $this->attributes[self::GATEWAY];
    }

    public function getDowntimeFrom()
    {
        return $this->attributes[self::DOWNTIME_FROM];
    }

    public function getDowntimeTo()
    {
        return $this->attributes[self::DOWNTIME_TO];
    }

}
