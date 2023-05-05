<?php

namespace RZP\Jobs;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Document;
use RZP\Base\RepositoryManager;
use RZP\Models\Lambda;

class MerchantFirsDocumentsZip extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    const FIRS_ICICI_FILE = 'firs_icici_file';
    const FIRS_ICICI_ZIP  = 'firs_icici_zip';
    const BATCH_SIZE      = 6000;

    /**
     * @var string
     */
    protected $queueConfigKey = 'zip-firs-documents';

    /**
     * @var array
     */
    protected $request;

    protected $mode;

    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    public $timeout = 300;

    public function __construct(array $payload)
    {
        $this->setMode($payload);

        parent::__construct($this->mode);

        $this->request = $this->getZipFirsDocumentData($payload);
    }

    public function handle()
    {
        try {
            parent::handle();

            $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_INIT,[
                'request'  => $this->request,
            ]);

            $response = $this->processFirsZipCreation($this->request);

            $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_COMPLETED,[
                'response' => $response,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_FAILED,[
                    'request' => $this->request,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->release($workerRetryDelay);

            $this->trace->info(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_RELEASED, [
                'request'               => $this->request,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::FIRS_DOCUMENTS_BULK_ZIPPING_JOB_DELETED, [
                'request'           => $this->request,
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload)
    {
        if (array_key_exists(self::MODE, $payload) === true)
        {
            $this->mode = $payload[self::MODE];
        }
    }

    protected function processFirsZipCreation(array $input){

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $ufhService = $this->app['ufh.service'];

        $merchantId = $input['merchant_id'];
        $month = $input['month'];
        $year = $input['year'];

        $this->deleteExistingZipFilesIfExists($merchantId, $month, $year);

        $from = strtotime($month.'/01/'.$year);
        $to = strtotime("+1 Month",$from)-1;

        $documents = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,self::FIRS_ICICI_FILE,$from,$to);

        $fileIds=[];

        foreach ($documents as $file)
        {
            array_push($fileIds,$file->getPublicFileStoreId());
        }

        $batches = array_chunk($fileIds,self::BATCH_SIZE);

        foreach($batches as $batch)
        {
            $prefix = "Firs";

            $zipFileId = $ufhService->downloadFiles($batch,$merchantId,$prefix,self::FIRS_ICICI_ZIP);

            $this->trace->info(TraceCode::BULK_DOWNLOAD,[
                'success' => isset($zipFileId),
            ]);

            $response = ['id' => $zipFileId];

            $documentDate = strtotime($input['month'].'/'.date('d').'/'.$input['year']);

            $document = (new Document\Core)->saveInMerchantDocument($response,$merchantId,self::FIRS_ICICI_ZIP,$documentDate);
        }

        if (isset($document))
        {
            try
            {
                // set the mode
                if (isset($this->app['rzp.mode']) === false)
                {
                    $this->app['rzp.mode'] = $this->mode ?? Mode::LIVE;
                }
                $data = [
                    'document_id' => $document->getId(),
                    'action'      => MerchantCrossborderEmail::FIRS_AVAILABLE_NOTIFICATION,
                    'mode'        => $this->app['rzp.mode'],
                ];
                $this->trace->info(TraceCode::FIRS_SEND_EMAIL_MESSAGE_DISPATCHED,
                    [
                        'data' => $data,
                    ]
                );
                // adding delay of 10 to 15 minutes for the ZIP creation
                MerchantCrossborderEmail::dispatch($data)->delay(600 + rand(0, 1000) % 301);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::FIRS_SEND_EMAIL_MESSAGE_DISPATCH_FAILED,
                    [
                        'document_id' => $document->getId()
                    ]);
            }
        }

        return ['merchant_id' => $merchantId, 'success' => isset($document)];
    }

    /*
     * Deleting only ICICI FIRS ZIP Files
    */
    protected function deleteExistingZipFilesIfExists(string $merchantId,$month,$year)
    {
        $from = strtotime($month.'/01/'.$year);
        $to = strtotime("+1 Month",$from)-1;

        $ufhService = $this->app['ufh.service'];

        $documentEntities = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,self::FIRS_ICICI_ZIP,$from,$to);

        foreach($documentEntities as $documentEntity)
        {
            if ($documentEntity != null)
            {
                $this->trace->info(TraceCode::DELETE_EXISTING_ZIPPED_FIRS_DOCUMENTS, [
                    'file_store_id' => $documentEntity->getPublicFileStoreId()
                ]);

                $ufhService->deleteFile($documentEntity->getPublicFileStoreId(),$merchantId,self::FIRS_ICICI_ZIP);
                (new Document\Core)->deleteDocument($documentEntity->getFileStoreId());
            }
        }
    }

    protected function getZipFirsDocumentData(array $payload)
    {
        return [
            'merchant_id'   => $payload['merchant_id'],
            'month'         => $payload['month'],
            'year'          => $payload['year'],
            'force_create'  => $payload['force_create']
        ];
    }
}
