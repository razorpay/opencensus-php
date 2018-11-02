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

    /**
     * {@inheritdoc}
     */
    public function buildQueryForQ(array & $query, string $value)
    {
        //
        // For linked accounts, we wish to allow query on id, name and email. The mapping for the
        // `id` field is set to `keyword` and keyword datatype properties can only be searched by
        // exact values, not partially.
        //
        // In the case of merchants, this is fine since merchants.id field has no sign prefix.
        // However, in the case of accounts, there is a id prefix 'acc_', which will result in no
        // ES matches on the id field if queried with the sign.
        //
        // As a workaround, we first strip the sign here if the query value matches our signed ID regex.
        //
        $isPublicId = preg_match(PublicEntity::SIGNED_PUBLIC_ID_REGEX, $value);

        if ($isPublicId === 1)
        {
            $value = Entity::stripDefaultSign($value);
        }

        parent::buildQueryForQ($query, $value);
    }

    public function buildQueryForParentId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::PARENT_ID, $value);
    }
}
