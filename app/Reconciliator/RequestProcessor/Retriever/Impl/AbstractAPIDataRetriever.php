<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Exception\ReconciliationException;
use RZP\Models\FileStore\Creator;
use RZP\Models\FileStore\Format;
use RZP\Models\FileStore\Store;
use RZP\Models\FileStore\Type;
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

    const GATEWAY    = 'gateway';
    const IDENTIFIER = 'identifier';
    const START_DATE = 'start_date';
    const END_DATE   = 'end_date';

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

        try
        {

            while (empty($request = $this->getNextRequest($input, $request, $response)) === false)
            {
                $terminal = $this->fetchTerminal($input, $request);
                list($key, $response) = $this->processRequest($input, $request, $terminal);
                $responseList[$key] = $response;
            }

            $responseList = $this->refactorResponse($responseList);

            if (empty($responseList) === true)
            {
                $this->trace->info(TraceCode::GATEWAY_RECONCILE_RESPONSE, ['entered']);
                throw new ReconciliationException(
                    'Response records missing for gateway.',
                    [
                        'gateway' => $input[self::GATEWAY]
                    ]);
            }

            foreach ($responseList as $key => $value)
            {
                $fileName = $key.'_'.$input[self::GATEWAY].'_reconcile_'.date('Y-m-d_h:i:s');
                array_push($files, $this->prepareFile($fileName, $value));
            }

        }
        catch (ReconciliationException $re)
        {
            throw $re;
        }
        catch (\Exception $e)
        {
            throw new ReconciliationException(
                'Exception in DataRetriever for gateway.',
                [
                    'gateway' => $input[self::GATEWAY],
                    'message' => $e->getMessage(),
                ]);
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

        $gatewayData['payment'] = [self::GATEWAY => $request[self::GATEWAY]];

        $gatewayData['reconRequest'] = $request;

        return [$request[self::IDENTIFIER], $this->gatewayManager->call($request[self::GATEWAY], 'reconcile', $gatewayData, $this->mode, $terminal)];
    }

    protected abstract function refactorResponse(array $responseList): array;

    protected function prepareFile($filename, array $data)
    {
        $creator = $this->createFile($data, $filename);

        $file = $creator->get();

        $filePath = $file['local_file_path'];

        return new File($filePath);
    }

    protected function createFile($content, string $fileName)
    {
        $creator = new Creator();

        $creator->extension(Format::CSV)
                ->content($content)
                ->name($fileName)
                ->store(Store::LOCAL)
                ->type(Type::RECONCILIATION_BATCH_INPUT)
                ->save();

        return $creator;
    }
}