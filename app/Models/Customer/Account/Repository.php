<?php

namespace RZP\Models\Customer;

use RZP\Base\BuilderEx;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Base\Collection;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\SalesforceConverge\Error;
use RZP\Trace\TraceCode;
use Rzp\Wda_php\WDAQueryBuilder;

class Repository extends Base\Repository
{
    protected $entity = 'customer';
    /*
    * * ------------------------------------------ Overridden methods START --------------------------------------------
    */

    public function fetchAndReturnPublicArrayWithExpand($id, $merchant, array $params)
    {
        $this->logMethodCall(__FUNCTION__);
        parent::fetchAndReturnPublicArrayWithExpand($id, $merchant, $params);
    }

    public function fetch(array $params, string $merchantId = null, string $connectionType = null): PublicCollection
    {
        // Check if query can be served by DB.
        // It should not contain any params other than "count" and "skip"
        $allowedKeys = ['count', 'skip'];
        $invalidKeys = array_diff(array_keys($params), $allowedKeys);
        if (!empty($invalidKeys))
            return parent::fetch($params, $merchantId, $connectionType);

        $merchant = $this->repo->merchant->find($merchantId);
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__, ['merchant_id' => $merchantId, 'should_read_via_cms' => $shouldReadViaCMS]);
        if ($shouldReadViaCMS)
        {
            $response = (new \RZP\Services\CMS\Service($this->app))->listCustomers(
                [
                    'merchant_id' => $merchantId,
                    'count' => $params['count'],
                    'skip' => $params['skip']
                ]
            );
            return (new Customer\Account\Transformations)->convertListResponseToPublicCollection($response);
        }

