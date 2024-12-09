<?php

namespace RZP\Models\BankTransfer;

use Config;
use App;
use Queue;
use RZP\Constants\Mode;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Address;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Jobs\LedgerStatus;
use RZP\Jobs\Transactions;
use RZP\Models\Transaction;
use RZP\Models\BankAccount;
use RZP\Models\Payout\Metric;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\BankTransferRequest;
use RZP\Models\Payment\Processor\IntlBankTransfer;
use RZP\Models\Payment\Refund as PaymentRefund;
use RZP\Models\Ledger\Constants as LedgerConstants;
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

    const IS_DUPLICATE = "is_duplicate";
    const BANK_TRANSFER_ID ='bank_transfer_id';

    const MAX_INTL_BANK_TRANSFER_AMOUNT_BY_CURRENCIES = [
        Currency\Currency::USD => 29000,
        Currency\Currency::AUD => 43000,
        Currency\Currency::CAD => 40000,
        Currency\Currency::HRK => 200000,
        Currency\Currency::DKK => 198000,
        Currency\Currency::CZK => 670000,
        Currency\Currency::EUR => 26500,
        Currency\Currency::HKD => 225000,
        Currency\Currency::HUF => 106000,
        Currency\Currency::ILS => 109000,
        Currency\Currency::KES => 3745000,
        Currency\Currency::MXN => 560000,
        Currency\Currency::NZD => 47500,
        Currency\Currency::NOK => 310000,
        Currency\Currency::QAR => 105000,
        Currency\Currency::RUB => 2775000,
        Currency\Currency::SAR => 108000,
        Currency\Currency::SGD => 37850,
        Currency\Currency::ZAR => 505000,
        Currency\Currency::SEK => 300000,
        Currency\Currency::CHF => 24900,
        Currency\Currency::THB => 960000,
        Currency\Currency::GBP => 22200,
        Currency\Currency::AED => 106000,
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

        if($input[Entity::IS_COLLECTX_BANK_TRANSFER] === true)
        {
            $bankTransfer->setAttribute(Entity::IS_COLLECTX_BANK_TRANSFER, true);
        }

        return $bankTransfer;
    }

    /**
     * Validate a bank transfer entity, but doesn't save.
     * Used for validation callback for bank transfers to VA before notification callback
     * @param array  $input
     *
     * @param string $provider
     *
     * @return bool
     */
    public function validationsForCallback(array $input, string $provider): bool
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_VALIDATING,
            $this->removePiiForLogging($input)
        );

        $processor = new Processor();

        $bankTransfer = null;

        try
        {
            $bankTransfer = $this->create($input, $provider);

            return $processor->validate($bankTransfer);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::VIRTUAL_ACCOUNT_VALIDATION_ERROR
            );
            return false;
        }
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
        $isCollectXBankTransfer = false;

        try
        {
            if ($bankTransferRequest->isCollectXBankTransfer())
            {
                $bankTransferInput[Entity::IS_COLLECTX_BANK_TRANSFER] = true;

                $isCollectXBankTransfer = true;

                // Removing the attribute from bank transfer request entity as we don't need it anymore
                $bankTransferRequest->removeCollectXAttributes();
            }

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
            $this->postProcessBankTransferUpdation($bankTransfer, $bankTransferInput, $bankTransferRequest, $errorMessage, $paymentSuccess, $isCollectXBankTransfer);
        }

        return true;
    }
    public function manualProcessBankTransferEntity($input)
    {

        $id = $input[self::BANK_TRANSFER_ID];

        $bankTransfer = $this->repo->bank_transfer->findOrFail($id);

        if ($bankTransfer == null)
        {
            throw new Exception\BadRequestException(ErrorCode::BANK_TRANSFER_NOT_FOUND,null,[
                "bank_transfer_id" => $id,
            ]);
        }

        $this->trace->info(
            TraceCode::MANUAL_ACTION_PROCESS_BANK_TRANSFER_REQUEST, [
            "id" => $bankTransfer->getId()
        ]);

        if ($bankTransfer['status'] !== Status::CREATED)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null,[
                "bank_transfer_id" => $id,
            ]);
        }

        $processor = new Processor();

        $bankTransfer->setAttribute(Entity::SKIP_DUPLICATE_CHECK,true);

       $resp = $processor->process($bankTransfer);

        if($resp=== null || $resp['status']!== Status::PROCESSED){

            $this->trace->error(
                TraceCode::MANUAL_ACTION_PROCESS_BANK_TRANSFER_FAILURE, [
                "id" => $bankTransfer->getId()
            ]);

            throw new Exception\BadRequestException(ErrorCode::BANK_TRANSFER_PROCESSING_FAILED,null,[
                "bank_transfer_id" => $id,
            ]);
        }

        $this->trace->info(
            TraceCode::MANUAL_ACTION_PROCESS_BANK_TRANSFER_SUCCESS, [
            "id" => $bankTransfer->getId()
        ]);
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
        $paymentSuccess = false,
        $isCollectXBankTransfer = false
    )
    {
        $provider = $bankTransferRequest->getGateway();

        $isExpected = null;

        if ($bankTransfer !== null)
        {
            $isExpected = $bankTransfer->isExpected();

            $errorMessage = $errorMessage ?? $bankTransfer->getUnexpectedReason();
        }

        if ($bankTransferRequest->isCollectXBankTransfer())
        {
            $bankTransferRequest->removeCollectXAttributes();
        }

        $this->bankTransferRequestCore
            ->updateBankTransferRequest($bankTransferInput[Entity::REQ_UTR], $paymentSuccess, $errorMessage, $bankTransferRequest);

        $this->virtualAccountMetrics
            ->pushPaymentMetrics(Constants\Entity::BANK_TRANSFER, $isExpected, $paymentSuccess, $provider, $errorMessage, isCollectXBankTransfer: $isCollectXBankTransfer);
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
        return $this->getFees($bankTransfer->getAmount(), $merchant, Currency\Currency::INR);
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
            case 'bank_transfer_process_rbl':
            case 'bank_transfer_process_axis':
            case 'bank_transfer_process_icici':
            case 'bank_transfer_process_hdfc_ecms':
                $properties = [
                    'source'        => 'callback',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_process_rbl_internal':
            case 'bank_transfer_process_axis_internal':
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

            case 'bank_transfer_process_internal':
                $properties = [
                    'source'              => 'callback',
                    'request_from'        => 'bank',
                    'sc_service_callback' => true
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
    }

    public function createBankTransferViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        if(empty($whitelistIds) === false)
        {
            $bankTransfers = $this->repo->bank_transfer->fetchCreatedBankTransferWhereTxnIdNullAndIdsIn($whitelistIds);

            return $this->processBankTransferViaLedgerCronJob($blacklistIds, $bankTransfers, true);
        }

        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all bank transfers created in the last 24 hours.
            // Doing this 3 times in for loop to fetch bank transfers created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $bankTransfers = $this->repo->bank_transfer->fetchCreatedBankTransferAndTxnIdNullBetweenTimestamp($i, $limit);

            $this->processBankTransferViaLedgerCronJob($blacklistIds, $bankTransfers);
        }
    }

    private function processBankTransferViaLedgerCronJob(array $blacklistIds, $bankTransfers, bool $skipChecks = false)
    {
        foreach ($bankTransfers as $bt)
        {
            try
            {
                /*
                 * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                 * only then skip the merchant.
                 */
                if ($bt->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
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

                if($skipChecks === false)
                {
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

                $this->trace->count(Metric::LEDGER_STATUS_CRON_FAILURE_COUNT,
                                    [
                                        'environment'      => $this->app['env'],
                                        'entity'           => 'bank_transfer'
                                    ]);

                continue;
            }
        }
    }

    public function createAndAuthorizePaymentForIntlBankTransfer($input,$merchantId,$webhookRequest)
    {
        try
        {
            $payments = $this->createPaymentEntitiesForIntlBankTransfer($input,$merchantId,$webhookRequest);

            // Merchants should add customer billing address from merchant dashboard
            // https://razorpay.slack.com/archives/C024U3B04LD/p1682496775025409?thread_ts=1681996740.555379&cid=C024U3B04LD
            // Commenting for now
            //
            // $this->createAddressEntityForB2B($input,$payment);
            //
            try {
                foreach ($payments as $payment) {
                    $this->saveSenderDetailsForIntlBankTransfer($input,$payment);
                }

            }
            catch (\Throwable $e) {
                $error = $e->getError();
                $errMsg = $e->getMessage() ?? '';
                $errCode = $error->getInternalErrorCode() ?? '';
                $this->trace->error(
                    TraceCode::SAVE_SENDER_ADDRESS_FAILED,
                    [
                        'err_msg'     => $errMsg,
                        'err_code'    => $errCode,
                        'merchant_id' => $merchantId,
                        'gateway'     => Payment\Gateway::CURRENCY_CLOUD,
                    ]);

            }

            foreach ($payments as $payment)
            {
                $this->authorizePaymentForIntlBankTransfer($payment);

                $this->getNewProcessor($payment->merchant)->autoCapturePaymentIfApplicable($payment);
            }
        }
        catch (\Exception $e)
        {
            $error = $e->getError();
            $errMsg = $e->getMessage() ?? '';
            $errCode = $error->getInternalErrorCode() ?? '';

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED, null,
            [
                'input'       => $input,
                'merchant_id' => $merchantId,
                'gateway'     => Payment\Gateway::CURRENCY_CLOUD,
                'err_msg'     => $errMsg,
                'err_code'    => $errCode,
            ]);
        }

        return $payments;
    }

    protected function createPaymentEntitiesForIntlBankTransfer($response, $merchantId,$webhookRequest)
    {
        // Get Mode For Intl Bank Transfer Payment from get_sender_details API Response
        $mode = $this->getIntlBankTransferModeFromResponse($response);

        if(in_array($response['currency'], Payment\Gateway::getSupportedCurrenciesForIntlBankTransferByMode($mode),true) === false)
        {
            $this->trace->info(TraceCode::INTL_BANK_TRANSFER_CURRENCY_NOT_SUPPORTED,[
                'merchantId' => $merchantId,
                'currency'   => $response['currency'],
                'gateway'    => Payment\Gateway::CURRENCY_CLOUD,
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED, null,
            [
                'currency'   => $response['currency'],
                'gateway'    => Payment\Gateway::CURRENCY_CLOUD,
            ]);
        }

        $denomination = Currency\Currency::getDenomination(strtoupper($response['currency']));

        $amount = ((float)$response['amount'])*$denomination;


        $input = [
            Payment\Entity::AMOUNT              => $amount,
            Payment\Entity::CURRENCY            => $response['currency'],
            Payment\Entity::METHOD              => Payment\Method::INTL_BANK_TRANSFER,
            Payment\Entity::PROVIDER            => $mode
        ];

        $repo = App::getFacadeRoot()['repo'];

        $this->merchant = $repo->merchant->find($merchantId);

        $payments = [];

        while($amount > 0)
        {
            if( ($amount > self::MAX_INTL_BANK_TRANSFER_AMOUNT_BY_CURRENCIES[$response['currency']] * $denomination) &&
                ($amount - self::MAX_INTL_BANK_TRANSFER_AMOUNT_BY_CURRENCIES[$response['currency']] * $denomination > 100 * $denomination) )
            {
                $input[Payment\Entity::AMOUNT] = self::MAX_INTL_BANK_TRANSFER_AMOUNT_BY_CURRENCIES[$response['currency']] * $denomination;
            }
            else
            {
                $input[Payment\Entity::AMOUNT] = $amount;
            }

            $amount = $amount - $input[Payment\Entity::AMOUNT];

            $payments[] = $this->buildPaymentForIntlBankTransfer($input, $webhookRequest);
        }

        $this->trace->info(TraceCode::INTERNATIONAL_BANK_TRANSFERS_SPLIT_PAYMENTS,[
            'merchantId' => $merchantId,
            'gateway'    => Payment\Gateway::CURRENCY_CLOUD,
            'paymentIds' => array_map(function($payment){
                return $payment->getId();
            },$payments),
        ]);

        return $payments;
    }

    protected function buildPaymentForIntlBankTransfer($input, $webhookRequest)
    {
        $payment = new \RZP\Models\Payment\Entity;

        $payment->generateId();

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

        $payment->setReference1($webhookRequest['related_entity_short_reference']);

        $this->paymentCurrencyConversions($payment);

        $payment->setGateway(Constants\Entity::CURRENCY_CLOUD);

        $payment->setInternational();

        $this->repo->saveOrFail($payment);

        return $payment;
    }

    protected function paymentCurrencyConversions($payment)
    {
        $amount = $payment->getAmount();

        $currency = $payment->getCurrency();

        $baseAmount = (new Currency\Core)->getBaseAmount($amount, $currency, $this->merchant->getCurrency(), $input);

        // if gateway is doing currency conversions, actual rate used by gateway
        // will use lower than current rates hence we also use merchant / default
        // level percentage for lower values in base_amount for settlement.
        if ($payment->getConvertCurrency() === false ||
            ($currency !== Currency\Currency::INR && $payment->getConvertCurrency() === null))
        {
            $input['mcc_mark_down_percent'] = $this->merchant->getMccMarkdownMarkdownPercentage($payment);
            $mccMarkdownPercentage = 1 - $input['mcc_mark_down_percent'] / 100;
            $baseAmount = (int) ceil($baseAmount * $mccMarkdownPercentage);

            $paymentMetaInput = [
                'mcc_applied'           => $input['mcc_applied'],
                'mcc_mark_down_percent' => $input['mcc_mark_down_percent'],
                'mcc_forex_rate'        => $input['mcc_forex_rate'],
                'payment_id'            => $payment->getId(),
            ];

            $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

            $paymentMetaEntity->payment()->associate($payment);
        }

        $payment->setBaseAmount($baseAmount);
    }

    protected function saveSenderDetailsForIntlBankTransfer($response, $payment)
    {
        /*
            Sample Address by Gateway - "sender": "Joe Bloggs;1 Street, City, GB, Postcode;GB;1111111111;;00000000",
        */
        // "FUNDACIO INSTITUT D'INVESTIGACIO SA;ES;ES;ES2021000551580200293352;CAIXESBBXXX;"
        $senderDetails = explode(';',$response['sender']);
        $address = explode(',',$senderDetails[1]);

        if(!isset($address))
        {
            $this->trace->info(TraceCode::RAW_ADDRESS_CREATE_REQUEST,[
                'sender_details' => $senderDetails,
            ]);
        }

        $billingAddressFromInput['type']        = Address\Type::SENDER_ADDRESS;
        $billingAddressFromInput['name']        = trim($senderDetails[0]);
        $billingAddressFromInput['zipcode']     = trim(last($address));
        $billingAddressFromInput['line1']       = trim($address[0]);
        if(count($address)>1) {
            $billingAddressFromInput['city']    = trim($address[1]);
        }
        $billingAddressFromInput['country']     = trim($senderDetails[2]);

        if(empty($billingAddressFromInput['line1']) === true or strlen($billingAddressFromInput['line1']) > 255) {
            $billingAddressFromInput['line1'] = BankTransferConstants::ADDRESS_ONE_NOT_AVAILABLE;
        }

        if(empty($billingAddressFromInput['city']) === true or (strlen($billingAddressFromInput['city'])<2 or strlen($billingAddressFromInput['city'])>32)) {
            $billingAddressFromInput['city'] = BankTransferConstants::CITY_NOT_AVAILABLE;
        }

        if(!ctype_digit($billingAddressFromInput['zipcode']) or strlen($billingAddressFromInput['zipcode'])<2 or strlen($billingAddressFromInput['zipcode'])>10) {
            unset($billingAddressFromInput['zipcode']);
        }
        $this->trace->info(TraceCode::ADDRESS_CREATE_REQUEST,[
            'billing_address' => $billingAddressFromInput,
            'payment_id'   => $payment->getId()
        ]);
        if(isset($billingAddressFromInput['country']) or isset($billingAddressFromInput['name'])) {
            (new Address\Core)->create($payment, $payment->getEntity(), $billingAddressFromInput);
        }
    }

    protected function authorizePaymentForIntlBankTransfer($payment)
    {
        $payment->setGateway(Constants\Entity::CURRENCY_CLOUD);

        $payment->setInternational();

        $payment->setAuthenticatedTimestamp();

        $payment->setStatus(Payment\Status::AUTHORIZED);

        $payment->setAmountAuthorized();

        $payment->setAuthorizeTimestamp();

        $this->repo->payment->saveOrFail($payment);

        $this->sendInvoiceToSqs($payment);
    }

    // Send payment details to cross border SQS queue
    protected function sendInvoiceToSqs($payment): void
    {
        try {
            $queueName = $this->app['config']->get('queue.cross_border_document_queue.' . Mode::LIVE);

            $data = [
                'payment_id' => $payment->getId(),
                'merchant_id' => $payment->getMerchantId(),
            ];

            $this->trace->info(TraceCode::PROCESS_CB_SQS_TASK,[
                'payment_id' => $payment->getId(),
                'merchant_id'   => $payment->getMerchantId()
            ]);

            Queue::connection('sqs')->pushRaw(json_encode($data), $queueName);

        } catch (\Exception $e)
        {
            $errMsg = $e->getMessage() ?? '';
            $errCode = $e->getCode() ?? '';

            $this->trace->error(
                TraceCode::SQS_PUSH_FAILED_FOR_CROSS_BORDER_TASK_QUEUE,
                [
                    'err_msg'     => $errMsg,
                    'err_code'    => $errCode,
                ]);
        }

    }

    public function capturePaymentForB2B($payment, $input = [])
    {
            if($payment->isAuthorized())
            {
                $merchantId = $payment->getMerchantId();

                $merchant = $this->repo->merchant->find($merchantId);

                $paymentProcessor = new PaymentProcessor($merchant);

                $values = [
                    Payment\Entity::AMOUNT => $payment->getAmount(),
                    Payment\Entity::CURRENCY => $payment->getCurrency(),
                ];

                $paymentProcessor->capture($payment,$values);
            }
            else
            {
                $this->trace->info(TraceCode::B2B_PAYMENT_CAPTURE_FAILURE,[
                    'payment_id' => $payment->getId(),
                    'reason'     => 'Payment Not Authorized yet',
                ]);
            }
    }

    protected function getIntlBankTransferModeFromResponse(array $response)
    {
        $currency = $response['currency'];
        $receiving_account_iban = $response['receiving_account_iban'];

        if(isset($receiving_account_iban) === true)
        {
            return IntlBankTransfer::SWIFT;
        }
        else
        {
            return Payment\Gateway::getIntlBankTransferModeByCurrency($currency);
        }
    }

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $bankTransfer = $this->repo->bank_transfer->find($entityId);

        if ($bankTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                ['merchant_id' => $bankTransfer->getMerchantId()]);
        }

        list($entityId, $txnId) = $this->mutex->acquireAndRelease('bt_' . $entityId,
            function () use ($bankTransfer, $ledgerResponse)
            {
                $bankTransfer->reload();
                $journalId = $ledgerResponse["id"];
                $balance = Transaction\Processor\Ledger\Base::getMerchantBalanceFromLedgerResponse($ledgerResponse);

                $tempBankTransfer = $bankTransfer;
                list($bankTransfer, $txn) = $this->repo->transaction(function() use ($tempBankTransfer, $journalId, $balance)
                {
                    $bankTransfer = clone $tempBankTransfer;

                    list ($txn, $feeSplit) = (new Transaction\Processor\BankTransfer($tempBankTransfer))->createTransactionWithIdAndLedgerBalance($journalId, intval($balance));
                    $this->repo->saveOrFail($txn);

                    $bankTransfer->setTransactionId($txn->getId());
                    $this->repo->saveOrFail($bankTransfer);

                    return [$bankTransfer, $txn];
                });

                // dispatch event for txn created
                // if merchant is in experiment, it goes in to new flow, else old flow.

                (new Processor())->dispatchEventForTransactionCreatedForTxnDependentMerchants($bankTransfer, $txn);

                return [
                    $bankTransfer->getPublicId(),
                    $txn->getPublicId(),
                ];
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return [
            'entity_id' => $entityId,
            'txn_id'    => $txnId
        ];
    }

    /**
     * This function will perform the following tasks after successful journal creation on CLS for RX reverse shadow merchants
     * update the merchant balance, update the transaction id in the bank transfer entity, save fee breakup entity,
     * push api.transaction.created event
     * @param string $entityId
     * @param array $ledgerResponse
     * @return mixed
     * @throws Exception\LogicException
     */
    public function updateBalanceAndTransactionIDInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $bankTransfer = $this->repo->bank_transfer->find($entityId);

        if ($bankTransfer->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                ['merchant_id' => $bankTransfer->getMerchantId()]);
        }

        $journalId = $ledgerResponse[Entity::ID];

        // Check if worker is processing duplicate request
        if ($this->isTransactionIdUpdated($bankTransfer, $journalId) === true)
        {
            return [
                self::IS_DUPLICATE => true
            ];
        }

        $merchantId = $ledgerResponse[LedgerConstants::LEDGER_ENTRY][0][Entity::MERCHANT_ID];
        $newBalance = Transaction\Processor\Ledger\Base::getMerchantBalanceFromLedgerResponse($ledgerResponse);

        $this->mutex->acquireAndRelease('bt_' . $entityId,
            function () use ($bankTransfer, $entityId, $journalId, $merchantId, $newBalance)
            {
                $bankTransfer->reload();

                if ($this->isTransactionIdUpdated($bankTransfer, $journalId) === true)
                {
                    return [
                        self::IS_DUPLICATE => true
                    ];
                }

                $this->repo->transaction(function() use ($bankTransfer, $entityId, $journalId, $newBalance)
                {
                    (new Transaction\Processor\BankTransfer($bankTransfer))->updateBalanceForLedgerReverseShadow($entityId, $journalId, $newBalance);

                    $bankTransfer->setTransactionId($journalId);
                    $this->repo->saveOrFail($bankTransfer);
                });

                // dispatch event for ledger txn created
                (new Processor())->dispatchEventForLedgerTransactionCreated($bankTransfer, $journalId, $merchantId);
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return [
            'entity_id' => $entityId,
            'txn_id'    => $journalId
        ];
    }

    public function makePayoutAndTransferCommission($input, $mii, $merchantId, $commissionFee)
    {
        $notes = $mii->getNotes();

        try{
            if(isset($notes['beneficiary_id']) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                    null,
                    "Beneficiary Not present for the merchant"
                );
            }

            $payoutRequest = $this->createRequestBodyForCCPayouts($input, $mii);

            $payoutResponse = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'payment_create',$payoutRequest);

            $this->trace->info(TraceCode::PAYOUT_TRIGGERED_FROM_VA,[
                'payout_status' => $payoutResponse['data']['status'],
                'payout_id'     => $payoutResponse['data']['id'],
            ]);
        }catch (\Exception $ex)
        {
            $this->trace->info(TraceCode::PAYOUT_TRIGGER_FAILED_FROM_VA,[
                'error_message' => $ex->getMessage()
            ]);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_FAILED_UNKNOWN_ERROR,
                null,
            );
        }

        try{
            $RZPCommissionFeeAccountId = $this->app['config']->get('gateway.currency_cloud.rzp_commission_fee_account_id');

            $transferRequest = [
                'currency'              => $input['currency'],
                'amount'                => strval($commissionFee),
                'reason'                => BankTransferConstants::COMMISSION_TRANSFER_REASON.";" . $merchantId,
                'destination_account_id'=> $RZPCommissionFeeAccountId,
                'payment_id'            => $payoutResponse['data']['id'],
                'source_account_id'     => $mii->getIntegrationKey(),
            ];

            $transferResponse = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'create_transfer',$transferRequest);

            $this->trace->info(TraceCode::TRANSFER_OF_COMMISSION_FEE_FOR_PAYOUT_SUCCESS,[
                'transfer_status'   => $transferResponse['data']['status'],
                'transfer_id'         => $transferResponse['data']['id'],
            ]);
        }catch (\Exception $ex)
        {
            $this->trace->info(TraceCode::TRANSFER_OF_COMMISSION_FEE_FOR_PAYOUT_FAILED,[
                'input'         => $input,
                'error_message' => $ex->getMessage()
            ]);
        }
    }

    protected function createRequestBodyForCCPayouts($input, $mii)
    {
        $notes = $mii->getNotes();
        $request = [
            'currency'              => $input['currency'],
            'amount'                => strval($input['amount']),
            'reason'                => $input['reason'],
            'reference'             => $mii->getMerchantId(),
            'beneficiary_id'        => $notes['beneficiary_id'],
            'unique_request_id'     => UniqueIdEntity::generateUniqueId(),
            'on_behalf_of'          => $mii->getReferenceId(),
        ];

        return $request;
    }

    // Function to check if the entity's transaction id field has been updated
    protected function isTransactionIdUpdated($bankTransfer, $journalId)
    {
        if (empty($bankTransfer->getTransactionIdViaAttribute()) === false)
        {
            $this->trace->info(TraceCode::LEDGER_API_TXN_DUAL_WRITE_DUPLICATE_REQUEST, [
                'id' => $journalId
            ]);

            return true;
        }

        return false;
    }
    public function isWebhookSyncFiringEnabled($merchantId,$experimentName,string $checkVariant)
    {
        try
        {
            $experimentId = $this->app['config']->get($experimentName);

            $response = $this->app['splitzService']->evaluateRequest([
                'id' => $merchantId,
                'experiment_id' => $experimentId,
            ]);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'merchant_id' => $merchantId,
                'experiment_id' => $experimentId,
                'result' => $response
            ]);
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id' => $merchantId,
                'experiment_id' => $this->app['config']->get($experimentName) ?? null
            ]);

            return false;

        }

        return $response['response']['variant']['name'] == $checkVariant;

    }
}
