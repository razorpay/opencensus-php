<?php

namespace RZP\Models\Admin\AdminsMeta;


use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Base;
use RZP\Exception\BadRequestException;

class Repository extends Base\Repository
{

    protected $entity = 'admins_meta';


    protected array $appFetchParamRules = [
        Entity::UNIQUE_IDENTIFIER => 'sometimes|string',
    ];

    public function fetchByUniqueIdentifierOrFail(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::UNIQUE_IDENTIFIER, '=', $id)
                    ->firstOrFailPublic();
    }

}
