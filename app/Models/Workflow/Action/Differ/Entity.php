<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    const ID           = 'id';
    const ENTITY_NAME  = 'entity_name';
    const ENTITY_ID    = 'entity_id';
    const ACTOR        = 'actor';
    const TYPE         = 'type';
    const URL          = 'url';
    const ROUTE_PARAMS = 'route_params';
    const METHOD       = 'method';
    const PAYLOAD      = 'payload';
    const CONTROLLER   = 'controller';
    const ROUTE        = 'route';
    const DIFF         = 'diff';
    const ACTION_ID    = 'action_id';

    const CREATED_AT    = 'created_at';

    const FUNCTION_NAME = 'function_name';
    const PERMISSIONS   = 'permissions';

    protected $entity   = 'action';

    protected $fillable = [
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::URL,
        self::ROUTE_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::ACTION_ID,
        self::CREATED_AT
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::URL,
        self::ROUTE_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::ACTION_ID,
        self::CREATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::ACTOR ,
        self::TYPE,
        self::URL,
        self::ROUTE_PARAMS,
        self::METHOD,
        self::PAYLOAD,
        self::CONTROLLER,
        self::ROUTE,
        self::DIFF,
        self::ACTION_ID,
        self::CREATED_AT
    ];

    public function setDiff(array $diff)
    {
        $this->setAttribute(self::DIFF, $diff);
    }

    public function getEntityName() : string
    {
        return $this->getAttribute(self::ENTITY_NAME);
    }

    public function getEntityId() : string
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getRoute() : string
    {
        return $this->getAttribute(self::ROUTE);
    }

    public function getPayload()
    {
        return $this->getAttribute(self::PAYLOAD);
    }
}
