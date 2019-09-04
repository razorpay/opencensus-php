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
        $request['gateway'] = $input['gateway'];
        if(isset($input['start_date'])) {
            $request['start_date'] = $input['start_date'];
        }
        if(isset($input['end_date'])) {
            $request['end_date'] = $input['end_date'];
        }
        if(isset($input['meta_data'])) {
            $request['meta_data'] = $input['meta_data'];
        }
        array_push($requestList, $request);
        return $requestList;
    }

    protected function fetchTerminal(array $input, $request)
    {
        $terminal = $this->repo->terminal->findByGateway($request['gateway']);
        return $terminal;
    }

    protected function processRequest(array $input, $request, $terminal)
    {
        $gatewayData = [];
        $gatewayData['terminal'] = $terminal;
        $gatewayData['gateway'] = $request['gateway'];
        $gatewayData['payment'] = ["gateway" => $request['gateway'], ];
        $gatewayData['entities'] = $request;
        $this->trace->info(TraceCode::CRAWLER_RECONCILE, ["Gateway Request : ",$gatewayData, $terminal]);
        return ["__", $this->gatewayManager->call($request['gateway'], 'reconcile', $gatewayData, $this->mode, $terminal)];
    }

    protected function refactorResponse(array $responseList): array{
        $output = [];
        foreach ($responseList as $response){
            array_push($output, $response['data']['records']);
        }
        return $output;
    }
}