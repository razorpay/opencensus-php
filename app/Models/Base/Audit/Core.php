<?php

namespace RZP\Models\Base\Audit;

use App;
use RZP\Models\Base;
use RZP\Constants\Mode;

class Core extends Base\Core
{

    public function create()
    {
        $auditInfo = new Entity;

        $auditInfo->generateId();

        $facadeRoot = App::getFacadeRoot();

        $ba = $facadeRoot['basicauth'];

        $request = $facadeRoot['request'];

        [$actorId, $actorType] = $this->getActorIdAndType();

        $meta = [
            Constants::ACTOR_ID   => $actorId,
            Constants::ACTOR_TYPE => $actorType,
            Constants::AUTH_TYPE  => $ba->getAuthType(),
            Constants::APP        => $ba->getInternalApp() ?? null,
            Constants::TASK_ID    => $request->getTaskId(),
        ];

        $auditInfo->setMeta($meta);

        $auditInfo->setConnection(Mode::LIVE);

        $auditInfo->saveOrFail();

        return $auditInfo;
    }

    protected function getActorIdAndType()
    {
        $facadeRoot = App::getFacadeRoot();

        $ba = $facadeRoot['basicauth'];

        $admin = $ba->getAdmin();

        if ($admin !== null)
        {
            return [$admin->getId(), Constants::ACTOR_TYPE_ADMIN];
        }

        $user = $ba->getUser();

        if ($user !== null)
        {
            return [$user->getId(), Constants::ACTOR_TYPE_USER];
        }

        return ['', ''];
    }
}
