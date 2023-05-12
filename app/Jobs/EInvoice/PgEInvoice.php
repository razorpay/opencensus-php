<?php

namespace RZP\Jobs\EInvoice;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\EInvoice;
use RZP\Models\Report\Types\InvoiceReport;
use RZP\Models\Merchant\Invoice\PdfGenerator;

class PgEInvoice extends Job
{
    const MAX_ATTEMPTS = 5;

    public $timeout = 1800;

    protected $merchantId;

    protected $type;

    protected $documentTypeData;

    protected $documentCount;

    protected $pgEInvoiceCore;

    protected $params;

    protected $b2cFallback;

    /**
     * @var string
     */
    protected $queueConfigKey = 'pg_einvoice';

    public function __construct(string $mode, string $merchantId,
                                int $documentCount, array $documentTypeData, array $params = [])
    {
        parent::__construct($mode);

        $this->merchantId   = $merchantId;

        $this->type         =  EInvoice\Types::PG;

        $this->documentCount = $documentCount;

        $this->documentTypeData = $documentTypeData;

        $this->params       = $params;

        $this->b2cFallback  = false;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        if($this->type !== EInvoice\Types::PG)
        {
            $this->trace->info(TraceCode::EINVOCICE_INVALID_REQUEST_IN_PG_JOB,
                $this->getTraceData());

            return;
        }

        try
        {
            $this->trace->info(TraceCode::EINVOICE_PG_JOB_INIT,
                $this->getTraceData());

            $input = $this->params;

            $merchant = $this->repoManager->merchant->findOrFail($this->merchantId);

            $input[EInvoice\Entity::TYPE] = $this->type;

            $this->pgEInvoiceCore = (new EInvoice\PgEInvoice());

            [$count, $entityMap] = $this->pgEInvoiceCore->getEInvoiceData($this->merchantId,
                $input[EInvoice\Entity::MONTH], $input[EInvoice\Entity::YEAR], $this->type);

            foreach ($this->documentTypeData as $documentType => $data)
            {
                if ($this->b2cFallback === true)
                {
                    break;
                }

                $resolvedDocumentType = InvoiceReport::$documentTypeMap[$documentType];

                if(sizeof($data) !== 0)
                {
                    if(isset($entityMap[$resolvedDocumentType]) === false)
                    {
                        $input[EInvoice\Entity::DOCUMENT_TYPE] = $resolvedDocumentType;

                        $eInvoiceEntity = $this->pgEInvoiceCore->create($input, $merchant);
                    }
                    else
                    {
                        $eInvoiceEntity = $entityMap[$resolvedDocumentType];
                    }

                    $this->processEInvoice($eInvoiceEntity);
                }
            }

            $this->checkForPdfGeneration();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EINVOICE_JOB_FAILURE_EXCEPTION,
                $this->getTraceData());

            $this->retry(30);
        }
    }

    protected function processEInvoice(EInvoice\Entity $eInvoiceEntity)
    {
        if($eInvoiceEntity->getStatus() === EInvoice\Status::STATUS_GENERATED)
        {
            return;
        }

        if($eInvoiceEntity->getStatus() !== EInvoice\Status::STATUS_GENERATED)
        {
            [$response, $failure] = $this->pgEInvoiceCore->generateEInvoice($eInvoiceEntity);
            if($failure === false)
            {
                $result = $response[EInvoice\Core::BODY][EInvoice\Core::RESULTS];

                if ($result[EInvoice\Core::RESULT_STATUS] === EInvoice\Core::FAILURE_STATUS)
                {
                    $errorMessage = $result[EInvoice\Core::ERROR_MESSAGE];

                    $shouldGenerateB2C = $this->pgEInvoiceCore->shouldGenerateB2C($errorMessage);

                    if ($shouldGenerateB2C === true)
                    {
                        $this->generateB2CFallBack();
                    }

                    $shouldRetry = $this->pgEInvoiceCore->shouldRetry($response);

                    //TODO: finalise the appropriate delay.
                    if ($shouldRetry === true)
                    {
                        $this->trace->info(TraceCode::EINVOICE_GSP_RETRYABLE_RESPONSE,
                            $this->getTraceData($response));

                        $this->retry(30);
                    }

                    if (($shouldGenerateB2C === false) and ($shouldRetry === false))
                    {
                        $this->trace->info(TraceCode::EINVOICE_GSP_FAILURE_RESPONSE,
                            $this->getTraceData($response));
                    }
                }
                else
                {
                    $this->trace->info(TraceCode::EINVOICE_GSP_SUCCESS_RESPONSE,
                        $this->getTraceData($response));
                }
            }
        }
    }

    protected function checkForPdfGeneration()
    {
        $generatedCount = 0;

        $this->pgEInvoiceCore = (new EInvoice\PgEInvoice());

        [$count, $entityMap] = $this->pgEInvoiceCore->getEInvoiceData($this->merchantId,
            $this->params[EInvoice\Entity::MONTH], $this->params[EInvoice\Entity::YEAR], $this->type);

        foreach ($entityMap as $documentType => $entity)
        {
            if ($entity->getStatus() === EInvoice\Status::STATUS_GENERATED)
            {
                $generatedCount++;
            }
        }

        if(($generatedCount === $this->documentCount) and ($count === $this->documentCount) or
            ($this->b2cFallback === true))
        {
            $this->trace->info(TraceCode::EINVOICE_PG_PDF_PERSIST_BEGIN,
                $this->getTraceData());

            $month = $this->params[EInvoice\Entity::MONTH];
            $year = $this->params[EInvoice\Entity::YEAR];

            $invoiceBreakup = $this->repoManager->merchant_invoice->fetchInvoiceReportData($this->merchantId, $month, $year);

            (new PdfGenerator())->generatePgInvoice($this->merchantId, $month, $year, $invoiceBreakup);
        }
    }

    protected function generateB2CFallBack()
    {
        $this->b2cFallback = true;

        $this->trace->info(TraceCode::EINOVICE_FALLBACK_TO_B2C,
            $this->getTraceData());
    }

    protected function retry(int $delay)
    {
        // if the max attempt is not exhausted then release the job for retry
        if ($this->attempts() <= self::MAX_ATTEMPTS)
        {
            $this->release($delay);
        }
    }

    protected function getTraceData($gspResponse = null) : array
    {
        $data = [
            'merchant_id'   => $this->merchantId,
            'type'          => $this->type,
            'params'        => $this->params,
            'mode'          => $this->mode,
            'b2cFallback'   => $this->b2cFallback,
        ];

        if(empty($gspResponse) === false)
        {
            $data['gsp_response'] = $gspResponse;
        }

        return $data;
    }
}
