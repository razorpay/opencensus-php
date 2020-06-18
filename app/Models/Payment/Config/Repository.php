<?php


namespace RZP\Models\Payment\Config;

use RZP\Models\Base;


class Repository extends Base\Repository
{
    protected $entity = 'config';

    protected $table = 'payment_configs';

    protected $entityFetch = [
        Entity::TYPE,
    ];

    public function fetchConfigByMerchantIdAndType($merchantId, $type, $input = [])
    {
           $query =  $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type);

           $this->buildQueryWithParams($query, $input);

           $this->addQueryOrder($query);

           return $query->get();
    }

    public function findByPublicIdAndMerchantAndType($id, $merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type)
                    ->first();
    }

    public function fetchDefaultConfigByMerchantIdAndType($merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::IS_DEFAULT, true)
                    ->first();
    }
}
