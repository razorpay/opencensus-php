<?php

namespace Models\Ledger;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetchMultiple;

    protected $entity = 'Ledger';

    public function findByIdAndMerchantId($id, $merchantId, $failPublic = true)
    {
        $repo = $this->repo;

        $query = $repo::where(Transaction\Entity::MERCHANT_ID, $merchantId);

        if ($failPublic)
            return $query->findOrFailPublic($id);
        else
            return $query->findOrFail($id);
    }
}