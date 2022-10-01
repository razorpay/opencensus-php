<?php

namespace RZP\Models\OfflineChallan;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Base\PublicEntity;
use Rzp\Models\Merchant;
Use RZP\Models\OfflineChallan\Entity as Entity;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::OFFLINE_CHALLAN;

    public function fetchByChallanNumber(string $challanNumber) {
            return $this->newQuery()
                        ->where(Entity::CHALLAN_NUMBER, '=', $challanNumber)
                        ->first();
        }

    public function findbyPublicId($id, string $connectionType = null) {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->where(Entity::ID, '=', $id)
                    ->first();
    }

}
