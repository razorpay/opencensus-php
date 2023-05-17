<?php

namespace RZP\Models\Base\Traits;

Use App;

use Carbon\Carbon;
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

            // Sometimes model is fetched from data warehouse that contain additional columns
            // Unsetting them before saving into hot storage
            $recordSourceAttributePresent = false;
            $recordSourceAttributeValue   = null;

            $attributes = $this->getAttributes();

            if (isset($attributes[Entity::RECORD_SOURCE]) === true)
            {
                $recordSourceAttributePresent = true;
                $recordSourceAttributeValue   = $attributes[Entity::RECORD_SOURCE];

                $this->unsetModelAttribute(Entity::RECORD_SOURCE);
            }

            $entityExists = $this->exists;

            parent::saveOrFail($options);

            // If env key not set for a table, dual writes will be disabled
            if ($this->isDualWriteEnabledViaEnv() !== true)
            {
                if ($recordSourceAttributePresent === true)
                {
                    $this->setModelAttribute(Entity::RECORD_SOURCE, $recordSourceAttributeValue);
                }

                return;
            }

            $dualWriteStartTime = millitime();

            $this->validateAndUpsert($strictDualWrite, $entityExists, $options);

            App::getFacadeRoot()['trace']->histogram(Metric::DUAL_WRITES_TIME_TAKEN, millitime() - $dualWriteStartTime);

            if ($recordSourceAttributePresent === true)
            {
                $this->setModelAttribute(Entity::RECORD_SOURCE, $recordSourceAttributeValue);
            }
        });

        // set archived value to false post successful save
        $this->setArchived(false);
    }

    /**
     * @throws \Throwable
     */
    public function upsert($strictDualWrite, bool $parentEntityExists, bool $dualEntityExists, array $options = array())
    {
        $trace = App::getFacadeRoot()['trace'];

        $actionType = $this->getOperationTypeForMetrics($parentEntityExists, $dualEntityExists);

        $originalTimeStampsValue = $this->timestamps;

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

            try
            {
                parent::saveOrFail($options);
            }
            catch(\Throwable $ex)
            {
                // SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
                // Retrying above case as a concurrent upsert might have just happened
                if (($ex->getCode() === '23000') and (str_contains($ex->getMessage(), '1062') === true))
                {
                    // Marking dual entity as exists so that an update will be performed with saveOrFail
                    $this->exists     = true;
                    $dualEntityExists = true;

                    parent::saveOrFail($options);
                }
                else
                {
                    throw $ex;
                }
            }
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
            $this->timestamps         = $originalTimeStampsValue;
            $this->exists             = true;
            $this->generateIdOnCreate = true;

            $this->setDualWrite(false);

            if ($strictDualWrite === true)
            {
                $this->exists = $parentEntityExists;

                throw $ex;
            }
        }

        $this->timestamps         = $originalTimeStampsValue;
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

    /**
     * @throws \Throwable
     */
    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        $repo = $this->initialiseRepo();

        $this->exists = $repo->existsInTable($this->getTable(), $this->getId());

        $entityExists = $this->exists;

        // Sometimes model is fetched from data warehouse that contain additional columns
        // Unsetting them before saving into hot storage
        $recordSourceAttributePresent = false;
        $recordSourceAttributeValue   = null;

        $attributes = $this->getAttributes();

        if (isset($attributes[Entity::RECORD_SOURCE]) === true)
        {
            $recordSourceAttributePresent = true;
            $recordSourceAttributeValue   = $attributes[Entity::RECORD_SOURCE];

            $this->unsetModelAttribute(Entity::RECORD_SOURCE);
        }

        if ($this->exists === false)
        {
            $originalTimeStampsValue          = $this->timestamps;
            $originalGenerateIdOnCreateValue  = $this->generateIdOnCreate;

            // on reinsert to DB, created_at should remain as the original timestamp and updated_at as the current timestamp
            $this->timestamps         = false;
            $this->generateIdOnCreate = false;

            $this->{Entity::UPDATED_AT} = Carbon::now()->getTimestamp();

            parent::saveOrFail();

            $this->timestamps         = $originalTimeStampsValue;
            $this->generateIdOnCreate = $originalGenerateIdOnCreateValue;
        }

        parent::incrementOrDecrement($column, $amount, $extra, $method);

        if ($this->isDualWriteEnabledViaEnv() !== true)
        {
            if ($recordSourceAttributePresent === true)
            {
                $this->setModelAttribute(Entity::RECORD_SOURCE, $recordSourceAttributeValue);
            }

            return;
        }

        $dualWriteStartTime = millitime();

        $this->validateAndUpsert(false, $entityExists);

        App::getFacadeRoot()['trace']->histogram(Metric::DUAL_WRITES_TIME_TAKEN, millitime() - $dualWriteStartTime);

        // set archived value to false post successful save
        $this->setArchived(false);

        if ($recordSourceAttributePresent === true)
        {
            $this->setModelAttribute(Entity::RECORD_SOURCE, $recordSourceAttributeValue);
        }
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

    private function unsetModelAttribute(string $key)
    {
        unset($this->attributes[$key]);

        unset($this->{$key});
    }

    private function setModelAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;

        $this->{$key} = $value;
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
