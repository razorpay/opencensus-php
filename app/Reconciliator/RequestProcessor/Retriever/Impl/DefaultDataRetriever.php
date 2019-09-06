<?php


namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

class DefaultDataRetriever extends AbstractAPIDataRetriever
{


    protected function prepareGatewayRequestArray(array $input): array
    {
        $requestList = [];
        $request = [];
        $request[self::GATEWAY] = $input['gateway'];
        $request[self::IDENTIFIER] = "_";
        if(isset($input['start_date'])) {
            $request['start_date'] = $input['start_date'];
        }else{
            $request['start_date'] = date('Y-m-d', strtotime("-1 days"));
        }
        if(isset($input['end_date'])) {
            $request['end_date'] = $input['end_date'];
        }else{
            $request['end_date'] = date('Y-m-d');
        }
        if(isset($input['meta_data'])) {
            $request['meta_data'] = $input['meta_data'];
        }
        array_push($requestList, $request);
        return $requestList;
    }

    protected function refactorResponse(array $responseList): array{
        $output = [];
        foreach ($responseList as $key => $value)   {
            $output[$key] =  $value['data']['records'];
        }
        return $output;
    }
}