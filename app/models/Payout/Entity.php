<?php

namespace Models\Payout;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID        =   'id';
    const FROM      =   'from';
    const TO        =   'to';
    const MODE      =   'mode';

    protected $entity = 'payout';

    protected $table  = \Constants\Table::PAYOUT;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::FROM,
        self::TO,
        self::MODE,
    );


    protected $visible = array(
        self::ID,
        self::FROM,
        self::TO,
        self::MODE,
    );

    protected $public = array(
        self::ID,
        self::FROM,
        self::TO,
        self::MODE,
    );
}