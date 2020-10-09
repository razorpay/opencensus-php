<?php

namespace RZP\Models\BankingAccountStatement;

use Mail;
use File;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;

class Core extends Base\Core
{
    const STORE_TYPE         = 'transactions';

    const FILE_ID            = 'file_id';

    const DASHBOARD_FILE_URL = '%sufh/file/%s';

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT = 6;

    const DEFAULT_RX_BAS_FORCED_FETCH_TIME_IN_HOURS = 8;

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
     * @throws Exception\BadRequestException
     */
    public function processStatementForAccount(array $input)
    {
        $channel = array_pull($input, Entity::CHANNEL);

        $accountNumber = array_pull($input, Entity::ACCOUNT_NUMBER);

        try
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_REMOTE_FETCH_REQUEST,
                [
                    'channel'       => $channel,
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
                    null,
                    TraceCode::ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS,
                    [
                        'channel'           => $channel,
                        'account_number'    => $accountNumber,
                        'message'           => $e->getMessage(),
                    ]);
            }
            else
            {
                throw $e;
            }
        }

        return ['channel' => $channel, 'account_number' => $accountNumber];
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
        $bankPostedDate = $bankTransaction[Entity::POSTED_DATE];
        $bankTxnChannel = $bankTransaction[Entity::CHANNEL];
        $bankTxnId      = $bankTransaction[Entity::BANK_TRANSACTION_ID];

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_TRANSACTION_BEING_SAVED,
            [
                'bank_txn_id'           => $bankTxnId,
                'bank_txn_posted_date'  => $bankPostedDate,
                'bank_txn_channel'      => $bankTxnChannel,
            ]);

        list($sourceEntity, $isSourceAlreadyCreated) = $this->repo->transaction(function () use ($bankTransaction, $merchant) {

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

            list($sourceEntity, $isSourceAlreadyCreated) = $this->processSourceEntity($basEntity);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_CREATION, $sourceEntity->toArray());

            $basEntity->source()->associate($sourceEntity);

            $basEntity->transaction()->associate($sourceEntity->transaction);

            (new Transaction\Core)->updatePostedDate($sourceEntity, $basEntity->getPostedDate());

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_SAVE, $basEntity->toArray());

            $this->repo->saveOrFail($basEntity);

            return [$sourceEntity, $isSourceAlreadyCreated];
        });

        $this->fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated);
    }

    protected function fireWebhooksAfterSuccessfulMappingOfSourceEntity($sourceEntity, $isSourceAlreadyCreated)
    {
        $sourceTransaction = $sourceEntity->transaction;

        $sourceTransaction->load('source');

        if ($sourceEntity->getEntityName() === Constants\Entity::EXTERNAL)
        {
            // transaction.created webhook for external transactions
            $variant = $this->app->razorx->getTreatment($sourceEntity->merchant->getId(),
                                                        Merchant\RazorxTreatment::BLOCK_EXTERNAL_TRANSACTION_CREATED_WEBHOOK_RBL,
                                                        $this->mode);

            // by default webhook will be sent for all merchants , if experiment is on for that merchant then webhook
            // will not be sent. In case razorx is down webhook will be sent.
            if ($variant !== 'on')
            {
                (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification(
                    $sourceEntity->transaction);
            }
        }
        else
        {
            // TODO: refactor using wasRecentlyCreated
            // https://razorpay.atlassian.net/browse/RX-2630
            if (($sourceEntity->getEntityName() === Constants\Entity::REVERSAL) and
                ($isSourceAlreadyCreated === false))
            {
                $this->app->events->fire('api.payout.reversed', $sourceEntity->entity);
            }

            (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification(
                $sourceEntity->transaction);
        }
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
        // to ensure duplicate webhook doesn't get fired
        $isSourceAlreadyCreated = true;

        if ($basEntity->isTypeCredit() === true)
        {
            list($sourceEntity, $isSourceAlreadyCreated) = $this->processReversal($basEntity);
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

        return [$sourceEntity, $isSourceAlreadyCreated];
    }

    protected function processReversal(Entity $basEntity)
    {
        $reversal = $this->fetchExistingReversalIfPresent($basEntity);

        // this is to ensure that payout.reversed webhook does not get fired twice. i.e
        // it only gets fired in this flow if a new reversal entity is created and we are not
        // able to map existing reversal to bas. Because if we were able to map existing reversal,
        // that means that webhook would have been fired already when that reversal entity was
        // created
        $isReversalAlreadyCreated = true;

        if ($reversal === null)
        {
            $isReversalAlreadyCreated = false;

            // There can be a possibilty that a payout is not marked reversed due
            // to some code-or-mapping miss at Mozart layer or if the webhook from
            // FTS to API is missed. In that case, we will not be able to
            // find any reversal for the credit row. So we will check if there
            // is a payout in the system for which this is the credit row.
            // If a payout is found, then we will also check if it has is marked
            // reversed, if not we will update the payout as reversed
            //
            /** @var Payout\Entity $existingPayout */
            $existingPayout = $this->fetchExistingPayoutForAccountStatement($basEntity);

            if ($existingPayout === null)
            {
                return null;
            }

            $this->trace->info(TraceCode::AUTO_RECON_PAYOUT_REVERSAL_CREATE_REQUEST,
                            [
                                'payout_id' => $existingPayout->getId()
                            ]);

            (new Payout\Core)->reversePayout($existingPayout,
                'REVERSAL');

            $reversal = $existingPayout->reversal;

            $this->trace->info(TraceCode::AUTO_RECON_PAYOUT_REVERSAL_CREATED,
                [
                    'payout_id'     => $existingPayout->getId(),
                    'reversal_id'   => $reversal->getId(),
                ]);
        }

        if (($reversal !== null) and
            ($reversal->transaction === null))
        {
            $reversal = (new Reversal\Core)->createTransactionFromPayoutReversal($reversal);

            $this->trace->info(TraceCode::REVERSAL_TRANSACTION_CREATED,
                [
                    'reversal_id'       => $reversal->getId(),
                    'transaction_id'    => $reversal->transaction->getId(),
                ]);
        }

        return [$reversal, $isReversalAlreadyCreated];
    }

    protected function processPayout(Entity $basEntity)
    {
        $payout = $this->fetchExistingPayoutForAccountStatement($basEntity);

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

    protected function fetchExistingPayoutForAccountStatement(Entity $basEntity)
    {
        $payouts = new Base\Collection;

        $balance = $this->getBalance($basEntity);

        $utr = $basEntity->getUtr();

        $unlinkedPayouts = [];

        if (empty($utr) === false)
        {
            if ($basEntity->getType() === Type::CREDIT)
            {
                /** @var Base\Collection $payouts */
                $payouts = $this->repo->payout->fetchFromReturnUtr($utr, $basEntity->getAmount(), $balance->getId());

                if ($payouts->count() === 1)
                {
                    return $payouts->first();
                }

                // TODO: remove unique constraint from utr fields in db .
                // https://razorpay.atlassian.net/browse/RX-2390
                if ($payouts->count() > 1)
                {
                    throw new Exception\LogicException(
                        'Too many payouts found after scrapping via return utr',
                        ErrorCode::SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND_VIA_RETURN_UTR,
                        [
                            'bas_id'        => $basEntity->getId(),
                            'balance_id'    => $balance->getId(),
                            'utr'           => $utr,
                            'count'         => $payouts->count()
                        ]);
                }
            }

            $payouts = $this->repo->payout->fetchFromUtr($utr, $basEntity->getAmount(), $balance->getId());

            if ($basEntity->getType() === Type::CREDIT)
            {
                if ($payouts->count() === 1)
                {
                    return $payouts->first();
                }
                else if ($payouts->count() > 1)
                {
                    $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_UTR_TYPE_CREDIT, [
                        'payout_ids' => $payouts->getQueueableIds(),
                        'bas_id'     => $basEntity->getId(),
                        'channel'    => $basEntity->getChannel(),
                        'amount'     => $basEntity->getAmount(),
                    ]);
                }
            }

            foreach ($payouts as $key => $payout)
            {
                if ($payout->getTransactionId() !== null)
                {
                    $data = [
                        'channel'                  => $basEntity->getChannel(),
                        'amount'                   => $basEntity->getAmount(),
                        'current_payout_id'        => $payout->getId(),
                        'payout_ids_with_same_utr' => $payouts->getQueueableIds(),
                    ];

                    $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_DUPLICATE_UTR, [
                        'data' => $data,
                    ]);

                    $operation = 'duplicate UTR in account statement fetch for a linked payout';

                    (new SlackNotification)->send(
                                                $operation,
                                                $data,
                                                null,
                                                1,
                                                'rx_ca_rbl_alerts');

                    unset($payouts[$key]);
                }
                else
                {
                    $unlinkedPayouts[] = $payout;
                }
            }

            if (count($unlinkedPayouts) === 1)
            {
                return $unlinkedPayouts[0];
            }
            // RBL has confirmed that UTR will be unique across all transactions
            // of RBL and so we not process this account statement record
            if (count($unlinkedPayouts) > 1)
            {
                throw new Exception\LogicException(
                    'Too many unlinked payouts found after utr match',
                    ErrorCode::SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND_VIA_UTR,
                    [
                        'bas_id'        => $basEntity->getId(),
                        'balance_id'    => $balance->getId(),
                        'utr'           => $utr,
                        'count'         => $payouts->count()
                    ]);
            }
        }

        if ($payouts->count() === 0)
        {
            $bankTxnId = $basEntity->getBankTransactionId();

            $bankTimeBeforePostedDate = Carbon::createFromTimestamp(
                                                        $basEntity->getPostedDate(), Timezone::IST)
                                                        ->subHours(4)
                                                        ->getTimestamp();

            $payouts = $this->repo->payout->fetchUnlinkedPayoutsFromCmsRefNumberWithinTimeRangeForIFT(
                                                                                       $bankTxnId,
                                                                                       $basEntity->getPostedDate(),
                                                                                       $bankTimeBeforePostedDate,
                                                                                       $basEntity->getAmount(),
                                                                                       $balance->getId());
        }

        if ($payouts->count() === 1)
        {
            return $payouts->first();
        }

        if ($payouts->count() > 1)
        {
            throw new Exception\LogicException(
                'Too many unlinked payouts found after scrapping via cms ref number for IFT',
                ErrorCode::SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND_VIA_CMS_REF_NO_FOR_IFT,
                [
                    'bas_id'        => $basEntity->getId(),
                    'balance_id'    => $balance->getId(),
                    'utr'           => $utr,
                    'count'         => $payouts->count()
                ]);
        }

        $payouts = $this->repo->payout->fetchUnlinkedPayoutsFromCmsRefNumber(
                                                                        $bankTxnId,
                                                                        $basEntity->getAmount(),
                                                                        $balance->getId());

        if ($payouts->count() > 1)
        {
            throw new Exception\LogicException(
                'Too many unlinked payouts found after scrapping via cms ref number for non IFT',
                ErrorCode::SERVER_ERROR_TOO_MANY_PAYOUTS_FOUND_VIA_CMS_REF_NO_FOR_NON_IFT,
                [
                    'bas_id'        => $basEntity->getId(),
                    'balance_id'    => $balance->getId(),
                    'utr'           => $utr,
                    'count'         => $payouts->count(),
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
    // We will fetch accountNumbers per channel ascending order by last_statement_fetch_at and pass the details through
    // a filter which would select primarily accounts whose statement was fetched more than certain hours ago and which
    // made payouts.
    //
    // Create and dispatch jobs to pull data for those MIDs
    // Return accountNumbers dispatched for processing for the route response
    //
    public function dispatchAccountNumberForChannel(string $channel, array $input)
    {
        $limit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT]);
        $forcedFetchTime = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::RX_BAS_FORCED_FETCH_TIME_IN_HOURS]);

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_RATE_LIMIT;
        }

        if (empty($forcedFetchTime) === true)
        {
            $forcedFetchTime = self::DEFAULT_RX_BAS_FORCED_FETCH_TIME_IN_HOURS;
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_CRON_INITIATED,
            [
                'channel'                               => $channel,
                'banking_account_statement_rate_limit'  => $limit,
                'bas_force_fetch_time_hrs'              => $forcedFetchTime,
            ]);

        $bankingAccountDetails = $this->repo->banking_account->fetchAccountNumbersByChannel($channel);
        $accountNumbersToDispatch = [];
        $accountsThatMadePayouts = [];
        $otherAccounts = [];
        $numberOfAccountsSelected = 0;

        $currentTime = Carbon::now()->getTimestamp();

        foreach ($bankingAccountDetails as $bankingAccountDetail)
        {
            $forcedFetchTimeInSeconds = $forcedFetchTime * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

            if ($bankingAccountDetail->getLastStatementAttemptAt() <= $currentTime - $forcedFetchTimeInSeconds)
            {
                $accountNumbersToDispatch[$numberOfAccountsSelected] = $bankingAccountDetail->getAccountNumber();

                $numberOfAccountsSelected++;
            }
            else
            {
                if (count($accountsThatMadePayouts) < $limit - $numberOfAccountsSelected)
                {
                    $count = $this->repo->payout->countOfPayoutsMadeForDirectAccountSinceLastStatementFetch(
                        $bankingAccountDetail->getBalanceId(),
                        $bankingAccountDetail->getLastStatementAttemptAt());

                    if ($count > 0)
                    {
                        array_push($accountsThatMadePayouts, $bankingAccountDetail->getAccountNumber());
                    }
                    else
                    {
                        array_push($otherAccounts, $bankingAccountDetail->getAccountNumber());
                    }
                }
            }

            if ($numberOfAccountsSelected >= $limit)
            {
                break;
            }
        }

        $accountNumbersToDispatchUnderForceFetchRule = $accountNumbersToDispatch;

        if ($numberOfAccountsSelected < $limit)
        {
            foreach ($accountsThatMadePayouts as $accountThatMadePayouts)
            {
                $accountNumbersToDispatch[$numberOfAccountsSelected] = $accountThatMadePayouts;

                $numberOfAccountsSelected++;

                if ($numberOfAccountsSelected >= $limit)
                {
                    break;
                }
            }
        }

        if ($numberOfAccountsSelected < $limit)
        {
            foreach ($otherAccounts as $otherAccount)
            {
                $accountNumbersToDispatch[$numberOfAccountsSelected] = $otherAccount;

                $numberOfAccountsSelected++;

                if ($numberOfAccountsSelected >= $limit)
                {
                    break;
                }
            }
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_DISPATCH_JOB_CRON,
            [
                'currentTime'                                       => $currentTime,
                'channel'                                           => $channel,
                'account_numbers_under_force_fetch_rule'            => $accountNumbersToDispatchUnderForceFetchRule,
                'account_numbers_to_be_dispatched_if_made_payouts'  => $accountsThatMadePayouts,
                'account_numbers_to_be_dispatched'                  => $accountNumbersToDispatch,
            ]);

        foreach ($accountNumbersToDispatch as $accountNumber)
        {
            $this->dispatchBankingAccountStatementJob($channel, $accountNumber);
        }

        return ['account_processed' => $accountNumbersToDispatch];
    }

    // Adding a delay in dispatch and default is 0 min delay.
    public function dispatchBankingAccountStatementJob(string $channel,
                                                       string $accountNumber,
                                                       int $delay = 0)
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
                                             ])->delay($delay);
    }
}
