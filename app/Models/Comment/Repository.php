<?php

namespace RZP\Models\Comment;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;

class Repository extends Base\Repository
{
    const VALID_ENTITY_TYPE = [
        E::DISPUTE,
        E::WORKFLOW_ACTION,
    ];

    protected $entity = 'comment';

    protected $adminFetchParamRules = [
        Entity::ENTITY_TYPE => 'filled|string|max:100|custom',
        Entity::ENTITY_ID   => 'filled|string|min:14|max:25',
        Entity::ADMIN_ID    => 'filled|string|min:14|max:19',
        Entity::MERCHANT_ID => 'filled|string|size:14',
    ];

    public function fetchByActionIdWithRelations(
        string $actionId,
        array $relations = []): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $actionId)
                    ->where(Entity::ENTITY_TYPE, '=', E::WORKFLOW_ACTION)
                    ->with($relations)
                    ->get();
    }

    public function fetchByActionId(
        string $actionId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $actionId)
                    ->where(Entity::ENTITY_TYPE, '=', E::WORKFLOW_ACTION)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    protected function validateEntityType($attribute, $value)
    {
        if (in_array($value, self::VALID_ENTITY_TYPE, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MORPHED_ENTITY_INVALID,
                Entity::ENTITY_TYPE,
                [
                    Entity::ENTITY_TYPE => $value
                ]);
        }
    }
}
