<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = 'merchant_request';

    protected $adminFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|string|max:14',
        Entity::NAME        => 'sometimes|string|max:25',
        Entity::TYPE        => 'sometimes|string|max:25',
        Entity::STATUS      => 'sometimes|string|max:30',
        self::EXPAND . '.*' => 'filled|string|in:merchant,',
    );

    protected $proxyFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|string|max:14',
        Entity::NAME        => 'sometimes|string|max:25',
        Entity::TYPE        => 'sometimes|string|max:25',
        Entity::STATUS      => 'sometimes|string|max:30',
        self::EXPAND . '.*' => 'filled|string|in:merchant,',
    );

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
