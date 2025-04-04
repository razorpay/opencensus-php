<?php

namespace RZP\Jobs;

use App;

use Razorpay\Trace\Logger;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Exception\LogicException;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Models\Adjustment\Core as AdjustmentCore;
use RZP\Models\BankTransfer\Core as BankTransferCore;
use RZP\Models\FundAccount\Validation\Core as FavCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\CreditTransfer\Core as CreditTransferCore;

class LedgerJournalBase extends Job
{
    const MAX_RETRY_ATTEMPTS = 10;
    // the delay is in second
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY    = 5;

    protected $trace;

    protected $mode;

    protected $ledgerResponse;

    protected $razorx;

    // ledger transactor id prefix
    const PAYOUT_PREFIX          = "pout_";
    const REVERSAL_PREFIX        = "rvrsl_";
    const FAV_PREFIX             = "fav_";
    const BANK_TRANSFER_PREFIX   = "bt_";
    const ADJUSTMENT_PREFIX      = "adj_";
    const CREDIT_TRANSFER_PREFIX = "ct_";

    const ID                     = "id";
    const LEDGER_ENTRY           = "ledger_entry";
    const MERCHANT_ID            = "merchant_id";
    const JOURNAL_ID             = "journal_id";
    const TRANSACTOR_ID          = "transactor_id";
    const TRANSACTOR_EVENT       = "transactor_event";

    const IS_DUPLICATE = "is_duplicate";

    public function __construct(string $mode, array $payload)
    {
        parent::__construct($mode);

        $this->mode = $mode;
        $this->ledgerResponse = $payload;
    }

