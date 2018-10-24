<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;

class EsRepository extends Merchant\EsRepository
{
    protected $queryFields = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
    ];

    protected $commonFetchParams = [
        Entity::PARENT_ID,
    ];

    /**
     * Overridden: Usage merchant index only for now.
     *
     * @return string
     */
    public function getIndexSuffix(): string
    {
        return E::MERCHANT . '_' . $this->mode;
    }

    public function buildQueryForQ(array & $query, string $value)
    {
        $isPublicId = preg_match(PublicEntity::SIGNED_PUBLIC_ID_REGEX, $value);

        if ($isPublicId === 1)
        {
            $value = Entity::stripDefaultSign($value);
        }

        parent::buildQueryForQ($query, $value);
    }
}
