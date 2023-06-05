<?php

namespace RZP\Models\Base\Traits;

use App;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;

trait ExternalRepo
{
    use ArchivedCore;

    protected $entityName;

    public function findByPublicId($id, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = $this->findByPublicIdArchived($id, $connectionType);

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

    public function findByPublicIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = $this->findByPublicIdAndMerchantArchived($id, $merchant, $params, $connectionType);

            $class = Entity::getExternalRepoSingleton($this->entity);

            $this->handleOrderExpands($params,$this->entity, $entity, $id, $class, $merchant->getId());

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

    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = $this->findByIdAndMerchantArchived($id, $merchant, $params, $connectionType);

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

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = $this->findByIdAndMerchantIdArchived($id, $merchantId, $connectionType);

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
            $entity = $this->findOrFailByPublicIdWithParamsArchived($id, $params, $connectionType);

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

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = $this->findOrFailPublicArchived($id, $columns, $connectionType);

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

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $entity = $this->findOrFailArchived($id, $columns, $connectionType);

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

    /**
     * Returns external repo payment entity
     *
     * @param  string $paymentID payment id
     * @param  string $merchantID merchant_id
     * @return Payment\Entity
     * @throws Exception\BadRequestException
     */
    public function fetchExternalPaymentEntity(string $paymentID, string $merchantID): Payment\Entity
    {
        $this->entityName = $this->entity;

        return $this->fetchExternalEntity($paymentID, $merchantID);
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

                $this->handleOrderExpands($input,$this->entity, $entity, $id, $class, $merchantId);

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

    protected function handleOrderExpands(array & $expands, $entityType, $entity, $id, $class, $merchantId)
    {
        if (($entityType === Entity::ORDER) and (array_key_exists("expand",$expands) === true))
        {
            $id = Order\Entity::verifyIdAndSilentlyStripSign($id);

            //relations --> payments,payments.card
            if (in_array("payments.card",  $expands['expand']) === true)
            {
                $apiPayments = $this->repo->payment->fetchPaymentsWithCardForOrderId($id);

                $rearchPayments = $class->fetchOrderPayments($id, $merchantId, true);

                $res = $apiPayments->merge($rearchPayments);

                $entity->payments = $res->toArrayPublic();

                // in case payments.card it is not a relation so while loading the relations its failing,
                // so we need to unset that key
                $key = array_search("payments.card", $expands[self::EXPAND]);

                unset($expands[self::EXPAND][$key]);
            }
            else if (in_array("payments", $expands['expand']) === true)
            {
                $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($id);

                $rearchPayments = $class->fetchOrderPayments($id, $merchantId);

                $res = $apiPayments->merge($rearchPayments);

                $entity->payments = $res->toArrayPublic();

                $key = array_search("payments", $expands[self::EXPAND]);

                unset($expands[self::EXPAND][$key]);
            }
        }
    }

    public function serializeForIndexingForExternal(PublicEntity $entity): array
    {
        return $this->serializeForIndexing($entity);
    }
}