        return parent::fetch($params, $merchantId, $connectionType);
    }

    public function getEntitiesFromWda(WDAQueryBuilder $wdaQueryBuilder, $query)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::getEntitiesFromWda($wdaQueryBuilder, $query);
    }

    public function findByPublicId($id, string $connectionType = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findByPublicId($id, $connectionType);
    }

    public function findArchivedByPublicId($id, string $connectionType = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findArchivedByPublicId($id, $connectionType);
    }

    public function findByPublicIdAndMerchant(string $id, Merchant\Entity $merchant, array $params = [], string $connectionType = null): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__);
        // The parent method internally calls findByIdAndMerchant so there is no need to add override call here
        return parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    public function findArchivedByPublicIdAndMerchant(string $id, Merchant\Entity $merchant, array $params = [], string $connectionType = null): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findArchivedByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    public function findManyByPublicIdsAndMerchant(array $ids, Merchant\Entity $merchant, array $params = []): PublicCollection
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findManyByPublicIdsAndMerchant($ids, $merchant, $params);
    }

    /**
     * @throws BadRequestException
     */
    public function findByIdAndMerchant(string $id, Merchant\Entity $merchant, array $params = [], string $connectionType = null): PublicEntity
    {
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__, ['customer_id' => $id, 'merchant_id' => $merchant->getId(), 'params' => $params, 'should_read_via_cms' => $shouldReadViaCMS]);
        if ($shouldReadViaCMS)
        {
            return $this->getCustomerEntityFromCMS($id, $merchant->getId());
        }
        return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    public function findArchivedByIdAndMerchant(string $id, Merchant\Entity $merchant, array $params = [], string $connectionType = null): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findArchivedByIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    /**
     * @throws BadRequestException
     */
    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__, ['customer_id' => $id, 'merchant_id' => $merchantId, 'should_read_via_cms' => $shouldReadViaCMS]);
        if ($shouldReadViaCMS)
        {
            return $this->getCustomerEntityFromCMS($id, $merchant->getId());
        }
        return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
    }

    public function findArchivedByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findArchivedByIdAndMerchantId($id, $merchantId, $connectionType);
    }

    public function findOrFailByPublicIdWithParams(string $id, array $params, string $connectionType = null): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
    }

    public function findOrFailArchivedByPublicIdWithParams(string $id, array $params, string $connectionType = null): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findOrFailArchivedByPublicIdWithParams($id, $params, $connectionType);
    }

    public function findOrFailByPublicIdWithParamsWDAQuery(string $id, BuilderEx $query): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findOrFailByPublicIdWithParamsWDAQuery($id, $query);
    }

    public function saveOrFail($entity, array $options = array())
    {
        parent::saveOrFail($entity);

        // Overriding is implemented in the caller function so no need to override here
        if (!array_key_exists('logged', $options) || !$options['logged'])
        {
            $this->logMethodCall(__FUNCTION__,
                [
                    'customer_id' => $entity->getId(),
                    'merchant_id' => $entity->getMerchantId()
                ], 'write'
            );
        }
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations);
    }

    public function createOrFail(array $attributes)
    {
        $this->logMethodCall(__FUNCTION__, [], 'write');
        return parent::createOrFail($attributes);
    }

    // Behaviour of parent method: if id is found, a customer entity is returned
    // Otherwise, an exception is thrown
    public function findOrFailPublic($id, $columns = ['*'], string $connectionType = null): Entity
    {
        $this->logMethodCall(__FUNCTION__, ['customer_id' => $id, 'connection_type' => $connectionType]);
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled(null);
        if ($shouldReadViaCMS)
        {
            try
            {
                return $this->getCustomerEntityFromCMS($id);
            }
            catch (\Exception) {
                /* This exception can occur when
                    1. CMS responds with "ID does not exist"
                    2. Request to CMS fails [Expected in geos where CMS is not yet deployed]
                */
            }
        }
        return parent::findOrFailPublic($id, $columns, $connectionType);
    }

    public function findOrFailArchivedPublic($id, $columns = ['*'], string $connectionType = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findOrFailArchivedPublic($id, $columns, $connectionType);
    }

    public function findOrFailPublicWithRelations(string $id, array $relations = [], array $columns = array('*'))
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findOrFailPublicWithRelations($id, $relations, $columns);
    }

    public function findWithRelations(string $id, array $relations = [], array $columns = array('*'))
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findWithRelations($id, $relations, $columns);
    }

    public function findMany($ids, $columns = array('*'))
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findMany($ids, $columns);
    }

    public function findManyWithRelations($ids, $relations, $columns = array('*'), $useWarehouse = false)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findManyWithRelations($ids, $relations, $columns, $useWarehouse);
    }

    public function findManyByPublicIds($ids)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findManyByPublicIds($ids);
    }

    public function findManyByMerchantIds(array $mids): Collection
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findManyByMerchantIds($mids);
    }

    public function existsInTable($tableName, $id, $throwException = false): bool
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::existsInTable($tableName, $id, $throwException);
    }

    public function saveOrFailWithoutEsSync($entity, array $options = array())
    {
        $this->logMethodCall(__FUNCTION__, [], 'write');
        parent::saveOrFailWithoutEsSync($entity, $options);
    }

    public function deleteOrFail($entity)
    {
        // This method is most likely not used
        $this->logMethodCall(__FUNCTION__, ['id' => $entity->getId()], 'write');
        parent::deleteOrFail($entity);
    }

    public function sync($entity, $relation, $ids = [], bool $detaching = true)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::sync($entity, $relation, $ids, $detaching);
    }

    public function validateExists($ids)
    {
        $this->logMethodCall(__FUNCTION__);
        parent::validateExists($ids);
    }

    public function newQueryWithoutTimestamps()
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::newQueryWithoutTimestamps();
    }

    public function newQueryOnSlave($lagThreshold = null)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::newQueryOnSlave($lagThreshold);
    }

    public function newQueryOnPaymentFetchReplica($lagThreshold = null, $endTime = null)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::newQueryOnPaymentFetchReplica($lagThreshold, $endTime);
    }

    public function find($id, $columns = array('*'), string $connectionType = null): Entity
    {
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled(null);
        $this->logMethodCall(__FUNCTION__, [ 'id' => $id, 'connection_type' => $connectionType, 'should_create_via_cms' => $shouldReadViaCMS]);
        if ($shouldReadViaCMS)
        {
            try
            {
                return $this->getCustomerEntityFromCMS($id);
            }
            catch (\Exception) {
                /* This exception can occur when
                    1. CMS responds with "ID does not exist"
                    2. Request to CMS fails [Expected in geos where CMS is not yet deployed]
                */
            }
        }

        return parent::find($id, $columns, $connectionType);
    }

    public function findArchived($id, $columns = array('*'), string $connectionType = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findArchived($id, $columns, $connectionType);
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null): Entity|Base\Entity
    {
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled(null);
        $this->logMethodCall(__FUNCTION__, [ 'customer_id' => $id, 'connection_type' => $connectionType, 'should_read_via_cms' => $shouldReadViaCMS]);
        if ($shouldReadViaCMS)
        {
            try
            {
                return $this->getCustomerEntityFromCMS($id);
            }
            catch (\Exception) {
                /* This exception can occur when
                    1. CMS responds with "ID does not exist"
                    2. Request to CMS fails [Expected in geos where CMS is not yet deployed]
                */
            }
        }

        return parent::findOrFail($id, $columns, $connectionType);
    }

    public function findArchivedOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findArchivedOrFail($id, $columns, $connectionType);
    }

    public function findOrFailOnMaster(string $id, $columns = array('*'))
    {
        $this->logMethodCall(__FUNCTION__, [ 'id' => $id ]);
        return parent::findOrFailOnMaster($id, $columns);
    }

    public function fetchBetweenTimestampWithRelations($merchantId, $from, $to, $count, $skip = 0, $relations = [], $useWarehouse = false)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::fetchBetweenTimestampWithRelations($merchantId, $from, $to, $count, $skip, $relations, $useWarehouse);
    }

    public function fetchAssociatedRelations($entities, $relation, $idCol = 'entity_id', $typeCol = 'type')
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::fetchAssociatedRelations($entities, $relation, $idCol, $typeCol);
    }

    public function fetchBetweenTimestamp($merchantId, $from, $to)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::fetchBetweenTimestamp($merchantId, $from, $to);
    }

    public function lockForUpdateAndReload(PublicEntity $entity, bool $withTrashed = false)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        parent::lockForUpdateAndReload($entity, $withTrashed);
    }

    public function lockForUpdate(string $id, bool $withTrashed = false)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::lockForUpdate($id, $withTrashed);
    }

    public function findForIndexing(string $id): array
    {
        $this->logMethodCall(__FUNCTION__, [ 'id' => $id ]);
        return parent::findForIndexing($id);
    }

    public function findManyForIndexingByIds(array $ids): array
    {
        $this->logMethodCall(__FUNCTION__, [ 'ids' => $ids ]);
        return parent::findManyForIndexingByIds($ids);
    }

    public function findManyForIndexing(string $afterId = null, int $take = 100, int $createdAtStart = null, int $createdAtEnd = null): array
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::findManyForIndexing($afterId, $take, $createdAtStart, $createdAtEnd);
    }

    public function syncToEs(PublicEntity $entity, string $action, array $dirty = null, string $mode = null)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        parent::syncToEs($entity, $action, $dirty, $mode);
    }

    public function syncToEsLiveAndTest(PublicEntity $entity, string $action, array $dirty = null)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        parent::syncToEsLiveAndTest($entity, $action, $dirty);
    }

    public function loadRelations(PublicEntity $entity): PublicEntity
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::loadRelations($entity);
    }

    public function getUniqueMerchantIdsWhereBalanceIdIsNull(int $limit): array
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::getUniqueMerchantIdsWhereBalanceIdIsNull($limit);
    }

    public function bulkUpdateBalanceId(string $merchantId, string $balanceId, int $limit)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::bulkUpdateBalanceId($merchantId, $balanceId, $limit);
    }

    public function isExperimentEnabledForId(string $feature, string $id = null): bool
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::isExperimentEnabledForId($feature, $id);
    }

    public function getIfUpdatedBetween(int $from, int $to, ?int $limit = null)
    {
        $this->logMethodCall(__FUNCTION__);
        return parent::getIfUpdatedBetween($from, $to, $limit);
    }

    public function create(array $attributes)
    {
        $this->logMethodCall(__FUNCTION__, [], 'write');
        return parent::create($attributes);
    }

    public function save($entity, array $options = array())
    {
        $customer = parent::save($entity, $options);
        $this->logMethodCall(__FUNCTION__,
            [
                'customer_id' => $entity->getId(),
                'merchant_id' => $entity->getMerchantId()
            ], 'write'
        );
        return $customer;
    }

    public function delete($entity)
    {
        $this->logMethodCall(__FUNCTION__, [], 'write');
        return parent::delete($entity);
    }

    public function pushOrFail($entity)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        parent::pushOrFail($entity);
    }

    public function reload(&$entity)
    {
        $this->logMethodCall(__FUNCTION__, [], 'n/a');
        return parent::reload($entity);
    }

    /*
    * ------------------------------------------ Overridden methods END -------------------------------------------------
    */

    public function getGlobalCustomerForPayment($payment)
    {
        if ($payment->getGlobalCustomerId() !== null)
        {
            $customer = $this->findOrFail($payment->getGlobalCustomerId());
            $payment->globalCustomer()->associate($customer);

            return $customer;
        }
    }

    public function fetchByAppToken(AppToken\Entity $appToken): Entity
    {
        if ($appToken->hasRelation('customer'))
        {
            return $appToken->customer;
        }

        $custId = $appToken->getCustomerId();

        $customer = $this->findOrFail($custId);

        $appToken->customer()->associate($customer);

        return $customer;
    }


    // behaviour: does not throw exception, either the entity or null
    public function findById($id, $columns = ['*'])
    {
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled(null);
        $this->logMethodCall(__FUNCTION__, [ 'customer_id' => $id, 'should_read_via_cms' => $shouldReadViaCMS]);
        if ($shouldReadViaCMS)
        {
            try
            {
                return $this->getCustomerEntityFromCMS($id);
            }
            catch (\Exception) {
                /* This exception can occur when
                    1. CMS responds with "ID does not exist"
                    2. Request to CMS fails [Expected in geos where CMS is not yet deployed]
                */
            }
        }

        return $this->newQuery()
            ->select($columns)
            ->find($id);
    }

    public function findByContactAndMerchant($contact, Merchant\Entity $merchant, bool $useWritePdo = false)
    {
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__,
            [
                'should_read_via_cms' => $shouldReadViaCMS,
                'merchant_id' => $merchant->getId(),
            ]);

        if ($shouldReadViaCMS)
        {
            $customers = (new \RZP\Services\CMS\Service($this->app))->listCustomers(
                [
                    'merchant_id' => $merchant->getId(),
                    'contact' => $contact,
                    'count' => 1
                ]
            );

            if (count($customers['items']) == 0)
                return null;

            $customerEntity = new Entity();
            (new Customer\Account\Transformations())->fillV2CustomerInfoInCustomerEntity($customerEntity, $customers['items'][0]);
            return $customerEntity;
        }

        $query = $useWritePdo ? $this->newQuery()->useWritePdo() : $this->newQuery();
        return $query->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }

    // behaviour: returns null if match not found
    // does not throw exception
    public function findByContactAndMerchantId($contact, $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__,
            [
                'should_read_via_cms' => $shouldReadViaCMS,
                'merchant_id' => $merchant->getId(),
            ]);

        if ($shouldReadViaCMS)
        {
            $customers = (new \RZP\Services\CMS\Service($this->app))->listCustomers(
                [
                    'merchant_id' => $merchantId,
                    'contact' => $contact,
                    'count' => 1
                ]
            );

            if (count($customers['items']) == 0)
                return null;

            $customerEntity = new Entity();
            (new Customer\Account\Transformations())->fillV2CustomerInfoInCustomerEntity($customerEntity, $customers['items'][0]);
            return $customerEntity;
        }

        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }


    public function fetchWithVpasBankAcnts($id, $columns = ['*'])
    {
        // From the metrics of last 90 days, the route that uses this method has not been invoked at all
        $this->logMethodCall(__FUNCTION__, ['customer_id' => $id]);
        return $this->newQuery()
                    ->select($columns)
                    ->with(['vpas', 'bank_accounts'])
                    ->find($id);
    }

    // Behaviour: Returns null if match not found. Does not throw exception.
    public function findByContactEmailAndMerchant($contact, $email, Merchant\Entity $merchant, bool $useWritePdo = false)
    {
        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__,
            [
                'should_read_via_cms' => $shouldReadViaCMS,
                'merchant_id' => $merchant->getId(),
            ]);

        if ($shouldReadViaCMS)
        {
            $customers = (new \RZP\Services\CMS\Service($this->app))->listCustomers(
                [
                    'merchant_id' => $merchant->getId(),
                    'contact' => $contact,
                    'email' => $email,
                    'count' => 1
                ]
            );

            if (count($customers['items']) == 0)
                return null;

            $customerEntity = new Entity();
            (new Customer\Account\Transformations())->fillV2CustomerInfoInCustomerEntity($customerEntity, $customers['items'][0]);
            return $customerEntity;
        }

        $query = $useWritePdo ? $this->newQuery()->useWritePdo() : $this->newQuery();
        return $query->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::EMAIL, '=', $email)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }

    public function findOrFailByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        Entity::verifyIdAndStripSign($id);

        $shouldReadViaCMS = (new Customer\Account\SplitzExperimentEvaluator())->isReadOverrideToCmsEnabled($merchant);
        $this->logMethodCall(__FUNCTION__,
            [
                'should_read_via_cms' => $shouldReadViaCMS,
                'merchant_id' => $merchant->getId(),
                'customer_id' => $id
            ]);

        if ($shouldReadViaCMS)
        {
           return $this->getCustomerEntityFromCMS($id, $merchant->getId());
        }

        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->find($id);
    }

    public function fetchByMerchantId($merchantId)
    {
        $this->logMethodCall(__FUNCTION__);
        return $this->newQuery()
            ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
            ->get();
    }


    protected function logMethodCall(string $methodName, $extra = [], $opType = 'read')
    {
        $this->trace->info(TraceCode::CUSTOMER_REPO_CALL,
            [
                'method' => $methodName,
                'op_type' => $opType,
                'extra' => $extra,
                'mode' => $this->app['rzp.mode'],
                'internal_app_name' => $this->app['request.ctx']->getInternalAppName()
            ]);
    }


    /**
     * @throws BadRequestException
     */
    protected function getCustomerEntityFromCMS($id, $merchantId = null): Entity
    {
        try
        {
            $responseData = (new \RZP\Services\CMS\Service($this->app))->getCustomerByReferenceId($id);
        }
        catch (\Exception)
        {
            // TODO: This will return 400 even when CMS returns 500. Fix exception handling.
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        if (!is_null($merchantId) && $responseData['merchant_id'] != $merchantId)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }
        $customerEntity = new Entity();
        (new Customer\Account\Transformations())->fillV2CustomerInfoInCustomerEntity($customerEntity, $responseData);
        return $customerEntity;
    }
}
