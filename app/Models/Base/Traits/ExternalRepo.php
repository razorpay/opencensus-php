<?php

namespace RZP\Models\Base\Traits;

use App;
use RZP\Constants\Environment;
use RZP\Constants\Metric;
use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Constants\Entity as EntityConstants;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;

trait ExternalRepo
{
    use ArchivedCore;

    protected $entityName;

    public function findByPublicId($id, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if (Entity::validateExternalRepoEntity($this->entityName) === true and $this->validateExternalFetchEnabled() === true )
                    {
                        return $this->fetchExternalEntity($id);
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    return parent::findByPublicId($id, $connectionType);
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    return parent::findByPublicId($id, $connectionType);
                }
                catch (\Throwable $e) {}

                try
                {
                    if (Entity::validateExternalRepoEntity($this->entityName) === true and $this->validateExternalFetchEnabled() === true )
                    {
                        return $this->fetchExternalEntity($id);
                    }
                }
                catch (\Throwable $e) {}
            }

        }
        else
        {
            try
            {
                return parent::findByPublicId($id, $connectionType);
            }
            catch (\Throwable $e) {}

            try
            {
                if (Entity::validateExternalRepoEntity($this->entityName) === true and $this->validateExternalFetchEnabled() === true )
                {
                    return $this->fetchExternalEntity($id);
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findByPublicIdArchived($id);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findByPublicIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, $merchant->getId(), $params);
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    $entity =  parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);

                    $class = Entity::getExternalRepoSingleton($this->entity);

                    $this->handleOrderExpands($params,$this->entity, $entity, $id, $class, $merchant->getId());

                    return $entity;
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    $entity =  parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);

                    $class = Entity::getExternalRepoSingleton($this->entity);

                    $this->handleOrderExpands($params,$this->entity, $entity, $id, $class, $merchant->getId());

                    return $entity;
                }
                catch (\Throwable $e) {}

                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, $merchant->getId(), $params);
                    }
                }
                catch (\Throwable $e) {}
            }

        }
        else
        {
            try
            {
                $entity =  parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);

                $class = Entity::getExternalRepoSingleton($this->entity);

                $this->handleOrderExpands($params,$this->entity, $entity, $id, $class, $merchant->getId());

                return $entity;
            }
            catch (\Throwable $e) {}

            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalEntity($id, $merchant->getId(), $params);
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            $entity =  $this->findByPublicIdAndMerchantArchived($id, $merchant, $params);

            $class = Entity::getExternalRepoSingleton($this->entity);

            $this->handleOrderExpands($params,$this->entity, $entity, $id, $class, $merchant->getId());

            return $entity;
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, $merchant->getId(), $params);
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
                }
                catch (\Throwable $e) {}

                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, $merchant->getId(), $params);
                    }
                }
                catch (\Throwable $e) {}
            }

        }
        else
        {
            try
            {
                return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
            }
            catch (\Throwable $e) {}

            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalEntity($id, $merchant->getId(), $params);
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findByIdAndMerchantArchived($id, $merchant, $params);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, $merchantId);
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
                }
                catch (\Throwable $e) {}

                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, $merchantId);
                    }
                }
                catch (\Throwable $e) {}
            }

        }
        else
        {
            try
            {
                return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
            }
            catch (\Throwable $e) {}

            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalEntity($id, $merchantId);
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findByIdAndMerchantIdArchived($id, $merchantId);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, "", $params);
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
                }
                catch (\Throwable $e) {}

                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, "", $params);
                    }
                }
                catch (\Throwable $e) {}
            }
        }
        else
        {
            try
            {
                return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
            }
            catch (\Throwable $e) {}

            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalEntity($id, "", $params);
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findOrFailByPublicIdWithParamsArchived($id, $params);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findOrFailByPublicIdWithParamsForApiPaymentFetch($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            try
            {
                return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findOrFailByPublicIdWithParamsArchived($id, $params);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, "");
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    return parent::findOrFailPublic($id, $columns, $connectionType);
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    return parent::findOrFailPublic($id, $columns, $connectionType);
                }
                catch (\Throwable $e) {}

                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, "");
                    }
                }
                catch (\Throwable $e) {}
            }
        }
        else
        {
            try
            {
                return parent::findOrFailPublic($id, $columns, $connectionType);
            }
            catch (\Throwable $e) {}

            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalEntity($id, "");
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findOrFailPublicArchived($id, $columns);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        if ($this->entity === Entity::PAYMENT)
        {
            if (Environment::isLowerEnvironment($this->app['env']) === false
                and Environment::isEnvironmentQA($this->app['env']) === false) {
                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, "");
                    }
                }
                catch (\Throwable $e) {}

                try
                {
                    return parent::findOrFail($id, $columns, $connectionType);
                }
                catch (\Throwable $e) {}
            } else {
                try
                {
                    return parent::findOrFail($id, $columns, $connectionType);
                }
                catch (\Throwable $e) {}

                try
                {
                    if ($this->validateExternalFetchEnabled() === true)
                    {
                        return $this->fetchExternalEntity($id, "");
                    }
                }
                catch (\Throwable $e) {}
            }
        }
        else
        {
            try
            {
                return parent::findOrFail($id, $columns, $connectionType);
            }
            catch (\Throwable $e) {}

            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalEntity($id, "");
                }
            }
            catch (\Throwable $e) {}
        }

        try
        {
            return $this->findOrFailOnlyArchived($id, $columns);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabledForLaPayment() === true)
                {
                    return $this->fetchExternalLinkedAccountPaymentEntity($id, "");
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
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

    private function validateExternalFetchEnabledForLaPayment()
    {
        if ($this->entity !== Entity::PAYMENT)
        {
            return false;
        }

        $keyName = Entity::getExternalConfigKeyName(EntityConstants::PAYMENT_METHOD_TRANSFER);

        return (bool) ConfigKey::get($keyName, false);
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

    private function fetchExternalLinkedAccountPaymentEntity($id, $merchantId = '', $input = [])
    {
        $class = Entity::getExternalRepoSingleton('transfer');

        Payment\Entity::silentlyStripSign($id);

        $startTime = millitime();

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        if (empty($id))
        {
            $data = [
                'model' => $this->entityName,
                'attributes' => [
                    'id'       => $id,
                    'input'    => $input,
                ],
                'operation' => $callerFunc,
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
        }

        try
        {
            $entity = $class->fetchPaymentById($id);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                $this->traceSuccessMetrics($callerFunc, $startTime);

                return $entity;
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->warning(
                TraceCode::FETCH_PAYMENT_VIA_ROUTE_SERVICE,
                [
                    'id'         => $id,
                    'data'       => $e->getMessage(),
                    'from'       => $callerFunc,
                ]);

            $this->traceFailureMetrics($callerFunc, $startTime);
        }

        $data = [
            'model' => $this->entityName,
            'attributes' => [
                'id'       => $id,
                'input'    => $input,
            ],
            'operation' => $callerFunc,
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
                $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($id, $merchantId);

                $rearchPayments = $class->fetchOrderPayments($id, $merchantId);

                $res = $apiPayments->merge($rearchPayments);

                $entity->payments = $res->toArrayPublic();

                $key = array_search("payments", $expands[self::EXPAND]);

                unset($expands[self::EXPAND][$key]);
            }
        }
    }

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_SUCCESS, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    public function serializeForIndexingForExternal(PublicEntity $entity): array
    {
        return $this->serializeForIndexing($entity);
    }
}
