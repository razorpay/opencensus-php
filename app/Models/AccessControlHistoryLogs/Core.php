<?php

namespace RZP\Models\AccessControlHistoryLogs;

use Rzp\Common\Mode\V1\Mode;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\AccessPolicyAuthzRolesMap;


class Core extends Base\Core
{
    /**
     * @param array           $input
     *
     * @return Entity
     */
    public function create(array $input) :array
    {
        $user = $this->app['basicauth']->getUser();

        $userId = $user ? $user->getUserId() : null;

        $input[Entity::CREATED_BY] = $userId;

        $history = (new Entity)->build($input);

        $this->trace->info(
            TraceCode::CREATING_ACCESS_CONTROL_UPDATE_HISTORY,
            [
                'input' => $input,
            ]
        );

        $this->repo->saveOrFail($history);

        return $history->toArrayPublic();
    }
}
