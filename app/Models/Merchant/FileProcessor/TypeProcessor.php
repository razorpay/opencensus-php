<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Models\Base as BaseModel;

class TypeProcessor extends BaseModel\Core
{
    public function process(array $fileDetails)
    {
        $processedIds = [];

        foreach ($fileDetails as $row)
        {
            $processed = false;

            try
            {
                $paymentId = $row['payment_id'];

                $processed = $this->processEntry($row);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }

            if ($processed === true)
            {
                $processedIds[] = $paymentId;
            }
        }

        return $processedIds;
    }

    /**
     * This method needs to be implemented by the child classes.
     *
     * @param array $entry
     *
     */
    protected function processEntry(array $entry)
    {
        throw new \BadMethodCallException();
    }
}
