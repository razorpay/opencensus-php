<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;

class DefaultDataRetriever extends AbstractAPIDataRetriever
{

    protected function getNextRequest(array $input, $prevRequest, $prevResponse): array
    {
        if (empty($prevRequest) === false)
        {
            return [];
        }

        $request = [];

        $request[self::GATEWAY] = $input['gateway'];
        $request[self::IDENTIFIER] = '_';

        $request[self::START_DATE] = date('Y-m-d', strtotime('-1 days'));
        $request[self::END_DATE] = date('Y-m-d');

        if (isset($input['start_date']) === true)
        {
            $request['start_date'] = $input['start_date'];
        }

        if (isset($input['end_date']) === true)
        {
            $request['end_date'] = $input['end_date'];
        }

        if (isset($input['meta_data']) === true)
        {
            $request['meta_data'] = $input['meta_data'];
        }

        return $request;
    }

    protected function refactorResponse(array $responseList): array
    {
        $output = [];

        foreach ($responseList as $key => $value)
        {
            $output[$key] =  $value['data']['records'];
        }

        return $output;
    }
}