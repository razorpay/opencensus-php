<?php

namespace RZP\Models\Base\Traits;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;


trait ExternalRepo
{
    protected $entityName;

    public function findByPublicId($id)
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findByPublicId($id);

            return $entity;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id);
    }

    public function findByPublicIdAndMerchant(string $id, Merchant\Entity $merchant,array $params = []): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findByPublicIdAndMerchant($id, $merchant, $params);

            return $entity;
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id, $merchant->getId(), $params);
    }

    public function findByIdAndMerchant(string $id, Merchant\Entity $merchant,array $params = []): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findByIdAndMerchant($id, $merchant, $params);

            return $entity;
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id, $merchant->getId(), $params);
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findByIdAndMerchantId($id, $merchantId);

            return $entity;
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id, $merchantId);
    }

    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);

            return $entity;
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id, "", $params);
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findOrFailPublic($id, $columns);

            return $entity;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id, "");
    }

    public function findOrFail($id, $columns = array('*'))
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = parent::findOrFail($id, $columns);

            return $entity;
        }
        catch (\Throwable $e)
        {
            if (Entity::validateExternalRepoEntity($this->entityName) === false || $this->validateExternalFetchEnabled() == false)
            {
                throw $e;
            }
        }

        return $this->fetchExternalEntity($id, "");
    }

    private function validateExternalFetchEnabled()
    {
        if (app()->runningUnitTests() === true)
        {
            $keyName = Entity::getExternalConfigKeyName($this->entityName);

            return (bool) ConfigKey::get($keyName, false);
        }

        return true;
    }

    private function fetchExternalEntity($id, $merchantId = '', $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            $entity = $class->fetch($this->entity, $id, $merchantId, $input);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

                $relations = $this->getExpandsForQueryFromInput($input);

                $this->handleOrderExpands($input,$this->entity, $entity, $id, $class, $merchantId);

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
                    'data' => $e->getMessage()
                ]);
        }

        $data = [
            'model' => $this->entityName,
            'attributes' => $id,
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    protected function handleOrderExpands($expands, $entityType, $entity, $id, $class, $merchantId)
    {
        if (($entityType === Entity::ORDER) and (array_key_exists("expands",$expands) === true))
        {
            //relations --> payments,payments.card
            if (in_array("payments.card",  $expands['expands']) === true)
            {
                $apiPayments = $this->repo->payment->fetchPaymentsWithCardForOrderId($id);

                $rearchPayments = $class->fetchOrderPayments($id, $merchantId, true);

                $res = $apiPayments->merge($rearchPayments);

                $entity->payments = $res->toArrayPublic();
            }
            else if (in_array("payments", $expands['expands']) === true)
            {
                $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($id);

                $rearchPayments = $class->fetchOrderPayments($id, $merchantId);

                $res = $apiPayments->merge($rearchPayments);

                $entity->payments = $res->toArrayPublic();
            }
        }
    }

    public function serializeForIndexingForExternal(PublicEntity $entity): array
    {
        return $this->serializeForIndexing($entity);
    }
}
