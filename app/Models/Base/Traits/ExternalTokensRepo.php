<?php

namespace RZP\Models\Base\Traits;

use PHPUnit\Framework\Exception;

use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Services\Tokens;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Entity as MerchantEntity;

trait ExternalTokensRepo
{
    protected $entityName;

    public function getByTokenAndCustomer($tokenId, Customer\Entity $customer)
    {
        $this->entityName = $this->entity;

        $apiToken = $this->getByTokenAndCustomerFromApi($tokenId, $customer);

        if ($apiToken != null)
        {
            return $apiToken;
        }

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                $token = $this->fetchExternalToken([Token\Entity::CUSTOMER_ID => $customer->getId(), Token\Entity::TOKEN => $tokenId]);

                if ($token != null)
                {
                    $token->customer()->associate($customer);
                }

                return $token;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' =>  $ex->getMessage(),
                    'function_name'     =>  __FUNCTION__
                ]);
        }

        return $apiToken;
    }

    public function getByTokenAndMerchant($tokenId, Merchant\Entity $merchant)
    {
        $this->entityName = $this->entity;

        $apiToken = $this->getByTokenAndMerchantFromAPI($tokenId, $merchant);

        if ($apiToken != null)
        {
            return $apiToken;
        }

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                $token = $this->fetchExternalToken([Token\Entity::MERCHANT_ID => $merchant->getId(), Token\Entity::TOKEN => $tokenId]);

                if ($token != null)
                {
                    $token->merchant()->associate($merchant);
                }

                return $token;
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function_name'     => __FUNCTION__
                ]);
        }

        return $apiToken;
    }

    public function getByToken(string $tokenId)
    {
        $this->entityName = $this->entity;

        $apiToken = $this->getByTokenFromAPI($tokenId);

        if($apiToken != null)
        {
            return $apiToken;
        }

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                return $this->fetchExternalToken([Token\Entity::TOKEN => $tokenId]);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function_name'     => __FUNCTION__
                ]);
        }

        return $apiToken;
    }

    public function getByTokenAndCustomerId(string $tokenId, string $customerId)
    {
        $this->entityName = $this->entity;

        $apiToken = $this->getByTokenAndCustomerIdFromAPI($tokenId, $customerId);

        if ($apiToken != null)
        {
            return $apiToken;
        }

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                return $this->fetchExternalToken([Token\Entity::CUSTOMER_ID => $customerId, Token\Entity::TOKEN => $tokenId]);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function_name'     => __FUNCTION__
                ]);
        }

        return $apiToken;
    }

    public function getByTokenIdAndCustomerId(string $id, string $customerId)
    {
        $this->entityName = $this->entity;

        try
        {
            return $this->getByTokenIdAndCustomerIdFromAPI($id, $customerId);
        }
        catch(\Throwable $ex)
        {
            try
            {
                if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                    and $this->validateExternalFetchEnabledForTokens())
                {
                    return $this->fetchExternalToken([Token\Entity::CUSTOMER_ID => $customerId, Token\Entity::ID => $id]);
                }
            }
            catch(\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $e->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }
            throw $ex;
        }
    }

    public function getByMethodAndMerchant($method, $merchant)
    {
        $this->entityName = $this->entity;

        $apiTokens = $this->getByMethodAndMerchantFromAPI($method, $merchant);

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                $params = [Token\Entity::METHOD => $method, Token\Entity::MERCHANT_ID => $merchant->getId()];

                $serviceTokens = $this->fetchExternalTokens($params);

                if(sizeof($serviceTokens) > 0)
                {
                    return $this->mergeTokenEntitiesFromAPIAndTokensService($apiTokens, $serviceTokens);
                }
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function_name'     => __FUNCTION__
                ]);
        }

        return $apiTokens;
    }

    public function getByMethodAndCustomerId($method, $customer)
    {
        $this->entityName = $this->entity;

        $apiTokens = $this->getByMethodAndCustomerIdFromAPI($method, $customer);

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                $params = [Token\Entity::METHOD => $method, Token\Entity::CUSTOMER_ID => $customer->getId(), Token\Entity::MERCHANT_ID => $customer->merchant->getId()];

                $serviceTokens = $this->fetchExternalTokens($params);

                if(sizeof($serviceTokens) > 0)
                {
                    return $this->mergeTokenEntitiesFromAPIAndTokensService($apiTokens, $serviceTokens);
                }
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function_name'     => __FUNCTION__
                ]);
        }

        return $apiTokens;
    }

    public function getByMethodCustomerIdAndVpaId($method, $customer, $vpaId)
    {
        $this->entityName = $this->entity;

        $apiToken = $this->getByMethodCustomerIdAndVpaIdFromAPI($method, $customer, $vpaId);

        if($apiToken != null)
        {
            return $apiToken;
        }

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                $params = [Token\Entity::METHOD => $method, Token\Entity::CUSTOMER_ID => $customer->getId(), Token\Entity::MERCHANT_ID => $customer->merchant->getId()];

                $params['entity_id'] = $vpaId; //In the new schema we are using entity_id for vpa_id, card_id etc

                return $this->fetchExternalToken($params);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function'          => __FUNCTION__,
                ]);
        }

        return $apiToken;
    }

    public function getByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        $this->entityName = $this->entity;

        $apiToken = $this->getByPublicIdAndMerchantFromAPI($id, $merchant);

        if($apiToken != null)
        {
            return $apiToken;
        }

        try
        {
            if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                and $this->validateExternalFetchEnabledForTokens())
            {
                $params = [Token\Entity::ID => $id, Token\Entity::MERCHANT_ID => $merchant->getId()];

                return $this->fetchExternalToken($params);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                [
                    'exception_message' => $ex->getMessage(),
                    'function_name'     => __FUNCTION__,
                ]);
        }

        return $apiToken;
    }

    /**
     * @throws \Throwable
     * @throws BadRequestException
     */
    public function findOrFailByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        $this->entityName = $this->entity;

        try
        {
            return $this->findOrFailByPublicIdAndMerchantFromAPI($id, $merchant);
        }
        catch(\Throwable $ex)
        {
            try
            {
                if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                    and $this->validateExternalFetchEnabledForTokens())
                {
                    $params = [Token\Entity::ID => $id, Token\Entity::MERCHANT_ID => $merchant->getId()];

                    return $this->fetchExternalToken($params);
                }
            }
            catch(\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message'     => $e->getMessage(),
                        'function_name'         => __FUNCTION__
                    ]);
            }

            throw $ex;
        }
    }

    /**
     * @throws \Throwable
     * @throws BadRequestException
     */
    public function findOrFailTrashedById($id)
    {
        $this->entityName = $this->entity;

        try
        {
            return $this->findOrFailTrashedByIdFromAPI($id);
        }
        catch(\Throwable $ex)
        {
            try
            {
                if ((EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                    and $this->validateExternalFetchEnabledForTokens())
                {
                    $params = [Token\Entity::ID => $id, 'deleted' => true];

                    return $this->fetchExternalToken($params);
                }
            }
            catch(\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message'     => $e->getMessage(),
                        'function_name'         => __FUNCTION__
                    ]);

            }

            throw $ex;
        }
    }

    /**
     * @throws \Throwable
     * @throws BadRequestException
     */
    public function findByPublicId($id, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByPublicId($id, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForTokens() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalToken([Token\Entity::ID=>$id]);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }
            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws BadRequestException
     */
    public function findByPublicIdAndMerchant(
        string $id,
        MerchantEntity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForTokens() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    $param[Token\Entity::MERCHANT_ID] = $merchant->getId();

                    $param[Token\Entity::ID] = $id;

                    $token = $this->fetchExternalToken($param);

                    if (method_exists($token, 'merchant') === true)
                    {
                        $token->merchant()->associate($merchant);
                    }

                    return $token;
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }
            throw $e;
        }
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findOrFail($id, $columns, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForTokens() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    $params[Token\Entity::ID] = $id;

                    return $this->fetchExternalToken($params);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }
            throw $e;
        }
    }

    public function getExternalTokensByCustomer($customer, $isPassUnusedRejectedTokensExperimentEnabled, $withVpas, $skipUsedAt=false)
    {
        $this->entityName = $this->entity;

        $apiTokens = $this->newQuery()
            ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
            ->where(function($query) use ($isPassUnusedRejectedTokensExperimentEnabled)
            {
                if ($isPassUnusedRejectedTokensExperimentEnabled === true)
                {
                    $query->whereNull(Token\Entity::USED_AT)
                        ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::REJECTED);
                }
                $query->orwhereNull(Token\Entity::USED_AT)
                    ->where(Token\Entity::FREQUENCY, '=', Token\Constants::ONE_TIME_FREQUENCY)
                    ->where(Token\Entity::RECURRING_STATUS, '!=', Token\RecurringStatus::INITIATED);
                $query->orWhereNotNull(Token\Entity::USED_AT);
            })
            ->where(function($query)
            {
                $query->whereNull(Token\Entity::EXPIRED_AT)
                    ->orWhere(Token\Entity::EXPIRED_AT, '>', time());
            })
            ->withVpaTokens($withVpas)
            ->orderBy(Token\Entity::CREATED_AT, 'desc')
            ->orderBy(Token\Entity::ID, 'desc')
            ->get();

        try
        {
            if ($this->validateExternalFetchEnabledForTokens() and
                (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
            {
                $params = [];

                $params[Token\Entity::CUSTOMER_ID] = $customer->getId();

                $class = Entity::getExternalRepoSingleton($this->entity);

                $externalTokens = $class->fetchCustomerTokens($params, $skipUsedAt);

                if(sizeof($externalTokens) > 0)
                {
                    return $this->mergeTokenEntitiesFromAPIAndTokensService($apiTokens, $externalTokens);
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        return $apiTokens;
    }

    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForTokens() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    $this->trace->info(TraceCode::TOKENS_FIND_OR_FAIL_BY_PUBLIC_ID_WITH_PARAMS, [
                        'function_name' => __FUNCTION__,
                        'params' => $params
                    ]);
                    $params['id'] = $id;

                    return  $this->fetchExternalToken($params);
                }
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }
        }

        return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
    }

    public function saveOrFail($token, array $options = array())
    {
        if (($token !== null) and ($token->isExternal() === true))
        {
            try
            {
                $this->entityName = $this->entity;

                if ($this->validateExternalUpdateEnabledForTokens() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    $params = $this->getUpdatableTokensField($token);

                    $params[Token\Entity::ID] = $token->getId();

                    $this->updateTokenById($params);
                }

                return;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKENS_ENTITY_UPDATE_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
                throw $ex;
            }
        }

        parent::saveOrFail($token, $options);
    }

    /**
     * @throws \Exception
     */
    public function deleteOrFail($token)
    {
        try
        {
            parent::deleteOrFail($token);
        }
        catch(\Exception $ex)
        {
            try
            {
                $tokenID = $token->getId();

                /** @var Tokens $externalRepo */
                $externalRepo = Entity::getExternalRepoSingleton($this->entity);
                $resp = $externalRepo->deleteTokensInternal($tokenID);
                if ($resp['code'] == 200)
                {
                    return;
                }
            }
            catch(\Exception $exExternal)
            {
                $this->trace->info(TraceCode::TOKENS_EXTERNAL_DELETE_FAILURE, [
                    'exception_msg' => $exExternal->getMessage(),
                ]);
            }

            throw $ex;
        }
    }

    public function getExternalTokensByIdsAndCustomerIds(array $ids, string $customerId): PublicCollection
    {
        try
        {
            /** @var Tokens $externalRepo */
            $externalRepo = Entity::getExternalRepoSingleton($this->entity);
            $resp = $externalRepo->fetchTokenByIdsInternal(
                [
                    'ids' => $ids,
                    'customer_id' => $customerId
                ],
            );
            if (!empty($resp['body']) && !empty($resp['body']['data']))
            {
                return $externalRepo->getPublicCollection($resp['body']['data']);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::TOKEN_EXTERNAL_FETCH_TOKEN_BY_IDS,[
                'error' => $ex->getMessage()
            ]);
        }

        return new PublicCollection();
    }

    public function fetchExternalTokens($params, $input=[])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            $entity = $class->fetchTokens($params);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function fetchExternalToken($params, $input=[])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            $entity = $class->fetchToken($params);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function fetchExternalTokenByIds($ids, $input=[])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            $entity = $class->fetchTokensByIds($ids);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);
            }

            return $entity;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => 'find_by_ids'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function updateTokenById($params)
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            return $class->updateToken($params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => 'update'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function getUpdatableTokensField($token)
    {
        $params[Token\Entity::USED_AT] = $token->getUsedAt();

        $params[Token\Entity::EXPIRED_AT] = $token->getExpiredAt();

        return $params;
    }

    public function mergeTokenEntitiesFromAPIAndTokensService(PublicCollection $apiTokens, PublicCollection $serviceTokens) : PublicCollection
    {
        $combinedTokens = [];

        foreach ($apiTokens->toArrayWithItems()[PublicCollection::ITEMS] as $item)
        {
            $combinedTokens[] = $item;
        }

        foreach ($serviceTokens->toArrayWithItems()[PublicCollection::ITEMS] as $item)
        {
            $combinedTokens[] = $item;
        }

        return new PublicCollection($combinedTokens);
    }

    public function validateExternalFetchEnabledForTokens() : bool
    {
        $keyName = Entity::getExternalConfigKeyName($this->entityName);

        $keyStatus = (bool) ConfigKey::get($keyName, false);

        return $keyStatus;
    }

    public function validateExternalUpdateEnabledForTokens() : bool
    {
        $keyName = Entity::getExternalConfigKeyName($this->entityName);

        $keyStatus = (bool) ConfigKey::get($keyName, false);

        if ($keyStatus === true)
        {
            try
            {
                $experimentId = $this->app['config']->get('app.external_updates_enabled_for_tokens');

                $properties = [
                    "id" => $this->app['request']->getTaskId(),
                    "experiment_id" => $experimentId,
                ];

                $response = $this->app['splitzService']->evaluateRequest($properties);

                $variant = 'control';

                if(!empty($response['response']['variant']) && isset($response['response']['variant']['name']))
                {
                    $variant = $response['response']['variant']['name'];
                }

                $this->trace->info(TraceCode::TOKENS_EXTERNAL_ENTITY_UPDATE_SPLITZ_EXPRIMENT_RESPONSE, [
                    'variant' => $variant,
                    'experiment_id' => $experimentId
                ]);

                return $variant === 'enable';
            }
            catch( \Throwable $ex)
            {
                $this->trace->error(TraceCode::TOKENS_EXTERNAL_ENTITY_UPDATE_SPLITZ_EXPRIMENT_FAILURE, [
                    'message' => $ex->getMessage(),
                ]);

                return false;
            }
        }
        return $keyStatus;
    }
}
