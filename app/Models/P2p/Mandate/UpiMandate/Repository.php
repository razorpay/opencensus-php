<?php

namespace RZP\Models\P2p\Mandate\UpiMandate;

use RZP\Models\P2p\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\P2p\Mandate\UpiMandate
 */
class Repository extends Base\Repository
{
    protected $entity = 'p2p_upi_mandate';

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MANDATE_ID, 'desc');
    }

    public function findAll(array $input)
    {
        $query =  $this->newQuery()->where($input);

        return $query->get();
    }
}
