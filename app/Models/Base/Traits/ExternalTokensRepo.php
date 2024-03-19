<?php

namespace RZP\Models\Base\Traits;

use PHPUnit\Framework\Exception;

use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
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
                    $params[Token\Entity::MERCHANT_ID] = $merchant->getId();

                    $params[Token\Entity::ID] = $id;

                    $token = $this->fetchExternalToken($params);

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

    public function getExternalTokensByCustomer($customer, $isPassUnusedRejectedTokensExperimentEnabled, $withVpas)
    {
        $this->entityName = $this->entity;

        $apiTokens = $this->newQuery()
            ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
            ->where(function($query) use ($isPassUnusedRejectedTokensExperimentEnabled)
            {
                if (strtolower($isPassUnusedRejectedTokensExperimentEnabled) === 'on')
                {
                    $query->whereNull(Token\Entity::USED_AT)
                        ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::REJECTED);
                }
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

                $externalTokens = $class->fetchCustomerTokens($params);

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

        if ($keyStatus === true)
        {
            $mode = $this->app['rzp.mode'] ?? 'live';

            $result = $this->app['razorx']->getTreatment(
                UniqueIdEntity::generateUniqueId(),
                RazorxTreatment::ENTITY_RELATIONAL_LOAD_FROM_TOKENS_SERVICE,
                $mode);

            $this->trace->info(
                TraceCode::TOKENS_ENTITY_FETCH_RAZORX_EXPERIMENT_RESPONSE,
                [
                    'result'    => $result,
                    'mode'      => $mode,
                    'key_status'=> $keyStatus,
                ]);

            if ($result === 'on')
            {
                return true;
            }
        }

        return false;
    }
}
