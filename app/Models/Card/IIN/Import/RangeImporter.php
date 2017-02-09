<?php

namespace RZP\Models\Card\IIN\Import;

class RangeImporter extends Base
{
    public function import($input)
    {
        if (isset($input['network']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please pass network name as input for given file');
        }

        $formattedData = (new Formatter)->formatIinDataRange($input, $input['range']);

        $dataCleaner = new DataCleaner();
        $cleaned = $dataCleaner->parse($input['network'], $formattedData);
        $duplicates = $dataCleaner->getDuplicateEntries();
        $conflicts = $dataCleaner->getDBConflicts();
        $networkCheckFails = $dataCleaner->getNetworkCheckFails();

        $this->enterIntoDB($cleaned);
        $this->updateIntoDB($conflicts);

        $successCount = count($cleaned);

        return [
            'duplicates'     => $duplicates,
            'db_conflicts'   => $conflicts,
            'network_errors' => $networkCheckFails,
            'success'        => $successCount,
        ];
    }
}