<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Models\Terminal;
use RZP\Reconciliator\RequestProcessor\Retriever\DataRetriever;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use Symfony\Component\HttpFoundation\File\File;

abstract class AbstractAPIDataRetriever implements DataRetriever
{

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

    const GATEWAY = 'gateway';
    const IDENTIFIER = 'identifier';
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->mode = $this->app['rzp.mode'];
        $this->repo = $this->app['repo'];
        $this->gatewayManager = $this->app['gateway'];
    }

    public function fetchData(array $input): array
    {

        $files = [];

        $request = [];

        $response = [];

        $responseList = [];

        while (empty($request = $this->getNextRequest($input, $request, $response)) === false)
        {
            $terminal = $this->fetchTerminal($input, $request);
            list($key, $response) = $this->processRequest($input, $request, $terminal);
            $responseList[$key] = $response;
        }

        $responseList = $this->refactorResponse($responseList);

        foreach ($responseList as $key => $value)
        {
            $fileName = $key.'_'.$input['gateway'].'_reconcile_'.date('Y-m-d_h:i:s');
            array_push($files, $this->prepareFile($fileName, $value));
        }
        return $files;
    }

    protected abstract function getNextRequest(array $input, $prevRequest, $prevResponse): array;

    protected function fetchTerminal(array $input, $request)
    {
        $terminal = $this->repo->terminal->findByGatewayMerchantId(\RZP\Models\Merchant\Account::SHARED_ACCOUNT, $request[self::GATEWAY]);
        return $terminal;
    }

    protected function processRequest(array $input, $request, $terminal)
    {
        $gatewayData = [];
        $gatewayData['terminal'] = $terminal;
        $gatewayData[self::GATEWAY] = $request[self::GATEWAY];
        $gatewayData['payment'] = ['gateway' => $request['gateway'], ];
        $gatewayData['reconRequest'] = $request;
        return [$request[self::IDENTIFIER], $this->gatewayManager->call($request['gateway'], 'reconcile', $gatewayData, $this->mode, $terminal)];
    }

    protected abstract function refactorResponse(array $responseList): array;

    protected function prepareFile($filename, array $data)
    {

        $f = null;
        $filePath = storage_path('files/filestore') . '/'  . $filename . '.csv';
        try
        {
            $f = fopen($filePath, 'w');
            fputcsv($f, array_keys($data[0]), ',');
            //fputs($f,PHP_EOL);
            foreach ($data as $record)
            {
                fputcsv($f, array_values($record), ',');
            }
        }
        catch(\Exception $e)
        {
            unlink($filePath);
            throw $e;
        }
        finally
        {
            if (empty($f) === false)
            {
                fclose($f);
            }
        }
        return new File($filePath);
    }
}