<?php

namespace RZP\Models\Base\Traits;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

trait ExternalReversalRepo
{
    protected $entityName;

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
                if ($this->validateIfExternalFetchIsEnabledForReversal() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalReversal($id);
                }
            }
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
    }

    public function findOrFailPublic($id, $columns = ['*'], string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findOrFailPublic($id, $columns, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForReversal() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    Reversal\Entity::stripSignWithoutValidation($id);

                    return $this->fetchExternalReversal($id);
                }
            }
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
    }


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
                if ($this->validateIfExternalFetchIsEnabledForReversal() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    Reversal\Entity::stripSignWithoutValidation($id);

                    return $this->fetchExternalReversal($id);
                }
            }
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
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
            return parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForReversal() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    Reversal\Entity::stripSignWithoutValidation($id);

                    return $this->fetchExternalReversal($id, $merchant->getId(), $params);
                }
            }
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
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
            return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForReversal() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalReversal($id, $merchant->getId(), $params);
                }
            }
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
    }

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForReversal() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalReversal($id, $merchantId);
                }
            }
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
    }

    protected function fetchExternalReversal($reversalId, $merchantId = null, $queryParams=[])
    {
        $class = EntityConstants::getExternalRepoSingleton($this->entity);

        $startTime = millitime();

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        try
        {
            $entity = $class->fetchReversalById($reversalId, $merchantId);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($queryParams);

                $entity->loadMissing($relations);

                $this->traceSuccessMetrics($callerFunc, $startTime);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FETCH_TRANSFER_VIA_ROUTE_SERVICE_FAILURE,
                [
                    'reversal_id' => $reversalId,
                    'data'         => $e->getMessage(),
                    'from'         => $callerFunc,

                ]);

            $this->traceFailureMetrics($callerFunc, $startTime);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => $callerFunc,
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    protected function validateIfExternalFetchIsEnabledForReversal()
    {
        $keyName = EntityConstants::getExternalConfigKeyName($this->entityName);

        return (bool) ConfigKey::get($keyName, false);
    }

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_REPO_FETCH_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_REPO_FETCH_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS, [
            'caller'      => $functionName,

        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

}
