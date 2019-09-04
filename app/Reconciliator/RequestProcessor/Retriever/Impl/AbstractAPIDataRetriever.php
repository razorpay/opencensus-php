<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Models\Terminal;
use RZP\Reconciliator\RequestProcessor\Retriever\DataRetriever;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use Symfony\Component\HttpFoundation\File\File;

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


    public function fetchData(array $input): array {

        $files = [];

        $requestList = (array) $this->prepareGatewayRequestArray($input);

        $responseList = [];

        foreach ($requestList as $request)  {
            $terminal = $this->fetchTerminal($input, $request);
            list($key, $response) = $this->processRequest($input, $request, $terminal);
            $responseList[$key] = $response;
        }

        $responseList = $this->refactorResponse($responseList);

        foreach ($responseList as $key => $value)   {

            $fileName = $key."_".$input["gateway"]."_reconcile_".date('Y-m-d_h:i:s');
            array_push($files, $this->prepareFile($fileName, $value));
        }
        return $files;
    }

    protected abstract function prepareGatewayRequestArray(array $input): array;

    protected abstract function fetchTerminal(array $input, $request);

    protected abstract function processRequest(array $input, $request, $terminal);

    protected abstract function refactorResponse(array $responseList): array;

    protected function prepareFile($filename, array $data)  {

        $f = null;
        $filePath = storage_path('files/filestore') . '/'  . $filename . '.csv';
        try{
            $this->trace->info(TraceCode::CRAWLER_RECONCILE, ["prepareFile : ", $filePath, $data]);
            $f = fopen($filePath, 'w');


            // Header line: the field names (keys in $data)
            fputcsv($f, array_keys($data[0]), ',');

            foreach ($data as $record){
                // Data line (can use array_values($data) or just $data as the 2nd argument)
                fputcsv($f, array_values($record), ',');
            }

        }catch(\Exception $e){
            unlink($filePath);
            throw $e;
        }finally{
            if($f !== null) {
                fclose($f);
            }
        }
        return new File($filePath);
    }

}