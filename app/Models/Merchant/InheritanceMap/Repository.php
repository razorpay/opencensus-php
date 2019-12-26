<?php

namespace RZP\Models\Merchant\InheritanceMap;


use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_inheritance_map';

    public function fetchResourceParent()
    {

    }

    public function fetchInheritanceParentByMerchantId($mid)
    {
        $merchantRepo = $this->repo->merchant;

        $merchantId = $merchantRepo->dbColumn(Merchant\Entity::ID);

        $mapMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        $mapParentID = $this->dbColumn(Entity::PARENT_MERCHANT_ID);

        return $this->newQuery()
                    ->select(Table::MERCHANT . '.*')
                    ->join(Table::MERCHANT, $mapParentID, '=', $merchantId)
                    ->where($mapMerchantId, '=', $mid)
                    ->get();
    }
}
