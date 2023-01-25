<?php

namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Constants\Entity as E;
use RZP\Constants\Mode;
use RZP\Models\Invoice\Constants;
use RZP\Models\Invoice\DccEInvoiceCore;
use RZP\Models\Invoice\Status;
use RZP\Trace\TraceCode;

class PaymentEInvoice extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'generate_payment_e_invoice';

    /**
     * @var array
     */
    protected $payload;

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

        $this->payload = $payload;
    }

    /**
     * 0. Get ID from the message along with the flow (payment or refund)
     * 1. validate if payment is DCC and invoice PDF is not already generated
     * 2. Check tax invoice exist in case of credit note request
     * 3. Create Invoice entity with status CREATED (default status)
     * 4. Prepare request data to send
     * 5. Make a post call to Masters-India to register invoice for GST
     * 6. Update Invoice entity with Invoice reference number (IRN) and QR code URL from the response
     * 7. Generate pdf using invoice template
     * 8. Upload the pdf to s3 using UFH service
     * 9. Update Invoice entity status to GENERATED
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_INIT,[
                'payload'  => $this->payload,
            ]);

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $requestId = $this->payload[Constants::REFERENCE_ID];
            $referenceType = $this->payload[Constants::REFERENCE_TYPE];

            // get payment and refund entity using id of the payload
            $paymentId = $requestId;
            $refund = null;
            $baseEntity = null;
            if($referenceType === Constants::REFUND_FLOW)
            {
                $refund = $this->repo->refund->findOrFail($requestId);
                $paymentId = $refund->getPaymentId();
                $baseEntity = $refund;
            }
            $payment = $this->repo->payment->findOrFail($paymentId);
            if(!isset($baseEntity)) $baseEntity = $payment;

            // if payment is not DCC then do not process request
            if ($payment->isDCC() === false)
            {
                $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED,[
                    'message'  => 'requested payment is not a DCC payment',
                ]);

                $this->delete();
                return;
            }
            // validate the existing invoices if present
            $paymentEInvoices = $this->repo->invoice->fetchInvoicesByEntity($paymentId, E::PAYMENT);
            // this will store the existing e-invoice in case it was not generated earlier (failed state)
            $existingPaymentEInvoice = null;

            if ($referenceType === Constants::REFUND_FLOW)
            {
                if (count($paymentEInvoices) === 0)
                {
                    // INV should be present for CRN request
                    $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED,[
                        'message'  => 'INV not found for the provided CRN request',
                        'payload'  => $this->payload,
                    ]);

                    $this->delete();
                    return;
                }
                foreach ($paymentEInvoices as $eInvoice)
                {
                    if ($eInvoice->getType() === Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType] and $eInvoice->getRefNum() === $refund->getId())
                    {
                        if ($eInvoice->getStatus() !== Status::GENERATED)
                        {
                            $existingPaymentEInvoice = $eInvoice;
                            break;
                        }
                        // do not process duplicate request of CRN
                        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED,[
                            'message'  => 'CRN already exist for given refund id',
                            'payment_e_invoice_id' => $eInvoice->getId(),
                            'payload'  => $this->payload,
                        ]);

                        $this->delete();
                        return;
                    }
                }
            }
            else if (count($paymentEInvoices) > 0)
            {
                foreach ($paymentEInvoices as $eInvoice)
                {
                    if ($eInvoice->getType() === Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType])
                    {
                        if ($eInvoice->getStatus() !== Status::GENERATED)
                        {
                            $existingPaymentEInvoice = $eInvoice;
                            break;
                        }
                        // do not process duplicate request of IVN
                        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                            'message' => 'INV already exist for given payment id',
                            'payment_e_invoice_id' => $eInvoice->getId(),
                            'payload' => $this->payload,
                        ]);

                        $this->delete();
                        return;
                    }
                }
            }

            $eInvoiceCore = new DccEInvoiceCore();

            // if invoice already exist then fetch the object from master DB
            // this is required as writes can't be done on slave DB
            // if the invoice already exist then $existingPaymentEInvoice will be the read object (from slave)
            // which we fetched using entity_id field. (indexed on slave only)
            // we'll fetch write object from master's DB using the invoice ID (Primary key i.e. always indexed)
            if (isset($existingPaymentEInvoice) and !empty($existingPaymentEInvoice))
            {
                $paymentEInvoice = $eInvoiceCore->getEntityFromMaster($existingPaymentEInvoice->getId());
            }
            else
            {
                $paymentEInvoice = $eInvoiceCore->createNewDCCEInvoice($payment, $baseEntity, $referenceType);
            }

            // fetch request payload for registering invoice
            $requestData = $eInvoiceCore->getEInvoiceRequestData($paymentEInvoice, $payment, $baseEntity);
            if (empty($requestData))
            {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::BUILDING_REQUEST_DATA_FAILED);
                return;
            }

            // update status to initiated
            $eInvoiceCore->updateStatusAndError($paymentEInvoice, Status::INITIATED);

            // request to Masters India to register invoice
            $response = $eInvoiceCore->registerDCCInvoice($requestData, $this->mode);
            if (!isset($response) or empty($response[Constants::IRN]))
            {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::INVOICE_REGISTRATION_FAILED, $response);
                return;
            }

            // update entity with response data
            $eInvoiceCore->updateEInvoiceEntity($paymentEInvoice, $response);

            // generate E-Invoice PDF
            $pathToFile = $eInvoiceCore->generateInvoicePDF($payment, $requestData, $response);
            if (empty($pathToFile))
            {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::INVOICE_PDF_GENERATION_FAILED);
                return;
            }

            // upload to S3 using UFH
            $fileAccessUrl = $eInvoiceCore->uploadInvoiceToUFH($pathToFile, $baseEntity, $payment, $referenceType);
            if (!isset($fileAccessUrl))
            {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::INVOICE_UPLOAD_FAILED);
                return;
            }

            // delete local invoice
            $eInvoiceCore->deleteLocalFile($pathToFile);

            // update status to generated
            $eInvoiceCore->updateStatusAndError($paymentEInvoice, Status::GENERATED);

            $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                'payment_e_invoice_id' => $paymentEInvoice->getId(),
                'payload' => $this->payload,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_E_INVOICE_JOB_FAILED,[
                    'payload' => $this->payload,
                ]
            );

            if (isset($paymentEInvoice) and isset($eInvoiceCore))
            {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore);
                return;
            }

            $this->checkRetry();
        }
    }

    protected function handleFailure($paymentEInvoice, $eInvoiceCore, $errorCode = Constants::INVOICE_CREATION_FAILED, $data = [])
    {
        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_FAILED,[
            'error_code'  => $errorCode,
            'data' => $data,
            'payment_e_invoice_id' => $paymentEInvoice->getId(),
            'payload'  => $this->payload,
        ]);

        // update status to failed with error code
        $eInvoiceCore->updateStatusAndError($paymentEInvoice, Status::FAILED, $errorCode);

        $this->checkRetry();
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->release($workerRetryDelay);

            $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_RELEASED, [
                'payload'               => $this->payload,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::PAYMENT_E_INVOICE_JOB_DELETED, [
                'payload'           => $this->payload,
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
        if (array_key_exists(self::MODE, $payload) === true) {
            $this->mode = $payload[self::MODE];
        }
        else {
            $this->mode = Mode::LIVE;
        }
    }
}
