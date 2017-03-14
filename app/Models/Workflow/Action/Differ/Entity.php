<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const ENTITY_NAME = 'entity_name';
    const ENTITY_ID   = 'entity_id';
    const ACTOR       = 'actor';
    const HEADERS     = 'headers';
    const TYPE        = 'type';
    const URL         = 'url';
    const METHOD      = 'method';
    const PAYLOAD     = 'payload';
    const DIFF        = 'diff';

    const CREATED_AT  = 'created_at';

    protected $entity = 'action';

    protected $fillable = [
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::HEADERS,
        self::TYPE,
        self::URL,
        self::METHOD,
        self::PAYLOAD,
        self::DIFF,
        self::CREATED_AT
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::HEADERS,
        self::URL,
        self::DIFF,
        self::METHOD,
        self::PAYLOAD,
        self::DIFF,
        self::CREATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::HEADERS,
        self::URL,
        self::METHOD,
        self::DIFF,
        self::PAYLOAD,
    ];
}
