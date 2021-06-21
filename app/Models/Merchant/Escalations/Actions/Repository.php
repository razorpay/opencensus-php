<?php


namespace RZP\Models\Merchant\Escalations\Actions;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'onboarding_escalation_actions';

    public function fetchActionForEscalation(string $escalationId)
    {
        return $this->newQuery()
            ->where(Entity::ESCALATION_ID, $escalationId)
            ->first();
    }
}
