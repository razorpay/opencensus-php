<?php

namespace RZP\Models\Tax;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'tax';

    /**
     * @override
     *
     * We basically assign all items.tax_id with null where tax_id was equals to
     * one which is being soft deleted. There is constraint ON DELETE SET NULL
     * at MySQL level but because we're using SoftDelets trait that doesn't
     * get triggered.
     *
     * @param Entity $entity
     */
    public function deleteOrFail($entity)
    {
        $entity->items()->update([Item\Entity::TAX_ID => null]);

        parent::deleteOrFail($entity);
    }

    /**
     * @param array           $ids
     * @param Merchant\Entity $merchant
     * @param array           $params
     * @param bool            $includeShared If true, returns taxes defined on the Shared merchant account as well
     *
     * @return PublicCollection
     * @throws \RZP\Exception\BadRequestException
     */
    public function findManyByPublicIdsAndMerchant(
        array $ids,
        Merchant\Entity $merchant,
        array $params = [],
        bool $includeShared = true) : PublicCollection
    {
        Entity::verifyIdAndStripSignMultiple($ids);

        $query = $this->getQueryForFindWithParams($params);

        $merchantId = $merchant->getId();

        if ($includeShared === true)
        {
            $merchantIds = [$merchantId, Merchant\Account::SHARED_ACCOUNT];
            $query->whereIn(Entity::MERCHANT_ID, $merchantIds);
        }
        else
        {
            $query->merchantId($merchantId);
        }

        return $query->findManyOrFailPublic($ids);
    }
}
