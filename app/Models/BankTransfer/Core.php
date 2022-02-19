<?php

namespace RZP\Models\BankTransfer;

use Config;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Jobs\LedgerStatus;
use RZP\Jobs\Transactions;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankTransferRequest;
use RZP\Models\Payment\Refund as PaymentRefund;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Models\Transaction\Processor\Ledger\FundLoading as LedgerFundLoading;

class Core extends Base\Core
{
    protected $mutex;

    protected $bankTransferRequestCore;

    protected $virtualAccountMetrics;

    const MUTEX_KEY = 'bank_transfer_processing_%s_%s';

    const NRE_FAILURE_MESSAGES = [
        'neft-return credit to nri account',
        'imps-rtn-nre account',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->bankTransferRequestCore = new BankTransferRequest\Core();

        $this->virtualAccountMetrics = new VirtualAccount\Metric();
    }

    /**
     * Creates a bank account entity, but doesn't save. Save is done later. Creation is needed
     * before because we need validations and generations to run for further processing.
     *
     * @param array  $input
     *
     * @param string $provider
     *
     * @return Entity
     */
    protected function create(array $input, string $provider)
    {
        // This method does not save to DB. It should not save to DB,
        // because it is used to validate-and-modify the input received
        // in the notify request. We only create a bank_transfer obj here.
        $bankTransfer = (new Entity)->build($input);

        $bankTransfer->setGateway($provider);

        $bankTransfer->setStatus(Status::CREATED);

        return $bankTransfer;
    }

