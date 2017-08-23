<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Models\Base as BaseModel;

abstract class TypeProcessor extends BaseModel\Core
{
    public function process(array $fileContents)
    {
        $processedEntries = [];

        foreach ($fileContents as $row)
        {
            $id = $this->getId($row);

            try
            {
                $this->processEntry($row);

                $processedEntries['success'][] = $id;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $processedEntries['failure'][] = $id;
            }
        }

        return $processedEntries;
    }

    abstract protected function processEntry(array $entry);

    abstract protected function getId(array $entry);
}
