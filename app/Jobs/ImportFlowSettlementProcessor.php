<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use Mail;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction\Type;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\InternationalIntegration\Service as MIIService;
use RZP\Models\Merchant\HsCode\HsCodeList;
use RZP\Models\Invoice\Service as InvoiceService;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Type as InvoiceType;
use RZP\Models\Transaction;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Settlement\Processor\OPGSPImportICICI\Processor as OpgspIciciProcessor;


class ImportFlowSettlementProcessor extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    const DEFAULT_DAYS_FOR_BULK_CLEAR = 15;

    const OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT      = 'OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT';
    const OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK = 'OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK';
    const OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE      = 'OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE';
    const OPGSP_IMPORT_SEND_INVOICES                 = 'OPGSP_IMPORT_SEND_INVOICES';

    /**
     * @var string
     */
    protected $queueConfigKey = 'import_flow_process_settlements';

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

    public function handle()
    {
        try {
            parent::handle();

            $this->trace->info(TraceCode::IMPORT_FLOW_PROCESS_SETTLEMENTS_JOB_INIT,[
                'payload'  => $this->payload,
            ]);

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $action     = $this->payload['action'];

            switch($action)
            {
                // Triggers from
                // 1. Single upload of invoice -> payment/{id}/update_merchant_doc
                // 2. Bulk upload of invoice -> payment/merchant_documents
                // 3. Bulk clear cron job -> import/transactions/onhold/clear
                case self::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT :
                    $this->opgspOnHoldClear($this->payload);
                    break;
                // Triggers from cron job endpoint -> import/transactions/onhold/clear
                case self::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK :
                    $this->opgspBulkOnHoldClear($this->payload);
                    break;
                // Triggers from cron job endpoint -> settlements/import/generate
                case self::OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE :
                    $this->generateSettlementFile($this->payload);
                    break;
                // Triggers from cron job endpoint -> import/invoices/send
                case self::OPGSP_IMPORT_SEND_INVOICES :
                    $this->sendInvoices($this->payload);
                    break;
                default :
                    $this->trace->info(TraceCode::IMPORT_FLOW_PROCESS_SETTLEMENTS_INVALID_ACTION,[
                        'payload' => $this->payload,
                    ]);
            }

            $this->trace->info(TraceCode::IMPORT_FLOW_PROCESS_SETTLEMENTS_JOB_COMPLETED,[
                'payload' => $this->payload,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_PROCESS_SETTLEMENTS_JOB_FAILED,[
                    'payload' => $this->payload,
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

            $this->trace->info(TraceCode::IMPORT_FLOW_PROCESS_SETTLEMENTS_JOB_RELEASED, [
                'payload'               => $this->payload,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::IMPORT_FLOW_PROCESS_SETTLEMENTS_JOB_DELETED, [
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

    private function opgspOnHoldClear($input)
    {

        $merchantId = $input['merchant_id'];
        $paymentId  = $input['payment_id'];
        $hscode = $input['hscode'] ?? null;

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if($merchant->isOpgspImportSettlementEnabled() === false)
        {
            $this->trace->info(TraceCode::IMPORT_FLOW_MISSING_RISK_VALIDATION, [
                'input'      => $input,
            ]);

            return;
        }

        if(!isset($hscode))
        {
            $hscode = (new MIIService())->getMerchantHsCode($merchantId);
        }

        if(isset($hscode) === false)
        {
            $this->trace->info(TraceCode::INVALID_HS_CODE_FOR_MERCHANT, [
                'input'           => $input,
                'hscode'          => $hscode,
            ]);
            return;
        }

        $isAwbCheckRequired = HsCodeList::isGoodsMerchant($hscode['hs_code']);

        $paymentSupportingDocuments = (new InvoiceService())->findByPaymentId($paymentId);

        $payment = $this->repo->payment->findOrFail($paymentId);

        if($payment->isCaptured() === false)
        {
            return;
        }

        $isInvoiceUploaded = false;
        $isAwbUploaded = false;

        foreach($paymentSupportingDocuments as $document)
        {
            if ($document[InvoiceEntity::TYPE] === InvoiceType::OPGSP_INVOICE and
                isset($document[InvoiceEntity::REF_NUM]))
            {
                $isInvoiceUploaded = true;
            }

            if ($document[InvoiceEntity::TYPE] === InvoiceType::OPGSP_AWB and
                isset($document[InvoiceEntity::REF_NUM]))
            {
                $isAwbUploaded = true;
            }
        }

        if ($isInvoiceUploaded === false or
            ($isAwbCheckRequired === true
            and $isAwbUploaded === false))
        {
            return;
        }

        $transaction = $this->repo->transaction->findByEntityId($paymentId, $merchant);

        try
        {
            $txn = $this->setOnHoldFalse($transaction->getId());
            $successTxnIds[] = $txn->getId();

            $this->dispatchForSettlement($txn, $successTxnIds);
        }
        catch (\Throwable $e){
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_ON_HOLD_CLEAR_FAILED
            );
        }
    }

    /*
     * This method is used by cron to fetch all on hold transactions
     * in last 15 days, and enable them for settlement if they are eligible.
     */
    private function opgspBulkOnHoldClear($input)
    {
        $merchantId = $input['merchant_id'];
        $prevDays = $input['prev_days'] ?? self::DEFAULT_DAYS_FOR_BULK_CLEAR;

        $hscode = (new MIIService())->getMerchantHsCode($merchantId);

        if(isset($hscode) === false)
        {
            $this->trace->info(TraceCode::INVALID_HS_CODE_FOR_MERCHANT, [
                'input'           => $input,
                'hscode'          => $hscode,
            ]);
            return;
        }

        // get payments from last 15 days with on_hold true
        $startTime = Carbon::now()->subDays($prevDays)->getTimestamp();
        $endTime = Carbon::now()->getTimestamp();

        $transactions = $this->repo->transaction
            ->getTransactionsByMerchantAndOnholdAndTypes($merchantId, true, [Type::PAYMENT], $startTime, $endTime, 1000);

        foreach ($transactions as $transaction)
        {
            $data = [
                'merchant_id'   => $merchantId,
                'payment_id'    => $transaction[TransactionEntity::ENTITY_ID],
                'hscode'        => $hscode,
                'action'        => ImportFlowSettlementProcessor::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT,
            ];

            ImportFlowSettlementProcessor::dispatch($data)->delay(rand(60, 1000) % 601);
        }
    }

    private function setOnHoldFalse($transactionId): Transaction\Entity
    {
        $result = $this->repo->transaction(function () use ($transactionId)
        {
            $txn = $this->repo->transaction->lockForUpdate($transactionId);

            $txn->setOnHold(false);

            $this->repo->saveOrFail($txn);

            return $txn;
        });

        return $result;
    }

    private function dispatchForSettlement($txn, $successTxnIds)
    {

        try{

            $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

            $txn->setSettledAt($settledAt);

            $bucketCore = new Bucket\Core;

            $balance = $txn->accountBalance;

            $newService = $bucketCore->shouldProcessViaNewService($txn->getMerchantId(), $balance);

            if ($newService === true)
            {
                $bucketCore->settlementServiceToggleTransactionHold($successTxnIds, null);
            }
            else
            {
                (new Transaction\Core)->dispatchForSettlementBucketing($txn);
            }
        }catch (\Exception $e){
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_DISPATCH_SETTLEMENT_FAILED
            );
            throw $e;
        }

    }

    private function generateSettlementFile($input)
    {
        (new OpgspIciciProcessor())->generateSettlementFileForICICIOpgspImport($input);
    }

    private function sendInvoices($input)
    {
        (new OpgspIciciProcessor())->sendInvoicesForICICIOpgspImport($input);
    }

}
