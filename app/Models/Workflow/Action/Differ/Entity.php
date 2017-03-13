<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const ENTITY_NAME = 'entity_name';
    const ENTITY_ID   = 'entity_id';
    const ACTOR       = 'actor';
    const HEADER      = 'header';
    const TYPE        = 'type';
    const URI         = 'uri';
    const METHOD      = 'method';
    const PAYLOAD     = 'payload';
    const DIFF        = 'diff';

    const CREATED_AT  = 'created_at';

    protected $entity = 'action';

    protected $fillable = [
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::HEADER,
        self::TYPE,
        self::URI,
        self::METHOD,
        self::PAYLOAD,
        self::CREATED_AT
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::HEADER,
        self::URI,
        self::DIFF,
        self::METHOD,
        self::PAYLOAD,
        self::CREATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::HEADER,
        self::URI,
        self::METHOD,
        self::PAYLOAD,
    ];

    public function setDiff($diff)
    {
        return $this->setAttribute(self::DIFF, $diff);
    }
}
