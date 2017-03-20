<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const ENTITY_NAME   = 'entity_name';
    const ENTITY_ID     = 'entity_id';
    const ACTOR         = 'actor';
    const TYPE          = 'type';
    const URL           = 'url';
    const PATH_PARAMS   = 'path_params';
    const METHOD        = 'method';
    const PAYLOAD       = 'payload';
    const CONTROLLER    = 'controller';
    const ROUTE         = 'route';
    const DIFF          = 'diff';

    const CREATED_AT    = 'created_at';

    const FUNCTION_NAME = 'function_name';

    protected $entity   = 'action';

    protected $fillable = [
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::URL,
        self::PATH_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::CREATED_AT
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::URL,
        self::PATH_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::CREATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::URL,
        self::PATH_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::CREATED_AT
    ];

    public function setDiff(array $diff)
    {
        $this->setAttribute(self::DIFF, $diff);
    }

    public function getEntityName()
    {
        return $this->getAttribute(self::ENTITY_NAME);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getRoute()
    {
        return $this->getAttribute(self::ROUTE);
    }

    public function getPayload()
    {
        return $this->getAttribute(self::PAYLOAD);
    }
}
