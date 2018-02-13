<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = 'merchant_request';

    protected $adminFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|string|max:14',
        Entity::NAME        => 'sometimes|string|max:25',
        Entity::STATUS      => 'sometimes|string|max:30',
        self::EXPAND . '.*' => 'filled|string|in:merchant,',
    );

    public function findByIdOrFail(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->firstOrFailPublic();
    }

    public function findByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    public function findByMerchantIdAndNameOrFail(string $merchantId, string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::NAME, $featureName)
                    ->firstOrFailPublic();
    }

    public function findByMerchantIdAndTypeAndName(string $merchantId, string $type, string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::NAME, $featureName)
                    ->first();
    }

    public function getRequestDetails(string $id)
    {
        $relations = [
            'merchant',
            'states',
            'states.rejectionReasons'
        ];

        return $this->newQuery()
                       ->where(Entity::ID, $id)
                       ->with($relations)
                       ->get();
    }
}
