<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever;

use App;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

abstract class AbstractAPIDataRetriever implements DataRetriever {


    protected $app;
    protected $trace;
    protected $mode;
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
    }


    public function fetchData(array $input) {

        $output = [];

        $requestList = (array) $this->prepareGatewayRequest($input);

        $terminal = $this->fetchTerminal($input);

        $responseList = [];

        foreach ($requestList as $request)  {
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

    protected abstract function prepareGatewayRequest(array $input): array;

    protected function fetchTerminal(array $input)  {
        $terminal = $this->repo->terminal->findByGatewayMerchantId($input['gateway']);
        return $terminal;
    }

    protected function processRequest(array $input, $request, $terminal)  {

    }

    protected abstract function refactorResponse(array $responseList): array;

    protected function prepareFile($fileName, array $responseList): array  {

    }
}