    /**
     * Creates bank transfer and calls processor with it.
     * Implements mutex lock to avoid race conditions.
     * Catches validationExceptions to stop unnecessary retries.
     *
     * @param array  $input
     *
     * @param string $provider
     *
     * @return bool
     */
    public function process(array $input, string $provider)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESSING,
            $this->removePiiForLogging($input)
        );

        $processor = new Processor();

        $mutexKey = sprintf(self::MUTEX_KEY, $input[Entity::REQ_UTR], $input[Entity::PAYEE_ACCOUNT]);

        $bankTransfer = null;

        $paymentSuccess = false;

        $errorMessage = null;

        try
        {
            $bankTransfer = $this->create($input, $provider);

            $this->mutex->acquireAndRelease(
                $mutexKey,
                function() use ($processor, $bankTransfer)
                {
                    $processor->process($bankTransfer);
                },
                60,
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
                10,
                200,
                400);

            $paymentSuccess = true;
        }
        catch (\Throwable $ex)
        {
            $paymentSuccess = false;

            $errorMessage = $ex->getMessage();

            switch($errorMessage)
            {
                case TraceCode::REFUND_OR_CAPTURE_PAYMENT_FAILED:
                    $paymentSuccess = true;
                    break;

                default:
                    $paymentSuccess = false;
            }

            return $this->alertException($ex, $input);
        }
        finally
        {
            $isExpected = null;

            if ($bankTransfer !== null)
            {
                $isExpected = $bankTransfer->isExpected();

                $errorMessage = $errorMessage ?? $bankTransfer->getUnexpectedReason();
            }

            $this->bankTransferRequestCore->updateBankTransferRequest($input[Entity::REQ_UTR], $paymentSuccess, $errorMessage);

            (new VirtualAccount\Metric())->pushPaymentMetrics(Constants\Entity::BANK_TRANSFER, $isExpected, $paymentSuccess, $provider, $errorMessage);

            $this->pushBankTransferSourceToLake($bankTransfer);
        }

        return true;
    }

    public function processBankTransfer(BankTransferRequest\Entity $bankTransferRequest)
    {
        $bankTransferInput = $bankTransferRequest->getBankTransferProcessInput();
        $provider = $bankTransferRequest->getGateway();
        $paymentSuccess = false;
        $bankTransfer = null;
        $errorMessage = null;

        try
        {
            $bankTransfer = $this->processBankTransferRequest($bankTransferRequest, $bankTransferInput, $provider);

            $paymentSuccess = ($bankTransfer !== null);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $bankTransferRequest->toArrayTrace());

            $errorMessage = $ex->getMessage();

            switch ($errorMessage)
            {
                case TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR:
                    return true;

                case TraceCode::REFUND_OR_CAPTURE_PAYMENT_FAILED:
                    $paymentSuccess = true;
                    break;

                default:
                    return false;
            }
        }
        finally
        {
            // Todo: below function pushes $paymentSuccess = true, check if in case of ledger async retries (txn will be eventually done from jobs) its ok to do so
            $this->postProcessBankTransferUpdation($bankTransfer, $bankTransferInput, $bankTransferRequest, $errorMessage, $paymentSuccess);
        }

        return true;
    }

    public function processBankTransferRequest(
        BankTransferRequest\Entity $bankTransferRequest,
        $bankTransferInput,
        $provider
    )
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESSING,
            $bankTransferRequest->toArrayTrace()
        );

        $bankTransfer = null;
        $errorMessage = null;

        $bankTransfer = $this->create($bankTransferInput, $provider);

        $requestSource = $bankTransferRequest->getRequestSource();

        $bankTransfer->setRequestSource($requestSource);

        $processor = new Processor();

        $mutexKey = sprintf(self::MUTEX_KEY, $bankTransferInput[Entity::REQ_UTR], $bankTransferInput[Entity::PAYEE_ACCOUNT]);

        $bankTransfer = $this->mutex->acquireAndRelease(
            $mutexKey,
            function () use ($processor, $bankTransfer)
            {
                return $processor->process($bankTransfer);
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
            10,
            200,
            400);

        return $bankTransfer;
    }

    public function postProcessBankTransferUpdation(
        $bankTransfer,
        $bankTransferInput,
        $bankTransferRequest,
        $errorMessage = null,
        $paymentSuccess = false
    )
    {
        $provider = $bankTransferRequest->getGateway();

        $isExpected = null;

        if ($bankTransfer !== null)
        {
            $isExpected = $bankTransfer->isExpected();

            $errorMessage = $errorMessage ?? $bankTransfer->getUnexpectedReason();
        }

        $this->bankTransferRequestCore
            ->updateBankTransferRequest($bankTransferInput[Entity::REQ_UTR], $paymentSuccess, $errorMessage, $bankTransferRequest);

        $this->virtualAccountMetrics
            ->pushPaymentMetrics(Constants\Entity::BANK_TRANSFER, $isExpected, $paymentSuccess, $provider, $errorMessage);
    }

    public function getAccountForRefund(Entity $bankTransfer)
    {
        $payerAccount = $this->updateAndFetchPayerAccount($bankTransfer);

        return [
            BankAccount\Entity::IFSC_CODE        => $payerAccount->getIfscCode(),
            BankAccount\Entity::ACCOUNT_NUMBER   => $payerAccount->getAccountNumber(),
            BankAccount\Entity::BENEFICIARY_NAME => $payerAccount->getBeneficiaryName()
        ];
    }

    /**
     * This exists for older bank transfer payments. For new payments, we
     * create the payer bank account with the mapped IFSC. For older ones,
     * if the bank account has no IFSC, we set it to the mapped IFSC now.
     *
     * @param Entity $bankTransfer
     *
     * @return BankAccount\Entity
     */
    protected function updateAndFetchPayerAccount(Entity $bankTransfer): BankAccount\Entity
    {
        $payerAccount = $bankTransfer->payerBankAccount;

        if ($payerAccount->getIfscCode() === null)
        {
            $ifsc = PayerBankAccount::getPayerIfsc($bankTransfer);

            $payerAccount->setIfsc($ifsc);

            $this->repo->saveOrFail($payerAccount);
        }

        return $payerAccount;
    }

    /**
     * Trace to splunk, and also send an alert to Slack.
     *
     * @param  \Throwable $ex
     * @param  array      $input
     */
    protected function alertException(\Throwable $ex, array $input)
    {
        //
        // Empty request is not really actionable, trace info and skip Slack
        //
        if (empty($input) === true)
        {
            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_FAILED,
                [
                    'input' => $this->removePiiForLogging($input, [Entity::PAYEE_ACCOUNT]),
                ]
            );

            return;
        }

        // Any non-trivial (non-empty request) exception is critical, as
        // bank transfers are never supposed to fail. Trace accordingly.
        $this->trace->traceException(
            $ex, Trace::CRITICAL, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $this->removePiiForLogging($input, [Entity::PAYEE_ACCOUNT]));

        // Slack alerts are only for prod
        if (($this->isEnvironmentProduction() === false) or
            ($this->isTestMode() === true))
        {
            return;
        }

        $this->app['slack']->queue(
            TraceCode::BANK_TRANSFER_PROCESSING_FAILED,
            array_merge($input, ['message' => $ex->getMessage()]),
            [
                'channel'  => Config::get('slack.channels.virtual_accounts_log'),
                'username' => 'Scrooge',
                'icon'     => ':x:'
            ]
        );

        return false;
    }

    /**
     * Kotak has a second route that it hits to notify us of a bank transfer payment. It was useful
     * when these APIs were being planned, but serves no real purpose now. To not lose the info,
     * all we do here is validate input, find the bank transfer and marked it as 'notified'.
     *
     * @param array  $input
     *
     * @param string $provider
     *
     * @return bool
     */
    public function notify(array $input, string $provider)
    {
        try
        {
            // Bank Transfer core does not save to DB in this step.
            // This is effectively just a modify-and-validate.
            $this->create($input, $provider);

            $bankTransfer = $this->repo
                                 ->bank_transfer
                                 ->findByUtrAndPayeeAccountAndAmount(
                                    $input[Entity::REQ_UTR],
                                    $input[Entity::PAYEE_ACCOUNT],
                                    $input[Entity::AMOUNT] * 100);

            if ($bankTransfer !== null)
            {
                $this->notifyIfApplicable($bankTransfer);
            }
            else
            {
                $this->trace->error(
                    TraceCode::BANK_TRANSFER_UNEXPECTED_NOTIFY,
                    [
                        'input' => $this->removePiiForLogging($input),
                    ]
                );
            }
        }
        catch (\Throwable $ex)
        {
            $this->alertException($ex, $input);
        }

        return true;
    }

    /**
     * Marks the bank transfer as notified.
     *
     * @param Entity $bankTransfer
     */
    protected function notifyIfApplicable(Entity $bankTransfer)
    {
        if ($bankTransfer->isNotified() === false)
        {
            $bankTransfer->setNotified(true);

            $this->repo->saveOrFail($bankTransfer);
        }
    }

    /**
     * Processes refund retries, but skips those
     * with fund_transfer_attempt already created.
     *
     * @param array $input
     *
     * @return array
     */
    public function retryBankTransferRefund(array $input)
    {
        $refunds = $this->getRefundsToRetry($input);

        $this->trace->info(
            TraceCode::REFUND_RETRY_INITIATED,
            [
                'input'      => $input,
                'refund_ids' => $refunds->getIds()
            ]);

        $status  = [];
        $success = 0;
        $failure = 0;

        foreach ($refunds as $refund)
        {
            if ($this->skipRefund($refund) === true)
            {
                $this->trace->info(
                    TraceCode::REFUND_RETRY_SKIPPED,
                    [
                        'refund_id'     => $refund->getPublicId(),
                        'refund_status' => $refund->getStatus(),
                    ]);

                continue;
            }

            try
            {
                $processor = $this->getNewProcessor($refund->merchant);

                $status[$refund->getPublicId()] = $processor->processRefundRetry($refund);

                $success++;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::INFO,
                    TraceCode::PAYMENT_VERIFY_REFUND_EXCEPTION,
                    [
                        'refund_id'       => $refund->getPublicId(),
                    ]);

                $failure++;
            }
        }

        return [
            'successful'    => $success,
            'failure'       => $failure,
            'status'        => $status,
        ];
    }

    protected function skipRefund(PaymentRefund\Entity $refund)
    {
        if ($refund->isStatusFailed() === false)
        {
            return true;
        }

        $latestAttempt = $refund->fundTransferAttempts->last();

        if (($latestAttempt !== null) and
            ($this->isRefundToNreAccount($latestAttempt) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * If the last attempt failed with one of these messages, we
     * can consider it a hard bounce and not make more attempts.
     *
     * @param $latestAttempt
     *
     * @return boolean
     */
    protected function isRefundToNreAccount($latestAttempt)
    {
        $msg = strtolower($latestAttempt->getRemarks());

        if (in_array($msg, self::NRE_FAILURE_MESSAGES, true) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * New processor instance for retrying refund
     *
     * @param $merchant
     *
     * @return Payment\Processor\Processor
     */
    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }

    /**
     * Fetches failed refunds to retry, or takes from input
     *
     * @param array $input
     *
     * @return mixed
     */
    protected function getRefundsToRetry(array $input)
    {
        if (isset($input['ids']) === true)
        {
            $refunds = $this->repo
                            ->refund
                            ->findManyByPublicIds($input['ids']);
        }
        else
        {
            $method = Payment\Method::BANK_TRANSFER;

            $refunds = $this->repo
                            ->refund
                            ->fetchFailedRefundsByMethod($method);
        }

        return $refunds;
    }

    public function editPayerBankAccount(Entity $bankTransfer, array $input)
    {
        $payerBankAccount = $bankTransfer->payerBankAccount;

        if ($payerBankAccount === null)
        {
            $payerBankAccount = $this->createPayerBankAccount($bankTransfer, $input);

            $bankTransfer->payerBankAccount()->associate($payerBankAccount);
        }
        else
        {
            $payerBankAccount = $payerBankAccount->edit($input, 'editVirtualBankAccount');
        }

        $this->repo->saveOrFail($payerBankAccount);

        $this->editBankTransfer($bankTransfer, $input);

        $this->repo->saveOrFail($bankTransfer);

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PAYER_BANK_ACCOUNT_EDITED,
            [
                'bank_account_id' => $bankTransfer->getId(),
                'input'           => $this->removePiiForLogging($input,[BankAccount\Entity::ACCOUNT_NUMBER])
            ]);

        return $bankTransfer;
    }

    protected function createPayerBankAccount(Entity $bankTransfer, array $input)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = PayerBankAccount::getBankAccountInput($bankTransfer, $input);

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($bankTransfer->merchant);

        $bankAccount->source()->associate($bankTransfer->virtualAccount);

        return $bankAccount;
    }

    /**
     * Calculate fees that will be charged for an order,
     * assuming it is paid using bank_transfer.
     *
     * This is used in two places, for customer_fee_bearer merchants:
     * 1) To set amount_expected when creating the VA for the order
     * 2) To set fees in payment request, used in bank_tranfer_process
     *
     * @param  Order\Entity $order [description]
     *
     * @return mixed
     */
    public function getFeesForOrder(Order\Entity $order)
    {
        return $this->getFees($order->getAmountDue(), $order->merchant, $order->getCurrency());
    }

    public function getFeesForBankTransfer(Entity $bankTransfer, Merchant\Entity $merchant)
    {
        // TODO: Change the third parameter below once we add currency support in Bank Transfer
        return $this->getFees($bankTransfer->getAmount(), $merchant, Currency::INR);
    }

    /**
     * @param int             $amount
     * @param Merchant\Entity $merchant
     * @param string          $currency
     *
     * @return mixed
     */
    protected function getFees(int $amount, Merchant\Entity $merchant, string $currency)
    {
        $request = [
            Payment\Entity::AMOUNT   => $amount,
            Payment\Entity::CURRENCY => $currency,
            Payment\Entity::METHOD   => Payment\Method::BANK_TRANSFER,
        ];

        $paymentProcessor = new PaymentProcessor($merchant);

        $data = $paymentProcessor->processAndReturnFees($request);

        return $data['fees'];
    }

    protected function editBankTransfer(Entity $bankTransfer, array $input)
    {
        $mapping = [
            BankAccount\Entity::BENEFICIARY_NAME        => Entity::PAYER_NAME,
            BankAccount\Entity::ACCOUNT_NUMBER          => Entity::PAYER_ACCOUNT,
            BankAccount\Entity::IFSC_CODE               => Entity::PAYER_IFSC,
            'bank_account_id'                           => Entity::PAYER_BANK_ACCOUNT_ID,
        ];

        $data = [];

        foreach ($input as $key => $value)
        {
            $data[$mapping[$key]] = $value;
        }

        $bankTransfer->edit($data, 'editBankTransfer');
    }

    protected function pushBankTransferSourceToLake($bankTransfer)
    {
        if ($bankTransfer === null)
        {
            return;
        }

        $routeName = $this->app['api.route']->getCurrentRouteName();

        $properties = [];

        switch ($routeName)
        {
            case 'bank_transfer_process':
                $properties = [
                    'source'        => 'callback',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_process_rbl':
            case 'bank_transfer_process_icici':
            case 'bank_transfer_process_hdfc_ecms':
                $properties = [
                    'source'        => 'callback',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_process_rbl_internal':
                $properties = [
                    'source'        => 'file',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_process_icici_internal':
                $properties = [
                    'source'        => 'file',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_insert':
                $properties = [
                    'source'        => 'admin_dashboard',
                    'request_from'  => 'admin',
                ];

                break;

            case 'batch_create_admin':
                $properties = [
                    'source'        => 'file',
                    'request_from'  => 'admin',
                ];

                break;

            default:
                $this->trace->info(
                    TraceCode::UNTRACKED_ENDPOINT_BANK_TRANSFER,
                    [
                        'route_name'    => $routeName,
                        'npci_ref_id'   => $bankTransfer->getUtr(),
                    ]);
                break;
        }

        $this->app['diag']->trackBankTransferRequestEvent(
            EventCode::BANK_TRANSFER_REQUEST,
            $bankTransfer,
            null,
            $properties
        );
    }

    /**
     * Pass fields to $fields that are not to be logged.
     * If nothing is passed, default PII fields will be
     * fetched from Entity class. If any field is not to
     * be completely removed, use the switch-case.
     *
     * @param array $array
     * @param array $fields
     * @return array
     */
    public function removePiiForLogging(array $array, array $fields = [])
    {
        if (empty($fields) === true)
        {
            $fields = (new Entity())->getPii();
        }

        foreach ($fields as $field)
        {
            if (isset($array[$field]) === false)
            {
                continue;
            }

            switch ($field)
            {
                case Entity::PAYEE_ACCOUNT:
                    $payeeAccount = $array[Entity::PAYEE_ACCOUNT];

                    $array[Entity::PAYEE_ACCOUNT . '_prefix']       = substr($payeeAccount, 0, 8);
                    $array[Entity::PAYEE_ACCOUNT . '_descriptor']   = substr($payeeAccount, 8, strlen($payeeAccount));

                    break;

                default:
                    break;
            }

            unset($array[$field]);
        }

        return $array;
    }

    /**
     * @throws \Throwable
     */
    public function processBankTransferAfterLedgerStatusCheck($bankTransfer, $ledgerResponse)
    {
        $this->trace->info(
            TraceCode::PROCESS_BANK_TRANSFER_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'bank_transfer_id'      => $bankTransfer->getId(),
                'entity_name'           => Constants\Entity::BANK_TRANSFER,
            ]);

        $bankTransfer->setStatus(Status::PROCESSED);
        $this->repo->saveOrFail($bankTransfer);

        try {
            Transactions::dispatch($this->mode, $bankTransfer->getId(), Constants\Entity::BANK_TRANSFER, $ledgerResponse);
        } catch (\Throwable $ex) {
            // trace and ignore exception
            $payload = [
                'bank_transfer_id'     => $bankTransfer->getId(),
                'entity_name'          => Constants\Entity::BANK_TRANSFER,
                'ledger_response'       => $ledgerResponse,
            ];
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_PUSH_FAILED, $payload);
        }
    }

    public function createBankTransferViaLedgerCronJob(array $blacklistIds, array $forcedMerchantIds, int $limit)
    {
        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all bank transfers created in the last 24 hours.
            // Doing this 3 times in for loop to fetch bank transfers created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $bankTransfers = $this->repo->bank_transfer->fetchCreatedBankTransferAndTxnIdNullBetweenTimestamp($i, $limit);

            foreach ($bankTransfers as $bt)
            {
                try
                {
                    /*
                     * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                     * only then skip the merchant.
                     */
                    if (($bt->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
                        && (in_array($bt->getMerchantId(), $forcedMerchantIds) === false))
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_NOT_REVERSE_SHADOW,
                            [
                                'bank_transfer_id' => $bt->getPublicId(),
                                'merchant_id'      => $bt->getMerchantId(),
                            ]
                        );
                        continue;
                    }

                    if(in_array($bt->getPublicId(), $blacklistIds) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_BLACKLIST_BANK_TRANSFER,
                            [
                                'bank_transfer_id' => $bt->getPublicId(),
                            ]
                        );
                        continue;
                    }

                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_BANK_TRANSFER_INIT,
                        [
                            'bank_transfer_id' => $bt->getPublicId(),
                        ]
                    );

                    $terminal = (new TerminalProcessor())->getTerminalForBankTransfer($bt);
                    $ledgerRequest = (new LedgerFundLoading())->createPayloadForJournalEntry($bt, $terminal->getPublicId(), $terminal->getAccountType());

                    (new LedgerStatus($this->mode, $ledgerRequest, null, false))->handle();
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::LEDGER_STATUS_CRON_BANK_TRANSFER_FAILED,
                        [
                            'bank_transfer_id' => $bt->getPublicId(),
                        ]
                    );

                    continue;
                }
            }
        }
    }
}
