<?php

namespace RZP\Models\Base\Traits;

Use App;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
Use RZP\Models\Base\Entity;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestValidationFailureException;

// Note : This trait is not yet tested to support ElasticSearch updates on new table
trait DualWrite
{
    use ArchivedEntity;

    protected $dualWrite = false;

    public function setDualWrite(bool $dualWrite)
    {
        $this->dualWrite = $dualWrite;
    }

    public function dualWrite(): bool
    {
        return $this->dualWrite;
    }

    public function getTable()
    {
        if ($this->dualWrite() === true)
        {
            return parent::getTable() . '_new';
        }

        return parent::getTable();
    }

    public function getDirty()
    {
        // passing all attributes in dirty as there is no option to pass data locally identifying just updated columns
        if ($this->dualWrite() === true)
        {
            $dirty = [];

            foreach ($this->getAttributes() as $key => $value)
            {
                $dirty[$key] = $value;
            }

            return $dirty;
        }

        return parent::getDirty();
    }

    /**
     * @throws \Throwable
     */
    public function saveOrFail(array $options = array())
    {
        $this->getConnection()->transaction(function () use ($options)
        {
            $strictDualWrite = $options[Entity::SAVE_OPTION_RAZORPAY_API_STRICT_DUAL_WRITE] ?? false;

            unset($options[Entity::SAVE_OPTION_RAZORPAY_API_STRICT_DUAL_WRITE]);

            if ($this->isArchived() === true)
            {
                $repo = $this->initialiseRepo();

                $this->exists = $repo->existsInTable($this->getTable(), $this->getId());
            }

            $entityExists = $this->exists;

            parent::saveOrFail($options);

            // If env key not set for a table, dual writes will be disabled
            if ($this->isDualWriteEnabledViaEnv() !== true)
            {
                return;
            }

            $dualWriteStartTime = millitime();

            $this->validateAndUpsert($strictDualWrite, $entityExists, $options);

            App::getFacadeRoot()['trace']->histogram(Metric::DUAL_WRITES_TIME_TAKEN, millitime() - $dualWriteStartTime);
        });
    }

    /**
     * @throws \Throwable
     */
    public function upsert($strictDualWrite, bool $parentEntityExists, bool $dualEntityExists, array $options = array())
    {
        $trace = App::getFacadeRoot()['trace'];

        $actionType = $this->getOperationTypeForMetrics($parentEntityExists, $dualEntityExists);

        try
        {
            $this->timestamps         = false;
            $this->exists             = $dualEntityExists;
            $this->generateIdOnCreate = false;

            $this->setDualWrite(true);

            $trace->count(Metric::DUAL_WRITES_TOTAL, [
                'table'  => $this->getTable(),
                'action' => $actionType,
            ]);

            parent::saveOrFail($options);
        }
        catch(\Throwable $ex)
        {
            $trace->count(Metric::DUAL_WRITES_FAILED, [
                'table'  => $this->getTable(),
                'action' => $actionType,
            ]);

            $trace->traceException($ex,
                Trace::ERROR,
                TraceCode::DUAL_WRITE_EXCEPTION,
                [
                    'id'     => $this->getId(),
                    'table'  => $this->getTable(),
                    'action' => $actionType,
                ]);

            // original entity shouldn't have this modified even in case of failures,
            // as it can be accessed from different flows
            $this->timestamps         = true;
            $this->exists             = true;
            $this->generateIdOnCreate = true;

            $this->setDualWrite(false);

            if ($strictDualWrite === true)
            {
                $this->exists = $parentEntityExists;

                throw $ex;
            }
        }

        $this->timestamps         = true;
        $this->generateIdOnCreate = true;

        $this->setDualWrite(false);
    }

    /**
     * @throws \Throwable
     */
    public function validateAndUpsert($strictDualWrite, $parentEntityExists, array $options = array())
    {
        $repo = $this->initialiseRepo();

        $this->setDualWrite(true);

        if ($parentEntityExists === false)
        {
            $dualEntityExists = false;

            if ($this->isArchived() === true)
            {
                $dualEntityExists = $repo->existsInTable($this->getTable(), $this->getId());
            }

            $this->upsert($strictDualWrite, false, $dualEntityExists, $options);

            return;
        }

        $dualEntityExists = $repo->existsInTable($this->getTable(), $this->getId());

        // insert the new record/update if present
        $this->upsert($strictDualWrite, $parentEntityExists, $dualEntityExists, $options);
    }

    private function isDualWriteEnabledViaEnv() : bool
    {
        $app = App::getFacadeRoot();

        $originalValue = $this->dualWrite();

        // To get original table name always for env key
        $this->setDualWrite(false);

        $tableName = strval($this->getTable());

        // reset dual write value
        $this->setDualWrite($originalValue);

        if (empty($tableName) === true)
        {
            return false;
        }

        $dualWriteEnvKey = 'ENABLE_DUAL_WRITE_' . strtoupper($tableName);

        $dualWriteEnvValue = getenv($dualWriteEnvKey);

        // Logging critical info for debugging
        if ($tableName === 'payments')
        {
            $app['trace']->info(TraceCode::DUAL_WRITE_CONFIG, [
                'id'                   => $this->getId(),
                $dualWriteEnvKey       => $dualWriteEnvValue,
                'runningInQueue'       => $app->runningInQueue(),
                'instance_type'        => getenv('INSTANCE_TYPE'),
                'is_worker_pod_env'    => getenv('IS_WORKER_POD'),
                'is_worker_pod_config' => $app['config']->get('worker.is_worker_pod'),
            ]);
        }

        // Note : Explicitly setting `==` to handle env datatype conversions. Do not change to `===`
        if ($dualWriteEnvValue == true)
        {
            return true;
        }

        $isWorkerPod = ($app->runningInQueue() === true);

        // Loading from config key in workers
        if ($isWorkerPod === true)
        {
            return $this->isDualWriteConfigKeyEnabled($tableName);
        }

        return false;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function initialiseRepo()
    {
        $repoName = E::getEntityRepository($this->entity);

        return new $repoName;
    }

    private function getOperationTypeForMetrics(bool $parentEntityExists, bool $dualEntityExists) : string
    {
        if ($parentEntityExists === true)
        {
            return $dualEntityExists ? 'update' : 'upsert';
        }

        return 'insert';
    }

    private function isDualWriteConfigKeyEnabled($tableName): bool
    {
        if (isset(E::$dualWriteConfigKey[$tableName]) === true)
        {
            $keyName = E::$dualWriteConfigKey[$tableName];

            return (bool) ConfigKey::get($keyName, false);
        }

        return false;
    }
}
