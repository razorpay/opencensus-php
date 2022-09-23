<?php

namespace RZP\Models\Base\Traits;

Use App;

use RZP\Constants\Metric;
Use RZP\Models\Base\Entity;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestValidationFailureException;

// Note : This trait is not yet tested to support ElasticSearch updates on new table
trait DualWrite
{
    public function getTable()
    {
        if ($this->dualWrite === true)
        {
            return parent::getTable() . '_new';
        }

        return parent::getTable();
    }

    public function getDirty()
    {
        // passing all attributes in dirty as there is no option to pass data locally identifying just updated columns
        if ($this->dualWrite === true)
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

            $entityExists = $this->exists;

            parent::saveOrFail($options);

            // If env key not set for a table, dual writes will be disabled
            if ($this->isDualWriteEnabledViaEnv() !== true)
            {
                return;
            }

            $dualWriteStartTime = millitime();

            if ($entityExists === false)
            {
                $this->upsert($strictDualWrite, false, false, $options);
            }

            $this->validateAndUpsert($strictDualWrite, $entityExists, $options);

            $dualWriteDuration = millitime() - $dualWriteStartTime;

            $app = App::getFacadeRoot();

            $app['trace']->histogram(Metric::DUAL_WRITES_TIME_TAKEN, $dualWriteDuration);
        });
    }

    /**
     * @throws \Throwable
     */
    public function upsert($strictDualWrite, bool $parentEntityExists, bool $dualEntityExists, array $options = array())
    {
        $app = App::getFacadeRoot();

        $actionType = $this->getOperationTypeForMetrics($parentEntityExists, $dualEntityExists);

        try
        {
            $this->dualWrite          = true;
            $this->timestamps         = false;
            $this->exists             = $dualEntityExists;
            $this->generateIdOnCreate = false;

            $app['trace']->count(Metric::DUAL_WRITES_TOTAL, [
                'table'  => $this->getTable(),
                'action' => $actionType,
            ]);

            parent::saveOrFail($options);
        }
        catch(\Throwable $ex)
        {
            $app['trace']->count(Metric::DUAL_WRITES_FAILED, [
                'table'  => $this->getTable(),
                'action' => $actionType,
            ]);

            $app['trace']->traceException($ex);

            // original entity shouldn't have this modified even in case of failures,
            // as it can be accessed from different flows
            $this->dualWrite          = false;
            $this->timestamps         = true;
            $this->exists             = true;
            $this->generateIdOnCreate = true;

            if ($strictDualWrite === true)
            {
                $this->exists = $parentEntityExists;

                throw $ex;
            }
        }

        $this->dualWrite          = false;
        $this->timestamps         = true;
        $this->generateIdOnCreate = true;
    }

    /**
     * @throws \Throwable
     */
    public function validateAndUpsert($strictDualWrite, $parentEntityExists, array $options = array())
    {
        if ($this->isDualWriteEnabledViaEnv('update') === false)
        {
            return;
        }

        $repo = $this->initialiseRepo();

        $this->dualWrite = true;

        $dualEntityExists = $repo->existsInTable($this->getTable(), $this->getId());

        // insert the new record/update if present
        $this->upsert($strictDualWrite, $parentEntityExists, $dualEntityExists, $options);
    }

    private function isDualWriteEnabledViaEnv(string $operation = '') : bool
    {
        $tableName = strval($this->getTable());

        if (empty($tableName) === false)
        {
            $dualWriteEnvKey = 'ENABLE_DUAL_WRITE_' . strtoupper($tableName);

            // Enabling updates through a different key
            if ($operation === 'update')
            {
                $dualWriteEnvKey = 'ENABLE_DUAL_WRITE_UPDATE_' . strtoupper($tableName);
            }

            $dualWriteEnvValue = getenv($dualWriteEnvKey);

            return ($dualWriteEnvValue == true);
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
}
