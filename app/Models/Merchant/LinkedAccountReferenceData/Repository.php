<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'linked_account_reference_data';

    public function fetchAllByCategory(string $category)
    {
        return $this->newQuery()
                    ->where(Entity::CATEGORY, $category)
                    ->get();
    }
}
