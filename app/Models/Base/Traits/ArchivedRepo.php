<?php

namespace RZP\Models\Base\Traits;

use Throwable;
use RZP\Models\Merchant;
use RZP\Models\Base\PublicEntity;

trait ArchivedRepo
{
    use ArchivedCore;

    /**
     * @throws Throwable
     */
    public function findByPublicId($id, string $connectionType = null)
    {
        return $this->findByPublicIdArchived($id, $connectionType);
    }

    /**
     * @throws Throwable
     */
    public function findByPublicIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        return $this->findByPublicIdAndMerchantArchived($id, $merchant, $params, $connectionType);
    }

    /**
     * @throws Throwable
     */
    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        return $this->findByIdAndMerchantArchived($id, $merchant, $params, $connectionType);
    }

    /**
     * @throws Throwable
     */
    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        return $this->findByIdAndMerchantIdArchived($id, $merchantId, $connectionType);
    }

    /**
     * @throws Throwable
     */
    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        return $this->findOrFailByPublicIdWithParamsArchived($id, $params, $connectionType);
    }

    /**
     * @throws Throwable
     */
    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        return $this->findOrFailPublicArchived($id, $columns, $connectionType);
    }

    /**
     * @throws Throwable
     */
    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        return $this->findOrFailArchived($id, $columns, $connectionType);
    }
}
