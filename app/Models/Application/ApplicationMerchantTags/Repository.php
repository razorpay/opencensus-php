<?php

namespace RZP\Models\Application\ApplicationMerchantTags;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'application_merchant_tag';

    public function getMerchantTag(string $merchantId)
    {
        $merchantIdColumn      = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
            ->where($merchantIdColumn, '=', $merchantId)
            ->first();
    }

    public function getTagUsage(string $tag)
    {
        $tagColumn      = $this->dbColumn(Entity::TAG);

        return $this->newQuery()
            ->where($tagColumn, '=', $tag)
            ->get();
    }
}
