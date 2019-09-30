<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_document';

    /**
     * @param $fileStoreId
     *
     * @return mixed
     */
    public function findDocumentByFileStoreId(string $fileStoreId)
    {
        return $this->newQuery()
                    ->where(Entity::FILE_STORE_ID, '=', $fileStoreId)
                    ->first();
    }

    /**
     * Returns all non deleted documents for given merchantId
     * @param string $merchantID
     *
     * @return mixed
     */
    public function findAllDocumentsByMerchantID(string $merchantID)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantID)
                    ->get();
    }
}
