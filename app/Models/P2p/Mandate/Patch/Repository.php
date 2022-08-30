<?php

namespace RZP\Models\P2p\Mandate\Patch;

use RZP\Models\P2p\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\P2p\Mandate\Patch
 */
class Repository extends Base\Repository
{
    protected $entity = 'p2p_mandate_patch';

    public function findAll(array $input)
    {
        $query = $this->newQuery()->where($input);

        return $query->get();
    }

    public function findPatchByMandateIdAndActive(string $mandateId, bool $active)
    {
        $query = $this->newQuery()->where(Entity::MANDATE_ID, '=', $mandateId)->where(Entity::ACTIVE, '=', $active);

        return $query->first();
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MANDATE_ID, 'desc');
    }
}
