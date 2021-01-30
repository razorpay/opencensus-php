<?php


namespace RZP\Jobs\EInvoice;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\EInvoice;


class XEInvoice extends Job
{
    const MAX_ATTEMPTS = 5;

    protected $merchantId;

    protected $type;

    protected $XEInvoiceCore;

    protected $params;

    /**
     * @var string
     */
    protected $queueConfigKey = 'pg_einvoice';

    public function __construct(string $mode, string $merchantId, array $params = [])
    {
        parent::__construct($mode);

        $this->merchantId   = $merchantId;

        $this->type         =  EInvoice\Types::BANKING;

        $this->params       = $params;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        if($this->type !== EInvoice\Types::BANKING)
        {
            $this->trace->error(TraceCode::EINVOICE_INVALID_REQUEST_IN_X_JOB,
                $this->getTraceData());

            return;
        }
        
        try
        {
            $input = $this->params;

            $merchant = $this->repoManager->merchant->findOrFail($this->merchantId);

            $input[EInvoice\Entity::TYPE] = $this->type;

            $this->XEInvoiceCore = (new EInvoice\XEInvoice());

            [$count, $entityMap] = $this->XEInvoiceCore->getEInvoiceData($this->merchantId,
                $input[EInvoice\Entity::MONTH], $input[EInvoice\Entity::YEAR], $this->type);

            if(isset($entityMap[EInvoice\DocumentTypes::INV]) === false)
            {
                $input[EInvoice\Entity::DOCUMENT_TYPE] = EInvoice\DocumentTypes::INV;

                $eInvoiceEntity = $this->XEInvoiceCore->create($input, $merchant);
            }
            else
            {
                $eInvoiceEntity = $entityMap[EInvoice\DocumentTypes::INV];
            }

            $this->processEInvoice($eInvoiceEntity);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EINVOICE_JOB_FAILURE_EXCEPTION_FOR_X,
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
            [$response, $failure] = $this->XEInvoiceCore->generateEInvoice($eInvoiceEntity);

            if($failure === false) {

                $result = $response[EInvoice\Core::BODY][EInvoice\Core::RESULTS];

                if ($result[EInvoice\Core::RESULT_STATUS] === EInvoice\Core::FAILURE_STATUS)
                {
                    $errorMessage = $result[EInvoice\Core::ERROR_MESSAGE];
                    $shouldGenerateB2C = $this->XEInvoiceCore->shouldGenerateB2C($errorMessage);
                    if ($shouldGenerateB2C === true)
                    {
                        $this->trace->info(TraceCode::EINVOICE_FALLBACK_TO_B2C_FOR_X,
                            $this->getTraceData($response));
                    }

                    $shouldRetry = $this->XEInvoiceCore->shouldRetry($response);

                    if ($shouldRetry === true)
                    {
                        $this->trace->info(TraceCode::EINVOICE_GSP_RETRYABLE_RESPONSE_FOR_X,
                            $this->getTraceData($response));

                        $this->retry(30);
                    }

                    if (($shouldGenerateB2C === false) and ($shouldRetry === false))
                    {
                        $this->trace->info(TraceCode::EINVOICE_GSP_FAILURE_RESPONSE_FOR_X,
                            $this->getTraceData($response));
                    }
                }
                else
                {
                    $this->trace->info(TraceCode::EINVOICE_GSP_SUCCESS_RESPONSE_FOR_X,
                        $this->getTraceData());
                }

            }
            else
            {
                $this->retry(30);
            }
        }
    }

    protected function retry(int $delay)
    {
        // if the max attempt is not exhausted then release the job for retry
        if ($this->attempts() <= self::MAX_ATTEMPTS)
        {
            $this->release($delay);
        }
        else
        {
            $this->delete();
        }
    }

    protected function getTraceData($gspResponse = null) : array
    {
        $data = [
            'merchant_id'   => $this->merchantId,
            'type'          => $this->type,
            'params'        => $this->params,
            'mode'          => $this->mode,
            'attempts'      => $this->attempts(),
        ];

        if(isset($gspResponse) === true)
        {
            $data['gsp_response'] = $gspResponse;
        }

        return $data;
    }
}
