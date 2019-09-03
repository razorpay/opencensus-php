<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Models\Terminal;
use RZP\Reconciliator\RequestProcessor\Retriever\DataRetriever;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

abstract class AbstractAPIDataRetriever implements DataRetriever {


    protected $app;

    /**
     * Trace instance for tracing
     * @var $trace Trace
     */
    protected $trace;

    protected $mode;

    protected $gatewayManager;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->mode = $this->app['rzp.mode'];
        $this->repo = $this->app['repo'];
        $this->gatewayManager = $this->app['gateway'];
    }


    public function fetchData(array $input) {

        $output = [];

        $requestList = (array) $this->prepareGatewayRequestArray($input);

        $responseList = [];

        foreach ($requestList as $request)  {
            $terminal = $this->fetchTerminal($input, $request);
            list($key, $response) = $this->processRequest($input, $request, $terminal);
            $responseList[$key] = $response;
        }

        $responseList = $this->refactorResponse($responseList);

        foreach ($responseList as $key => $value)   {

            $fileName = $key."_".$input["gateway"]."_".date('Y-m-d_h:i:s');
            array_push($output, $this->prepareFile($fileName, $value));
        }
        return $output;
    }

    protected abstract function prepareGatewayRequestArray(array $input): array;

    protected abstract function fetchTerminal(array $input, $request);

    protected abstract function processRequest(array $input, $request, $terminal);

    protected abstract function refactorResponse(array $responseList): array;

    protected function prepareFile($fileName, array $response): array  {

    }

}