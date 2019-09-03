<?php


namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

class DefaultAPIDataRetriever extends AbstractAPIDataRetriever
{

    protected function prepareGatewayRequestArray(array $input): array
    {
        $requestList = [];
        $request = [];
        $request['gateway'] = $input['gateway'];
        $request['start_date'] = $input['start_date'];
        $request['end_date'] = $input['end_date'];
        $request['meta_data'] = $input['meta_data'];
        array_push($requestList, $request);
        return $requestList;
    }

    protected function fetchTerminal(array $input, $request)
    {
        $terminal = $this->repo->terminal->findByGateway($input['gateway']);
        return $terminal;
    }

    protected function processRequest(array $input, $request, $terminal)
    {
        $gatewayData = [];
        $gatewayData['terminal'] = $terminal;
        $gatewayData['gateway'] = $input['gateway'];
        $gatewayData['request'] = $request;
        $this->trace->info("Gateway Request : ", [$gatewayData, $terminal]);
        return $this->gatewayManager->call($input['gateway'], 'reconcile', $gatewayData, $this->mode, $terminal);
    }

    protected function refactorResponse(array $responseList): array{
        return responseList;
    }
}