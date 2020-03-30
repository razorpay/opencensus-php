<?php

namespace RZP\Models\BankingAccountStatement;

use Mail;
use File;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Admin\Service as AdminService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;

class Core extends Base\Core
{
    const STORE_TYPE         = 'transactions';

    const FILE_ID            = 'file_id';

    const DASHBOARD_FILE_URL = '%sufh/file/%s';

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT = 2;

    /**
     * Temporary hack. Should not set balance at a class level.
     * This restricts us from processing transactions from
     * multiple account statements at once.
     *
     * @var Merchant\Balance\Entity
     */
    protected $balance;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * NOTE: This function should be used for a specific account number only. We cannot
     * call this function for processing transactions of multiple account numbers.
     * This is because we are setting the balance entity at a class level.
     * If you want to process transactions of multiple account numbers
     * in a single shot, the logic for fetching balance should be fixed.
     *
     * @param array $input
     *
     * @return array
     */
    public function processStatementForAccount(array $input)
    {
        try
        {
            $channel = array_pull($input, Entity::CHANNEL);

            $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
                [
                    'channel'        => $channel,
                    'accountNumber' => $accountNumber,
                ]);

            $this->mutex->acquireAndRelease(
                'banking_account_statement_' . $accountNumber,
                function () use ($channel, $accountNumber, $input)
                {
                    $bankingAccount = (new BankingAccount\Repository)->findByAccountNumberAndChannel($accountNumber, $channel);

                    $currentTime = Carbon::now()->getTimestamp();

                    // updating LastStatementAttemptAt irrespective of success or fail so that new accounts are always fetched
                    // using LastStatementAttemptAt and failed accounts can be manually tried by sre also We are handling failure
                    // retry in job instead.
                    $bankingAccount->setLastStatementAttemptAt($currentTime);

                    $this->repo->saveOrFail($bankingAccount);

                    $merchant = $bankingAccount->merchant;

                    $processor = $this->getProcessor($channel, $accountNumber);

                    $accountStatementDetails = $processor->fetchAccountStatementDetails($input);

                    $this->processAccountStatement($accountStatementDetails, $accountNumber, $merchant);

                    $bankingAccount->balance->updateLastFetchedAt();
                },
                1800,
                ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS
            );
        }
        catch (Exception\BadRequestException $e)
        {
            // catching only BadRequestException exception to log and have noop for duplicate statement fetch request
            // Ignoring the duplicate exception and treating it success and delete account number from sqs.
            if ($e->getCode() === ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_FAILED,
                    [
                        'channel'       => $channel,
                        'accountNumber' => $accountNumber,
                        'message'       => $e->getMessage(),
                    ]);
            }
            else
            {
                throw $e;
            }
        }

        return ['processed' => true];
    }

    public function requestAccountStatement($input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_STATEMENT_GENERATE, $input);

        $statementFileId = $this->generateBankAccountStatement($input);

        $sendEmail = filter_var($input[Entity::SEND_EMAIL], FILTER_VALIDATE_BOOLEAN);

        if ($sendEmail === true)
        {
            $this->sendBankAccountStatementEmail($input, $statementFileId);

            return $input;
        }

        $input[self::FILE_ID] = $statementFileId;

        return $input;
    }

    /**
     * Creates either a PDF/Excel File and returns the file handle to the calling function
     *
     * @param $input
     *
     * @return string
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function generateBankAccountStatement(array $input)
    {
        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $fromDate = $input[Entity::FROM_DATE];

        $toDate = $input[Entity::TO_DATE];

        $format = $input[Entity::FORMAT];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_GENERATE,
            [
                'channel'        => $channel,
                'account_number' => $accountNumber,
                'from_date'      => $fromDate,
                'to_date'        => $toDate,
                'format'         => $format,
                'send_email'     => $input[Entity::SEND_EMAIL],
            ]);

        $statementGenerator = $this->getGenerator($accountNumber, $channel, $format, $fromDate, $toDate);

        $bankingAccount = $this->repo
                               ->banking_account
                               ->findByAccountNumberAndChannel($accountNumber, $channel);

        $temporaryFilePath = $statementGenerator->getStatement();

        $this->trace->info(TraceCode::CA_STATEMENT_GENERATED,
                           [
                               'banking_account_id'  => $bankingAccount->getId(),
                               'temporary_file_path' => $temporaryFilePath
                           ]);

        $ufhResponse = $this->uploadTemporaryFileToStore($temporaryFilePath, $bankingAccount);

        $fileId = $ufhResponse[self::FILE_ID] ?? null;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_GENERATE,
            [
                'file_id' => $fileId
            ]);

        return $fileId;
    }

    protected function sendBankAccountStatementEmail(array $input, string $statementFileId = null)
    {
        $fileAccessUrl = $this->getDashboardFileAccessUrl($statementFileId);

        $merchant = $this->merchant;

        $toEmails = $input[Entity::TO_EMAIL_LIST];

        $fromDate = $input[Entity::FROM_DATE];

        $toDate = $input[Entity::TO_DATE];

        $email = new StatementMail($merchant,
                                   $toEmails,
                                   $fromDate,
                                   $toDate,
                                   $fileAccessUrl);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_EMAIL,
            [
                'merchant_id' => $this->merchant->getId(),
                'to_emails'   => $toEmails,
                'from_date'   => $fromDate,
                'to_date'     => $toDate,
            ]);

        Mail::queue($email);
    }

    protected function getDashboardFileAccessUrl(string $fileId = null)
    {
        return sprintf(self::DASHBOARD_FILE_URL, $this->config['applications.dashboard.url'], $fileId);
    }

    protected function uploadTemporaryFileToStore(string $pathToTemporaryFile, BankingAccount\Entity $entity)
    {
        $ufhService = $this->app['ufh.service'];

        $uploadedFileInstance = $this->getUploadedFileInstance($pathToTemporaryFile);

        $response = $ufhService->uploadFileAndGetUrl($uploadedFileInstance,
                                                     $name = File::name($pathToTemporaryFile),
                                                     self::STORE_TYPE,
                                                     $entity);
        $this->trace->info(
            TraceCode::UFH_RESPONSE,
            [
                'banking_account_id' => $entity->getId(),
                'response'           => $response,
            ]);

        return $response;
    }

    protected function getUploadedFileInstance(string $path)
    {
        $name = File::name($path);

        $extension = File::extension($path);

        $originalName = $name . '.' . $extension;

        $mimeType = File::mimeType($path);

        $size = File::size($path);

        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.
        $test = true;

        $object = new UploadedFile($path, $originalName, $mimeType, $size, $error, $test);

        return $object;
    }

    protected function getGenerator(string $accountNumber, string $channel, string $format, int $fromDate, int $toDate)
    {
        $statementGeneratorNamespace = __NAMESPACE__ . '\\' . 'Generator\\Gateway\\' . studly_case($channel);

        $statementGenerator = $statementGeneratorNamespace . '\\' . studly_case($format);

        return new $statementGenerator($accountNumber, $channel, $fromDate, $toDate);
    }

    protected function getProcessor(string $channel, string $accountNumber): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Gateway';

        return new $processor($channel, $accountNumber);
    }

    protected function processAccountStatement(
        array $bankTransactions,
        string $accountNumber,
        Merchant\Entity $merchant)
    {
        $bankTxnCount = count($bankTransactions);
        $skippedCount = 0;

        foreach ($bankTransactions as $bankTransaction)
        {
            $bankTxnId      = $bankTransaction[Entity::BANK_TRANSACTION_ID];
            $bankTxnSrlNo   = $bankTransaction[Entity::BANK_SERIAL_NUMBER];
            $bankTxnDate    = $bankTransaction[Entity::TRANSACTION_DATE];
            $bankTxnChannel = $bankTransaction[Entity::CHANNEL];

            $txnExists = $this->repo->banking_account_statement->bankTransactionExists(
                $bankTxnId,
                $accountNumber,
                $bankTxnDate,
                $bankTxnChannel,
                $bankTxnSrlNo);

            if ($txnExists === true)
            {
                $skippedCount++;

                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_INSERT_SKIP,
                    [
                        'bank_transaction_id'               => $bankTxnId,
                        'bank_transaction_serial_number'    => $bankTxnSrlNo,
                        'bank_transaction_date'             => $bankTxnDate,
                        'bank_transaction_channel'          => $bankTxnChannel,
                        'bank_account_number'               => $accountNumber,
                    ]);

                continue;
            }

            $this->saveAccountStatement($bankTransaction, $merchant);
        }

        $processedCount = $bankTxnCount - $skippedCount;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE_SUMMARY,
            [
                'total'     => $bankTxnCount,
                'skipped'   => $skippedCount,
                'processed' => $processedCount,
            ]);

        $this->checkAndTraceForStaleResponse($bankTxnCount, $processedCount, $accountNumber);
    }

    protected function saveAccountStatement(array $bankTransaction, Merchant\Entity $merchant)
    {
        $this->repo->transaction(function () use ($bankTransaction, $merchant) {

            $basEntity = (new Entity)->build($bankTransaction);

            //
            // This should be done after build since `setUtr` fetches things from the entity.
            // Can be refactored if required, as long as properly tested.
            //
            if (empty($basEntity->getUtr()) === true)
            {
                $basEntity->setUtr();
            }

            $basEntity->merchant()->associate($merchant);

            $sourceEntity = $this->processSourceEntity($basEntity);

            $basEntity->source()->associate($sourceEntity);

            $basEntity->transaction()->associate($sourceEntity->transaction);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE, $basEntity->toArray());

            $this->repo->saveOrFail($basEntity);
        });
    }

    protected function getBalance(Entity $basEntity)
    {
        if (empty($this->balance) === true)
        {
            $this->balance = $this->repo
                                  ->balance
                                  ->getBalanceByMerchantIdAccountNumberAndChannelOrFail($basEntity->getMerchantId(),
                                                                                        $basEntity->getAccountNumber(),
                                                                                        $basEntity->getChannel());
        }

        return $this->balance;
    }

    protected function processSourceEntity(Entity $basEntity)
    {
        if ($basEntity->isTypeCredit() === true)
        {
            $sourceEntity = $this->processReversal($basEntity);
        }
        else
        {
            $sourceEntity = $this->processPayout($basEntity);
        }

        if ($sourceEntity === null)
        {
            $sourceEntity = $this->processExternal($basEntity);
        }

        $this->validateBalance($basEntity, $sourceEntity);

        return $sourceEntity;
    }

    protected function processReversal(Entity $basEntity)
    {
        $reversal = $this->fetchExistingReversalIfPresent($basEntity);

        if ($reversal === null)
        {
            return null;
        }

        //
        // TODO: Explore creating a reversal entity and its transaction here
        // if we are able to map the reversal BAS to a payout entity in the system.
        // Earlier, we had decided that we will not make any changes to any entity
        // as part of transaction flow. Also, this should be an edge case where we
        // haven't fetched the status yet, but we fetched the account statement.
        // But, this can also happen: when we fetched the status, it wasn't
        // reversed yet, but when we fetched the transaction, it was reversed.
        // But, for this reason, we decided to run the status check for 7 days
        // for processed payouts.
        // We can probably optimize for this later.
        //

        // TODO: Add a test case for this.
        $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);

        return $reversal;
    }

    protected function processPayout(Entity $basEntity)
    {
        $payout = $this->fetchExistingPayoutIfPresent($basEntity);

        if ($payout === null)
        {
            return null;
        }

        // We are checking for $payout->isStatusFailed(), because its possible that due to a code-miss,
        // a 'reversed' payout is marked as a 'failed' payout, in which case we will get a 'failed' payout
        // matching a BAS, which should be impossible ideally, because a Failed payout, means no debit
        // ever happened. So to ensure that error case is handled we are checking for 'failed' payouts too
        if ($payout->isStatusFailed() === true)
        {
            $this->trace->error(
                TraceCode::BAS_ENTRY_FOR_A_FAILED_PAYOUT,
                 [
                     'bas_id'    => $basEntity->getId(),
                     'payout_id' => $payout->getId()
                 ]);

            return null;
        }

        (new DownstreamProcessor('fund_account_payout', $payout, $this->mode))->processTransaction();

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    protected function processExternal(Entity $basEntity)
    {
        $external = (new External\Core)->create($basEntity);

        return $external;
    }

    protected function fetchExistingReversalIfPresent(Entity $basEntity)
    {
        $utr = $basEntity->getUtr();

        if (empty($utr) === true)
        {
            return null;
        }

        $balance = $this->getBalance($basEntity);

        $reversal = $this->repo
                         ->reversal
                         ->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId())
                         ->first();

        return $reversal;
    }

    /**
     * TODO: The logic would be different based on the channel.
     * Refactor this when adding more banks here.
     *
     * @param Entity $basEntity
     *
     * @return mixed
     * @throws Exception\LogicException
     */
    protected function fetchExistingPayoutIfPresent(Entity $basEntity)
    {
        $payouts = new Base\Collection;

        $balance = $this->getBalance($basEntity);

        $utr = $basEntity->getUtr();

        //
        // We first try to retrieve the payout from UTR, present in the description.
        //
        // In case of payouts
        // - IMPS is the most common mode
        // - UTR retrieval is supported only for IMPS.
        // - We do not know the mode via BAS entity. If we did, we could
        //   fetch using UTR or bank_transaction_id depending on the mode.
        // Due to the above two reasons, we try to fetch a payout using UTR first.
        //
        if (empty($utr) === false)
        {
            $payouts = $this->repo->payout->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId());
        }

        //
        // If either the UTR is not present in the description or if we were not able
        // to retrieve any payouts using the UTR, we try with bank_transaction_id
        //
        if ($payouts->count() === 0)
        {
            $bankTxnId = $basEntity->getBankTransactionId();

            $payouts = $this->repo->payout->fetchFromCmsRefNumber($bankTxnId,
                                                                  $basEntity->getAmount(),
                                                                  $balance->getId());
        }

        //
        // Finally, if the search with either UTR or with bank_transaction_id gave more
        // results than 1, it means our logic is wrong and needs to be re-looked at.
        //
        if ($payouts->count() > 1)
        {
            throw new Exception\LogicException(
                'Too many payouts found for the given criteria',
                ErrorCode::SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND,
                [
                    'bas_id'        => $basEntity->getId(),
                    'balance_id'    => $balance->getId(),
                    'utr'           => $utr,
                    'count'         => $payouts->count()
                ]);
        }

        return $payouts->first();
    }

    protected function validateBalance(Entity $basEntity, Base\PublicEntity $sourceEntity)
    {
        $balanceCalculated = $sourceEntity->transaction->getBalance();

        $balanceAtBankSide = $basEntity->getBalance();

        if ($balanceCalculated !== $balanceAtBankSide)
        {
            throw new Exception\LogicException(
                'Balance at channel does not match with our balance',
                ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_STATEMENT_BALANCES_DO_NOT_MATCH,
                [
                    'rzp_balance'     => $balanceCalculated,
                    'channel_balance' => $balanceAtBankSide,
                    'account_number'  => $basEntity->getAccountNumber(),
                    'bank_txn_id'     => $basEntity->getBankTransactionId(),
                ]
            );
        }
    }

    protected function checkAndTraceForStaleResponse(int $bankTxnCount, int $processedCount, string $accountNumber)
    {
        //
        // Check for stale response from the channel,
        // This happens when we have stored the txns already,
        // but the bank is still sending us the same txns again
        // Since we are using the last txn for pagination, we
        // should never be receiving the same txns again
        //
        if (($bankTxnCount > 0) and ($processedCount === 0))
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_STATEMENT_STALE_RESPONSE,
                [
                    'account_number'    => $accountNumber,
                    'total'             => $bankTxnCount,
                    'processed'         => $processedCount,
                ]);
        }
    }

    //
    // 0. Trace the request here.
    //
    // 1. Fetch accountNumbers to process for that channel
    // We will fetch accountNumbers per channel ascending order by last_statement_fetch_at
    //
    // Create and dispatch jobs to pull data for those MIDs
    // Return accountNumbers dispatched for processing for the route response
    //
    public function dispatchAccountNumberForChannel(string $channel, array $input)
    {
        $limit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT]);

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT;
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_CRON,
            [
                'channel'        => $channel,
            ]);

        $accountNumbers = $this->repo->banking_account->fetchAccountNumbersByChannel($channel, $limit)->pluck(Entity::ACCOUNT_NUMBER);

        foreach ($accountNumbers as $accountNumber)
        {
            $this->dispatchBankingAccountStatementJob($channel, $accountNumber);
        }

        return ['account_processed' => $accountNumbers];
    }

    public function dispatchBankingAccountStatementJob(string $channel, string $accountNumber)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_REQUEST,
            [
                'channel'        => $channel,
                'accountNumber'  => $accountNumber,
            ]);

        BankingAccountStatementJob::dispatch($this->mode,
                                             [
                                                 'channel'       => $channel,
                                                 'account_number' => $accountNumber
                                             ]);
    }
}
