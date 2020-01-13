<?php

namespace RZP\Models\Merchant\InheritanceMap;


use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_inheritance_map';


    public function fetchInheritanceMapByMerchantId($mid)
    {
        $merchantId = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->where($merchantId, '=', $mid)
                    ->FirstorFail();
    }
}