    public function handle()
    {
        $entityId = null;
        $entityName = null;
        $response = null;
        $skipTransactionCreation = false;

        try
        {
            parent::handle();

            $app = App::getFacadeRoot();
            $this->razorx = $app['razorx'];

            $this->trace->info(TraceCode::LEDGER_JOURNAL_QUEUE_JOB_INIT, $this->ledgerResponse);

            app('worker.ctx')->setLedgerDualWriteFlow(true);

            $experimentName =Merchant\RazorxTreatment::LEDGER_DISABLE_TRANSACTION_DUAL_WRITE;

            if($this->mode === Mode::TEST)
            {
                $experimentName = Merchant\RazorxTreatment::LEDGER_DISABLE_TRANSACTION_DUAL_WRITE_TEST;
            }

            if ($this->isExperimentEnabled($experimentName) === true)
            {
                $this->trace->info(TraceCode::LEDGER_JOURNAL_QUEUE_JOB_TRANSACTION_DUAL_WRITE_SKIPPED, [
                    self::MERCHANT_ID => $this->ledgerResponse[self::LEDGER_ENTRY][0][self::MERCHANT_ID],
                    self::JOURNAL_ID  => $this->ledgerResponse[self::ID]
                ]);

                $skipTransactionCreation = true;
            }

            // dual writes API Transaction
            $transactorId = $this->ledgerResponse[self::TRANSACTOR_ID];
            $transactorEvent = $this->ledgerResponse[self::TRANSACTOR_EVENT];

            // get the entity id and name
            if (strpos($transactorId, self::PAYOUT_PREFIX) !== false) {
                $entityId = str_replace(self::PAYOUT_PREFIX, '', $transactorId);
                $entityName = Entity::PAYOUT;
            } else if (strpos($transactorId, self::REVERSAL_PREFIX) !== false) {
                $entityId = str_replace(self::REVERSAL_PREFIX, '', $transactorId);
                $entityName = Entity::REVERSAL;
            } else if (strpos($transactorId, self::FAV_PREFIX) !== false) {
                $entityId = str_replace(self::FAV_PREFIX, '', $transactorId);
                $entityName = Entity::FUND_ACCOUNT_VALIDATION;
            } else if (strpos($transactorId, self::ADJUSTMENT_PREFIX) !== false) {
                $entityId = str_replace(self::ADJUSTMENT_PREFIX, '', $transactorId);
                $entityName = Entity::ADJUSTMENT;
            } else if (strpos($transactorId, self::BANK_TRANSFER_PREFIX) !== false) {
                $entityId = str_replace(self::BANK_TRANSFER_PREFIX, '', $transactorId);
                $entityName = Entity::BANK_TRANSFER;
            } else if (strpos($transactorId, self::CREDIT_TRANSFER_PREFIX) !== false) {
                $entityId = str_replace(self::CREDIT_TRANSFER_PREFIX, '', $transactorId);
                $entityName = Entity::CREDIT_TRANSFER;
            }

            $traceData = [
                'entityId'          => $entityId,
                'entityName'        => $entityName,
                'ledgerResponse'    => $this->ledgerResponse,
            ];

            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_DECODED,
                $traceData);

            // check if dual write already happened via createTransactionInLedgerReverseShadowFlow
            $apiTransaction = $this->repoManager->transaction->find($this->ledgerResponse['id']);
            if ($apiTransaction != null)
            {
                $this->trace->info(TraceCode::LEDGER_API_TXN_DUAL_WRITE_DUPLICATE_REQUEST, [
                    'id' => $this->ledgerResponse['id']
                ]);
                $this->delete();
                return;
            }

            // perform operations based on entity name
            switch ($entityName)
            {
                case Entity::BANK_TRANSFER :
                    if ($skipTransactionCreation === true)
                    {
                        $response = (new BankTransferCore())
                            ->updateBalanceAndTransactionIDInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    else
                    {
                        $response = (new BankTransferCore())
                            ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    break;

                case Entity::ADJUSTMENT :
                    if ($skipTransactionCreation === true)
                    {
                        $response = (new AdjustmentCore())
                            ->updateBalanceAndTransactionIDInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    else
                    {
                        $response = (new AdjustmentCore())
                            ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    break;

                case Entity::PAYOUT :
                    // process only for payout initiated cases, and skip payout failed
                    if (strpos($transactorEvent, Ledger\Payout::PAYOUT_INITIATED) !== false)
                    {
                        if ($skipTransactionCreation === true)
                        {
                            $response = (new PayoutCore)->updateBalanceAndTransactionIDInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                        }
                        else
                        {
                            $response = (new PayoutCore)
                                ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                        }
                    }
                    break;

                case Entity::REVERSAL :
                    if ($skipTransactionCreation === true)
                    {
                        $response = (new ReversalCore)
                            ->updateBalanceAndTransactionIDInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    else
                    {
                        $response = (new ReversalCore)
                            ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    break;

                case Entity::CREDIT_TRANSFER :
                    if ($skipTransactionCreation === true)
                    {
                        $response = (new CreditTransferCore())
                            ->updateBalanceAndTransactionIDInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    else
                    {
                        $response = (new CreditTransferCore())
                            ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                    }
                    break;

                case Entity::FUND_ACCOUNT_VALIDATION :
                    if (strpos($transactorEvent, Ledger\FundAccountValidation::FAV_INITIATED) !== false)
                    {
                        if ($skipTransactionCreation === true)
                        {
                            $response = (new FavCore)
                                ->updateBalanceAndTransactionIDInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                        }
                        else
                        {
                            $response = (new FavCore)
                                ->createTransactionInLedgerReverseShadowFlow($entityId, $this->ledgerResponse);
                        }
                    }
                    break;

                default:
                    $response = [];
                    $this->trace->info(
                        TraceCode::LEDGER_JOURNAL_QUEUE_JOB_ENTITY_NAME_NOT_SUPPORTED,
                        $traceData
                    );
            }

            // Duplicate request encountered during updateBalanceAndTransactionIDInLedgerReverseShadowFlow
            if (isset($response[self::IS_DUPLICATE]) === true)
            {
                $this->delete();
                return;
            }

            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_SUCCESSFUL,
                [
                    'entity_id'   => $entityId,
                    'entity_name' => $entityName,
                    'response'    => $response,
                    'start_time'  => millitime(),
                ]
            );

            $this->delete();
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_JOURNAL_QUEUE_UNEXPECTED_RESPONSE);

            if ($e->getCode() === ErrorCode::BAD_REQUEST_LEDGER_JOURNAL_ENTRY_BALANCE_GET_ERROR)
            {
                $this->delete();
                return;
            }

            $this->checkRetry($entityId, $entityName);
        }
        catch (LogicException $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_JOURNAL_QUEUE_JOB_EXCEPTION);

            $this->delete();

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::LEDGER_JOURNAL_QUEUE_JOB_EXCEPTION);

            $this->checkRetry($entityId, $entityName);
        }
    }

    protected function isExperimentEnabled($experiment)
    {
        if ((empty($this->ledgerResponse[self::LEDGER_ENTRY]) === false) and
            (empty($this->ledgerResponse[self::LEDGER_ENTRY][0]) === false) and
            (empty($this->ledgerResponse[self::LEDGER_ENTRY][0][self::MERCHANT_ID]) === false))
        {
            $merchantId = $this->ledgerResponse[self::LEDGER_ENTRY][0][self::MERCHANT_ID];

            $requestPayload = [
                "id" =>$merchantId,
                "experiment_name" =>  $experiment,
                'request_data'  => json_encode(['id' => $merchantId])
            ];

            return (new Merchant\Core())->isSplitzExperimentEnable($requestPayload, Merchant\RazorxTreatment::VARIANT_ENABLE);
        }

        return false;
    }

    protected function checkRetry($entityId, $entityName)
    {
        $noOfAttempts = $this->attempts();

        if ($noOfAttempts < self::MAX_RETRY_ATTEMPTS)
        {
            $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));

            $this->trace->info(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_RELEASED,
                [
                    'entity_id'      => $entityId,
                    'entity_name'    => $entityName,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );
        }
        else
        {
            $this->trace->error(
                TraceCode::LEDGER_JOURNAL_QUEUE_JOB_DELETED,
                [
                    'entity_id'      => $entityId,
                    'entity_name'    => $entityName,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );

            $this->delete();
        }
    }
}
