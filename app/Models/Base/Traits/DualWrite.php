<?php

namespace RZP\Models\Base\Traits;

Use RZP\Models\Base\Entity;

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

    /**
     * @throws \Throwable
     */
    public function saveOrFail(array $options = array())
    {
        $this->getConnection()->transaction(function () use ($options) {
            $strictDualWrite = $options[Entity::SAVE_OPTION_RAZORPAY_API_STRICT_DUAL_WRITE] ?? false;

            unset($options[Entity::SAVE_OPTION_RAZORPAY_API_STRICT_DUAL_WRITE]);

            $entityExists = $this->exists;

            parent::saveOrFail($options);

            // If env key not set for a table, dual writes will be disabled
            if ($this->isDualWriteEnabledViaEnv() !== true)
            {
                return;
            }

            try
            {
                $this->dualWrite          = true;
                $this->timestamps         = false;
                $this->exists             = $entityExists;
                $this->generateIdOnCreate = false;

                parent::saveOrFail($options);
            }
            catch(\Throwable $ex)
            {
                // original entity shouldn't have this modified even in case of failures,
                // as it can be accessed from different flows
                $this->dualWrite          = false;
                $this->timestamps         = true;
                $this->exists             = true;
                $this->generateIdOnCreate = true;

                if ($strictDualWrite === true)
                {
                    $this->exists = $entityExists;

                    throw $ex;
                }
            }

            $this->dualWrite          = false;
            $this->timestamps         = true;
            $this->generateIdOnCreate = true;
        });
    }

    private function isDualWriteEnabledViaEnv() : bool
    {
        $tableName = strval($this->getTable());

        if (empty($tableName) === false)
        {
            $dualWriteEnvKey = 'ENABLE_DUAL_WRITE_' . strtoupper($tableName);

            $dualWriteEnvValue = getenv($dualWriteEnvKey);

            return ($dualWriteEnvValue == true);
        }

        return false;
    }
}
