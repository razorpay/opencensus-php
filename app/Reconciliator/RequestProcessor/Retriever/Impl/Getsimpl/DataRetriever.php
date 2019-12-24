<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl\Getsimpl;

use RZP\Reconciliator\RequestProcessor\Retriever\Impl\PaginatedDataRetriever;

class DataRetriever extends PaginatedDataRetriever
{
    protected function refactorResponse(array $responseList): array
    {
        $output = [];

        foreach ($responseList as $key => $value)
        {
            if (isset($value['data']['records']))
            {
                $output = array_merge($output, $value['data']['records']);
            }
        }

        if (empty($output) === true)
        {
            return [];
        }
        return ['_' => $output];
    }
}