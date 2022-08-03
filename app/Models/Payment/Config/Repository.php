<?php


namespace RZP\Models\Payment\Config;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\Builder;

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
                    ->where(Entity::IS_DELETED, false)
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
                    ->where(Entity::IS_DELETED, false)
                    ->first();
    }

    public function fetchDefaultConfigByMerchantIdAndType($merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::IS_DEFAULT, true)
                    ->where(Entity::IS_DELETED, false)
                    ->when($type === Type::CHECKOUT, static function (Builder $query) {
                        return $query->orderBy(Entity::UPDATED_AT, 'desc');
                    })
                    ->first();
    }

    public function deletePaymentConfig($merchantId, $type)
    {
        $this->newQuery()
             ->where(Entity::MERCHANT_ID, $merchantId)
             ->where(Entity::TYPE, $type)
             ->update([Entity::IS_DELETED => true]);
    }

    public function fetchMultipleByParam($input){
        $query = $this->newQuery()->where(Entity::IS_DELETED, false);

        if ((isset($input['is_default']) === true) and (($input['is_default'] === 'true') or (strval($input['is_default']) === '1'))) {
            $query->where(Entity::IS_DEFAULT, true);
            unset($input['is_default']);
        }

        $this->buildQueryWithParams($query, $input);

        $this->addQueryOrder($query);

        return $query->get();
    }

    public function fetchByIdAndNotDeleted($id){
        $query = $this->newQuery()->where(Entity::IS_DELETED, false);

        return $query->findOrFailPublic($id);
    }
}
