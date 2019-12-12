<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl\Paypal;

use function Clue\StreamFilter\append;
use RZP\Reconciliator\RequestProcessor\Retriever\Impl\PaginatedDataRetriever;

class DataRetriever extends PaginatedDataRetriever
{
    protected function refactorResponse(array $responseList): array
    {
        $output = [];
        $mergedRecords = [];

        foreach ($responseList as $key => $value)
        {
            if (isset($value['data']['records']))
            {
                $refactoredList = array_map(function($object){
                    return $object['Response'];
                }, $value['data']['records']);
                $mergedRecords = array_merge($mergedRecords, $refactoredList);
            }
        }

        if (empty($mergedRecords) === false)
        {
            foreach ($mergedRecords as $key => $value)
            {
                if (isset($value['record']) === false)
                {
                    array_push($output, $value);
                }
            }
        }

        if (empty($output) === true)
        {
            return [];
        }
        return ['_' => $output];
    }
}